<?php

namespace Alliswell\Appyhp\Http\Controllers;

use Alliswell\Appyhp\Support\ModuleFileGenerator;
use Alliswell\Appyhp\Support\RuntimeStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DirectoryController
{
    public function __construct(private RuntimeStorage $runtime, private ModuleFileGenerator $moduleFiles) {}

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
            'hash' => hash('sha256', $contents),
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
        abort_if($relativePath === '' || str_ends_with($relativePath, '/'), 422, 'Choose a filename.');
        $file = $this->basePath() . DIRECTORY_SEPARATOR . $relativePath;
        $this->assertInsideBase($file);

        if (is_link($file)) {
            abort(422, 'Module files cannot target symbolic links.');
        }

        if (file_exists($file) && ! is_file($file)) {
            abort(422, 'The target is a folder. Choose a filename.');
        }

        $content = (string) $request->input('content', '');

        $createOnly = (bool) $request->input('createOnly', false);
        $generation = null;
        $emptyModuleFile = is_file($file) && filesize($file) === 0;
        if ($createOnly && (! file_exists($file) || $emptyModuleFile) && $content === '' && is_string($request->input('moduleType'))) {
            $config = $request->input('moduleConfig', []);
            if ($emptyModuleFile && ! unlink($file)) {
                abort(500, 'Unable to replace the empty module file.');
            }
            $generation = $this->moduleFiles->generate(
                $relativePath,
                (string) $request->input('moduleType'),
                is_array($config) ? $config : [],
            );
        } elseif (! $createOnly || ! file_exists($file)) {
            $this->resolveExistingPath($this->parentRelativePath($relativePath));
            if (file_put_contents($file, $content, LOCK_EX) === false) {
                abort(500, 'Unable to save file.');
            }
        }

        return response()->json([
            'path' => $relativePath,
            'saved' => true,
            'generation' => $generation,
            'hash' => is_file($file) ? hash_file('sha256', $file) : null,
        ]);
    }

    public function rename(Request $request): JsonResponse
    {
        $sourceRelativePath = $this->cleanRelativePath((string) $request->input('path', ''));
        $source = $this->basePath() . DIRECTORY_SEPARATOR . $sourceRelativePath;
        if (! file_exists($source) && (bool) $request->input('allowMissing', false)) {
            return response()->json(['path' => $sourceRelativePath, 'saved' => true]);
        }
        $source = $this->resolveExistingPath($sourceRelativePath);
        abort_unless(is_file($source), 422, 'Only files can be renamed here.');
        [, $name] = $this->validatedParentAndName($request->merge([
            'parent' => $this->parentRelativePath($sourceRelativePath),
        ]));
        $target = $this->targetPath(dirname($source), $name);

        if ($target !== $source && file_exists($target)) {
            abort(409, 'A file or folder already exists with that name.');
        }

        if ($target !== $source && ! rename($source, $target)) {
            abort(500, 'Unable to rename file.');
        }

        $this->renameMetadata($sourceRelativePath, $this->relativeFromAbsolute($target));

        return response()->json(array_merge($this->pathPayload($target), ['saved' => true]));
    }

    public function metadata(Request $request): JsonResponse
    {
        $path = $this->cleanRelativePath((string) $request->query('path', ''));
        $this->resolveExistingPath($path);

        return response()->json(['path' => $path, 'notes' => $this->readMetadata()[$path]['notes'] ?? '']);
    }

    public function updateMetadata(Request $request): JsonResponse
    {
        $request->validate([
            'path' => ['required', 'string', 'max:700'],
            'notes' => ['nullable', 'string', 'max:20000'],
        ]);
        $path = $this->cleanRelativePath((string) $request->input('path', ''));
        $this->resolveExistingPath($path);
        $notes = trim((string) $request->input('notes', ''));
        $metadata = $this->readMetadata();
        if ($notes === '') {
            unset($metadata[$path]);
        } else {
            $metadata[$path] = ['notes' => $notes];
        }
        $this->writeMetadata($metadata);

        return response()->json(['path' => $path, 'notes' => $notes, 'saved' => true]);
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

    public function destroy(Request $request): JsonResponse
    {
        $relativePath = $this->cleanRelativePath((string) $request->input('path', ''));
        abort_if($relativePath === '', 422, 'The project root cannot be deleted.');
        $candidate = $this->basePath() . DIRECTORY_SEPARATOR . $relativePath;
        abort_if(is_link($candidate), 422, 'Symbolic links cannot be deleted from Appyhp Studio.');
        $target = $this->resolveExistingPath($relativePath);

        if (is_dir($target)) {
            $this->deleteDirectory($target);
        } elseif (! unlink($target)) {
            abort(500, 'Unable to delete file.');
        }

        $metadata = $this->readMetadata();
        foreach (array_keys($metadata) as $path) {
            if ($path === $relativePath || Str::startsWith($path, $relativePath . '/')) {
                unset($metadata[$path]);
            }
        }
        $this->writeMetadata($metadata);

        return response()->json(['path' => $relativePath, 'deleted' => true]);
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

    private function deleteDirectory(string $directory): void
    {
        $items = scandir($directory);
        if ($items === false) {
            abort(500, 'Unable to read folder.');
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path) && ! is_link($path)) {
                $this->deleteDirectory($path);
            } elseif (! unlink($path)) {
                abort(500, 'Unable to delete folder contents.');
            }
        }

        if (! rmdir($directory)) {
            abort(500, 'Unable to delete folder.');
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

    private function parentRelativePath(string $path): string
    {
        return trim(str_replace('\\', '/', dirname($path)), '. /');
    }

    private function metadataPath(): string
    {
        return $this->runtime->path('directory-metadata.json');
    }

    private function readMetadata(): array
    {
        $contents = @file_get_contents($this->metadataPath());
        $decoded = $contents ? json_decode($contents, true) : [];
        return is_array($decoded) ? $decoded : [];
    }

    private function writeMetadata(array $metadata): void
    {
        $this->runtime->ensure();
        if (file_put_contents($this->metadataPath(), json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL, LOCK_EX) === false) {
            abort(500, 'Unable to save file notes.');
        }
    }

    private function renameMetadata(string $oldPath, string $newPath): void
    {
        $metadata = $this->readMetadata();
        if (isset($metadata[$oldPath])) {
            $metadata[$newPath] = $metadata[$oldPath];
            unset($metadata[$oldPath]);
            $this->writeMetadata($metadata);
        }
    }

    private function basePath(): string
    {
        return rtrim(str_replace('\\', '/', base_path()), '/');
    }
}
