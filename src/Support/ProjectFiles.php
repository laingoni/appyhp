<?php

namespace Alliswell\Appyhp\Support;

use Illuminate\Validation\ValidationException;

class ProjectFiles
{
    public const MAX_BYTES = 131072;

    public function resolve(string $relative): string
    {
        $parts = explode('/', $relative);
        $roots = config('appyhp.ai.source_roots', []);
        if (! in_array($parts[0], $roots, true) || count($parts) < 2
            || ! preg_match('/\.(php|js|jsx|ts|tsx|vue|svelte|css|json)$/i', $relative)) {
            throw ValidationException::withMessages(['path' => 'Choose a source file inside a configured project source folder.']);
        }

        $path = rtrim(base_path(), DIRECTORY_SEPARATOR);
        foreach ($parts as $part) {
            if (! preg_match('/\A[A-Za-z0-9_][A-Za-z0-9_.-]*\z/D', $part)) {
                throw ValidationException::withMessages(['path' => 'Use a project-relative path without hidden folders or parent traversal.']);
            }
            $path .= DIRECTORY_SEPARATOR . $part;
            if (is_link($path)) {
                throw ValidationException::withMessages(['path' => 'Generated files cannot target symbolic links.']);
            }
        }

        return $path;
    }

    public function snapshot(string $relative): array
    {
        $path = $this->resolve($relative);
        if (! file_exists($path)) {
            return ['path' => $relative, 'exists' => false, 'hash' => null, 'content' => ''];
        }

        abort_unless(is_file($path), 422, 'The target is a folder. Choose a filename.');
        abort_if(filesize($path) > self::MAX_BYTES, 413, 'The target file is too large for AI editing.');
        $content = file_get_contents($path);
        abort_if($content === false, 500, 'Unable to read the target file.');
        abort_if(str_contains($content, "\0"), 415, 'The target must be a text source file.');

        return ['path' => $relative, 'exists' => true, 'hash' => hash('sha256', $content), 'content' => $content];
    }

    public function write(string $relative, string $content, ?string $expectedHash): array
    {
        $path = $this->resolve($relative);
        $snapshot = $this->snapshot($relative);
        abort_unless($snapshot['hash'] === $expectedHash, 409, 'The file changed since generation. Generate again to include its latest contents.');
        abort_if(strlen($content) > self::MAX_BYTES, 413, 'Generated code is too large to write.');
        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            abort(500, 'Unable to create the target folder.');
        }

        $handle = fopen($path, $snapshot['exists'] ? 'r+b' : 'x+b');
        abort_if($handle === false, 409, 'Unable to open the target. Check its permissions or generate again.');
        try {
            abort_unless(flock($handle, LOCK_EX), 500, 'Unable to lock the target file.');
            $current = stream_get_contents($handle);
            abort_unless(! $snapshot['exists'] || hash('sha256', $current) === $expectedHash, 409, 'The file changed since generation. Generate again.');
            rewind($handle);
            abort_unless(ftruncate($handle, 0), 500, 'Unable to write the target file.');
            $written = 0;
            while ($written < strlen($content)) {
                $bytes = fwrite($handle, substr($content, $written));
                abort_if($bytes === false || $bytes === 0, 500, 'Unable to finish writing the target file.');
                $written += $bytes;
            }
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        return ['path' => $relative, 'hash' => hash('sha256', $content), 'saved' => true];
    }

    public function project(): array
    {
        $composer = $this->manifest('composer.json');
        $package = $this->manifest('package.json');
        $dependencies = array_merge($package['devDependencies'] ?? [], $package['dependencies'] ?? []);
        $frontend = 'blade';
        foreach (['vue' => '@inertiajs/vue3', 'react' => '@inertiajs/react', 'svelte' => '@inertiajs/svelte'] as $framework => $adapter) {
            if (isset($dependencies[$adapter])) {
                $frontend = $framework;
                break;
            }
        }

        $folders = [];
        foreach (config('appyhp.ai.source_roots', []) as $root) {
            $this->folders($root, $folders, 0);
        }
        sort($folders);

        return [
            'laravel' => app()->version(),
            'php' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
            'frontend' => $frontend,
            'composer' => array_intersect_key($composer['require'] ?? [], array_flip(['php', 'laravel/framework', 'inertiajs/inertia-laravel'])),
            'frontend_dependencies' => array_intersect_key($dependencies, array_flip(['@inertiajs/vue3', '@inertiajs/react', '@inertiajs/svelte', '@inertiajs/vite', 'vue', 'react', 'svelte', 'typescript', 'vite'])),
            'namespaces' => $composer['autoload']['psr-4'] ?? ['App\\' => 'app/'],
            'folders' => $folders,
        ];
    }

    private function manifest(string $name): array
    {
        $path = base_path($name);
        if (! is_file($path) || is_link($path) || filesize($path) > self::MAX_BYTES) {
            return [];
        }

        $decoded = json_decode(file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function folders(string $relative, array &$folders, int $depth): void
    {
        $path = base_path($relative);
        if (count($folders) >= 300 || $depth > 6 || ! is_dir($path) || is_link($path)) {
            return;
        }
        $folders[] = $relative;
        foreach (scandir($path) ?: [] as $name) {
            if (preg_match('/\A[A-Za-z0-9_][A-Za-z0-9_.-]*\z/D', $name)
                && ! in_array($name, ['node_modules', 'vendor'], true)) {
                $this->folders($relative . '/' . $name, $folders, $depth + 1);
            }
        }
    }
}
