<?php

namespace Alliswell\Appyhp\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SetupController
{
    /**
     * @var array<int, string>
     */
    private array $allowedPanels = [
        'workflows',
        'directories',
    ];

    public function show(): JsonResponse
    {
        $setup = $this->readSetup();
        $this->writeSetup($setup);

        return response()->json($setup);
    }

    public function update(Request $request): JsonResponse
    {
        $setup = array_replace_recursive($this->readSetup(), $request->all());

        if (isset($setup['activePanel']) && ! in_array($setup['activePanel'], $this->allowedPanels, true)) {
            $setup['activePanel'] = 'workflows';
        }

        $this->writeSetup($setup);

        return response()->json($setup);
    }

    /**
     * @return array<string, mixed>
     */
    private function readSetup(): array
    {
        $path = $this->setupPath();

        if (! is_file($path)) {
            return $this->defaultSetup();
        }

        $contents = file_get_contents($path);

        if ($contents === false || trim($contents) === '') {
            return $this->defaultSetup();
        }

        $decoded = json_decode($contents, true);

        if (! is_array($decoded)) {
            return $this->defaultSetup();
        }

        $setup = array_replace_recursive($this->defaultSetup(), $decoded);

        if (! in_array($setup['activePanel'], $this->allowedPanels, true)) {
            $setup['activePanel'] = 'workflows';
        }

        return $setup;
    }

    /**
     * @param array<string, mixed> $setup
     */
    private function writeSetup(array $setup): void
    {
        $directory = dirname($this->setupPath());

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            abort(500, 'Unable to create Appyhp setup directory.');
        }

        $encoded = json_encode($setup, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($encoded === false || file_put_contents($this->setupPath(), $encoded . PHP_EOL, LOCK_EX) === false) {
            abort(500, 'Unable to write Appyhp setup file.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultSetup(): array
    {
        return [
            'activePanel' => 'workflows',
            'autosave' => false,
        ];
    }

    private function setupPath(): string
    {
        return storage_path('app/appyhp/setup.json');
    }
}
