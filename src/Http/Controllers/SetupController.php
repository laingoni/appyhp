<?php

namespace Alliswell\Appyhp\Http\Controllers;

use Alliswell\Appyhp\Support\RuntimeStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SetupController
{
    public function __construct(private RuntimeStorage $runtime) {}

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
        $values = $request->validate([
            'activePanel' => ['sometimes', 'string', Rule::in($this->allowedPanels)],
            'autosave' => ['sometimes', 'boolean'],
            'sidebarVisible' => ['sometimes', 'boolean'],
        ]);
        $setup = array_replace($this->readSetup(), $values);

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
        $setup['sidebarVisible'] = filter_var($setup['sidebarVisible'] ?? true, FILTER_VALIDATE_BOOL);
        $setup['autosave'] = filter_var($setup['autosave'] ?? false, FILTER_VALIDATE_BOOL);

        return $setup;
    }

    /**
     * @param array<string, mixed> $setup
     */
    private function writeSetup(array $setup): void
    {
        $this->runtime->writeJson('setup.json', $setup);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultSetup(): array
    {
        return [
            'activePanel' => 'workflows',
            'autosave' => false,
            'sidebarVisible' => true,
        ];
    }

    private function setupPath(): string
    {
        return $this->runtime->path('setup.json');
    }
}
