<?php

namespace Alliswell\Appyhp\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DirectoryController
{
    private const MAX_FILE_BYTES = 1048576;

    /**
     * @var array<int, string>
     */
    private array $ignoredNames = [
        '.git',
        '.idea',
        '.vscode',
        'node_modules',
        'vendor',
    ];

    public function index(Request $request): JsonResponse
    {
        $relativePath = $this->cleanRelativePath((string) $request->query('path', ''));
        $directory = $this->resolveExistingPath($relativePath);

        if (! is_dir($directory)) {
            abort(404);
        }

        return response()->json([
            'path' => $relativePath,
            'items' => $this->directoryItems($directory, $relativePath),
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $relativePath = $this->cleanRelativePath((string) $request->query('path', ''));
        $file = $this->resolveExistingPath($relativePath);

        if (! is_file($file)) {
            abort(404);
        }

        if (filesize($file) > self::MAX_FILE_BYTES) {
            abort(413, 'File is too large to edit in Appyhp Studio.');
        }

        $contents = file_get_contents($file);

        if ($contents === false) {
            abort(500, 'Unable to read file.');
        }

        if (str_contains($contents, "\0")) {
            abort(415, 'Binary files are not editable in Appyhp Studio.');
        }

        return response()->json([
            'path' => $relativePath,
            'name' => basename($file),
            'content' => $contents,
        ]);
    }

    public function storeFile(Request $request): JsonResponse
    {
        [$parent, $name] = $this->validatedParentAndName($request);
        $target = $this->targetPath($parent, $name);

        if (file_exists($target)) {
            abort(409, 'A file or folder already exists with that name.');
        }

        if (file_put_contents($target, '') === false) {
            abort(500, 'Unable to create file.');
        }

        return response()->json($this->pathPayload($target), 201);
    }

    public function storeFolder(Request $request): JsonResponse
    {
        [$parent, $name] = $this->validatedParentAndName($request);
        $target = $this->targetPath($parent, $name);

        if (file_exists($target)) {
            abort(409, 'A file or folder already exists with that name.');
        }

        if (! mkdir($target, 0755)) {
            abort(500, 'Unable to create folder.');
        }

        return response()->json($this->pathPayload($target), 201);
    }

    public function update(Request $request): JsonResponse
    {
        $relativePath = $this->cleanRelativePath((string) $request->input('path', ''));
        $file = $this->resolveExistingPath($relativePath);

        if (! is_file($file)) {
            abort(404);
        }

        $content = (string) $request->input('content', '');

        if (file_put_contents($file, $content, LOCK_EX) === false) {
            abort(500, 'Unable to save file.');
        }

        return response()->json([
            'path' => $relativePath,
            'saved' => true,
        ]);
    }

    public function transfer(Request $request): JsonResponse
    {
        $mode = (string) $request->input('mode', '');
        $sourceRelativePath = $this->cleanRelativePath((string) $request->input('source', ''));
        $parentRelativePath = $this->cleanRelativePath((string) $request->input('parent', ''));
        $source = $this->resolveExistingPath($sourceRelativePath);
        $parent = $this->resolveExistingPath($parentRelativePath);

        if (! in_array($mode, ['cut', 'copy'], true)) {
            abort(422, 'Choose cut or copy.');
        }

        if (! is_file($source) && ! is_dir($source)) {
            abort(404);
        }

        if (! is_dir($parent)) {
            abort(404);
        }

        $target = $this->targetPath($parent, basename($source));
        $sourcePath = rtrim(str_replace('\\', '/', $source), '/');
        $targetPath = rtrim(str_replace('\\', '/', $target), '/');

        if ($targetPath === $sourcePath || Str::startsWith($targetPath, $sourcePath . '/')) {
            abort(422, 'Cannot paste a folder into itself.');
        }

        if (file_exists($target)) {
            abort(409, 'A file or folder already exists with that name.');
        }

        if ($mode === 'cut') {
            if (! rename($source, $target)) {
                abort(500, 'Unable to move item.');
            }
        } elseif (is_dir($source)) {
            $this->copyDirectory($source, $target);
        } elseif (! copy($source, $target)) {
            abort(500, 'Unable to copy file.');
        }

        return response()->json($this->pathPayload($target), 201);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function directoryItems(string $directory, string $relativePath): array
    {
        $items = scandir($directory);

        if ($items === false) {
            abort(500, 'Unable to list directory.');
        }

        $children = [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || in_array($item, $this->ignoredNames, true)) {
                continue;
            }

            $absolutePath = $directory . DIRECTORY_SEPARATOR . $item;
            $childRelativePath = $relativePath === '' ? $item : $relativePath . '/' . $item;

            if (is_dir($absolutePath)) {
                $children[] = [
                    'name' => $item,
                    'path' => $childRelativePath,
                    'type' => 'directory',
                ];

                continue;
            }

            if (is_file($absolutePath)) {
                $children[] = [
                    'name' => $item,
                    'path' => $childRelativePath,
                    'type' => 'file',
                ];
            }
        }

        usort($children, function (array $first, array $second): int {
            if ($first['type'] !== $second['type']) {
                return $first['type'] === 'directory' ? -1 : 1;
            }

            return strcasecmp((string) $first['name'], (string) $second['name']);
        });

        return $children;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function validatedParentAndName(Request $request): array
    {
        $parentRelativePath = $this->cleanRelativePath((string) $request->input('parent', ''));
        $parent = $this->resolveExistingPath($parentRelativePath);
        $name = trim((string) $request->input('name', ''));

        if (! is_dir($parent)) {
            abort(404);
        }

        if ($name === '' || in_array($name, ['.', '..'], true) || Str::contains($name, ['/', '\\', "\0"])) {
            abort(422, 'Enter a valid file or folder name.');
        }

        return [$parent, $name];
    }

    private function targetPath(string $parent, string $name): string
    {
        $target = $parent . DIRECTORY_SEPARATOR . $name;
        $this->assertInsideBase(dirname($target));

        return $target;
    }

    private function copyDirectory(string $source, string $target): void
    {
        if (! mkdir($target, 0755)) {
            abort(500, 'Unable to copy folder.');
        }

        $items = scandir($source);

        if ($items === false) {
            abort(500, 'Unable to read folder.');
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $sourceItem = $source . DIRECTORY_SEPARATOR . $item;
            $targetItem = $target . DIRECTORY_SEPARATOR . $item;

            if (is_dir($sourceItem)) {
                $this->copyDirectory($sourceItem, $targetItem);
                continue;
            }

            if (is_file($sourceItem) && ! copy($sourceItem, $targetItem)) {
                abort(500, 'Unable to copy file.');
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function pathPayload(string $absolutePath): array
    {
        return [
            'name' => basename($absolutePath),
            'path' => $this->relativeFromAbsolute($absolutePath),
        ];
    }

    private function resolveExistingPath(string $relativePath): string
    {
        $path = $this->basePath() . ($relativePath === '' ? '' : DIRECTORY_SEPARATOR . $relativePath);
        $resolved = realpath($path);

        if ($resolved === false) {
            abort(404);
        }

        return $this->assertInsideBase($resolved);
    }

    private function assertInsideBase(string $absolutePath): string
    {
        $basePath = $this->basePath();
        $normalizedPath = rtrim(str_replace('\\', '/', $absolutePath), '/');

        if ($normalizedPath !== $basePath && ! Str::startsWith($normalizedPath, $basePath . '/')) {
            abort(403);
        }

        return $absolutePath;
    }

    private function cleanRelativePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path), '/');

        if ($path === '') {
            return '';
        }

        if (Str::contains($path, ["\0"]) || collect(explode('/', $path))->contains(fn (string $part): bool => $part === '..')) {
            abort(403);
        }

        return $path;
    }

    private function relativeFromAbsolute(string $absolutePath): string
    {
        $basePath = $this->basePath();
        $normalizedPath = str_replace('\\', '/', $absolutePath);

        return trim(Str::after($normalizedPath, $basePath), '/');
    }

    private function basePath(): string
    {
        return rtrim(str_replace('\\', '/', base_path()), '/');
    }
}
