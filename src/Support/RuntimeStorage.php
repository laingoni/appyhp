<?php

namespace Alliswell\Appyhp\Support;

class RuntimeStorage
{
    public function path(string $relative = ''): string
    {
        $root = rtrim((string) config('appyhp.runtime_path', storage_path('app/appyhp')), DIRECTORY_SEPARATOR);

        return $relative === '' ? $root : $root . DIRECTORY_SEPARATOR . ltrim($relative, DIRECTORY_SEPARATOR);
    }

    public function ensure(string $relative = '', int $mode = 0755): string
    {
        $path = $this->path($relative);
        if (! is_dir($path) && ! mkdir($path, $mode, true) && ! is_dir($path)) {
            throw new \RuntimeException('Unable to create Appyhp runtime directory.');
        }

        if ($relative === '') {
            $this->migrateLegacyStorage($path);
        }

        return $path;
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
}
