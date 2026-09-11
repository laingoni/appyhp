<?php

namespace Alliswell\Appyhp\Support;

class RuntimeStorage
{
    public function path(string $relative = ''): string
    {
        $root = rtrim((string) config('appyhp.runtime_path', storage_path('app/appyhp')), DIRECTORY_SEPARATOR);
        if ($root === '' || (! str_starts_with($root, DIRECTORY_SEPARATOR) && preg_match('/\A[A-Za-z]:[\\\\\/]/', $root) !== 1)) {
            throw new \RuntimeException('APPYHP_RUNTIME_PATH must be an absolute path.');
        }

        return $relative === '' ? $root : $root . DIRECTORY_SEPARATOR . ltrim($relative, DIRECTORY_SEPARATOR);
    }

    public function ensure(string $relative = '', int $mode = 0700): string
    {
        $path = $this->path($relative);
        if (! is_dir($path) && ! mkdir($path, $mode, true) && ! is_dir($path)) {
            throw new \RuntimeException('Unable to create Appyhp runtime directory.');
        }
        if (! chmod($path, $mode)) {
            throw new \RuntimeException('Unable to secure Appyhp runtime directory.');
        }

        if ($relative === '') {
            $this->migrateLegacyStorage($path);
            $this->secureKnownPaths($path);
        }

        return $path;
    }

    public function writeJson(string $relative, array $value): void
    {
        if ($relative === '' || basename($relative) !== $relative || ! str_ends_with($relative, '.json')) {
            throw new \InvalidArgumentException('Runtime JSON files must use a simple relative filename.');
        }

        $directory = $this->ensure();
        $temporary = tempnam($directory, 'appyhp-');
        if ($temporary === false) {
            throw new \RuntimeException('Unable to create a temporary Appyhp runtime file.');
        }

        try {
            if (! chmod($temporary, 0600)) {
                throw new \RuntimeException('Unable to secure an Appyhp runtime file.');
            }
            $contents = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
            if (file_put_contents($temporary, $contents, LOCK_EX) === false || ! rename($temporary, $directory . DIRECTORY_SEPARATOR . $relative)) {
                throw new \RuntimeException('Unable to save Appyhp runtime state.');
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    private function migrateLegacyStorage(string $target): void
    {
        $legacy = storage_path('app/appyhp');
        if (! is_dir($legacy) || realpath($legacy) === realpath($target)) {
            return;
        }

        $items = scandir($legacy);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $source = $legacy . DIRECTORY_SEPARATOR . $item;
            $destination = $target . DIRECTORY_SEPARATOR . $item;
            if (! file_exists($destination)) {
                @rename($source, $destination);
            }
        }
    }

    private function secureKnownPaths(string $root): void
    {
        foreach (glob($root . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
            if (is_file($file) && ! is_link($file) && ! chmod($file, 0600)) {
                throw new \RuntimeException('Unable to secure Appyhp runtime state.');
            }
        }

        $sessions = $root . DIRECTORY_SEPARATOR . 'sessions';
        if (is_dir($sessions) && ! is_link($sessions) && ! chmod($sessions, 0700)) {
            throw new \RuntimeException('Unable to secure Appyhp Studio sessions.');
        }
    }
}
