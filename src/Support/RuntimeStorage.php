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
        $normalized = str_replace('\\', '/', $root);
        if (in_array('..', explode('/', $normalized), true)) {
            throw new \RuntimeException('APPYHP_RUNTIME_PATH cannot contain parent traversal.');
        }
        $public = rtrim(str_replace('\\', '/', public_path()), '/');
        if ($normalized === $public || str_starts_with($normalized, $public . '/')) {
            throw new \RuntimeException('APPYHP_RUNTIME_PATH must be outside the public web directory.');
        }
        if ($relative !== '' && (str_contains($relative, '..') || str_contains($relative, '\\') || str_starts_with($relative, '/'))) {
            throw new \InvalidArgumentException('Runtime paths must be relative and cannot contain parent traversal.');
        }

        return $relative === '' ? $root : $root . DIRECTORY_SEPARATOR . ltrim($relative, DIRECTORY_SEPARATOR);
    }

    public function ensure(string $relative = '', int $mode = 0700): string
    {
        if ($relative !== '') {
            $this->ensure();
        }
        $path = $this->path($relative);
        if (! is_dir($path) && ! mkdir($path, $mode, true) && ! is_dir($path)) {
            throw new \RuntimeException('Unable to create Appyhp runtime directory.');
        }
        if (! chmod($path, $mode)) {
            throw new \RuntimeException('Unable to secure Appyhp runtime directory.');
        }

        if ($relative === '') {
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

    public function synchronized(string $name, callable $callback): mixed
    {
        if (! preg_match('/\A[a-z-]+\z/', $name)) {
            throw new \InvalidArgumentException('Invalid runtime lock name.');
        }
        $handle = fopen($this->ensure() . DIRECTORY_SEPARATOR . $name . '.lock', 'c');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open the Appyhp state lock.');
        }
        try {
            if (! flock($handle, LOCK_EX)) {
                throw new \RuntimeException('Unable to lock Appyhp state.');
            }

            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
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
