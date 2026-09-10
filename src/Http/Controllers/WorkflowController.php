<?php

namespace Alliswell\Appyhp\Http\Controllers;

use Alliswell\Appyhp\Support\RuntimeStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowController
{
    public function __construct(private RuntimeStorage $runtime) {}

    public function index(): JsonResponse
    {
        $workflows = $this->readWorkflows();
        $this->writeWorkflows($workflows);

        return response()->json([
            'workflows' => $workflows,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'workflows' => ['required', 'array'],
        ]);

        $workflows = $this->normalizeWorkflows($payload['workflows']);
        $this->writeWorkflows($workflows);

        return response()->json([
            'workflows' => $workflows,
            'saved' => true,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readWorkflows(): array
    {
        $path = $this->workflowPath();

        if (! is_file($path)) {
            return [$this->starterWorkflow()];
        }

        $contents = file_get_contents($path);

        if ($contents === false || trim($contents) === '') {
            return [$this->starterWorkflow()];
        }

        $decoded = json_decode($contents, true);

        if (! is_array($decoded)) {
            return [$this->starterWorkflow()];
        }

        $source = array_is_list($decoded) ? $decoded : ($decoded['workflows'] ?? []);
        $workflows = $this->normalizeWorkflows(is_array($source) ? $source : []);

        return $workflows === [] ? [$this->starterWorkflow()] : $workflows;
    }

    /**
     * @param array<int, mixed> $workflows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeWorkflows(array $workflows): array
    {
        $normalized = [];

        foreach ($workflows as $index => $workflow) {
            if (! is_array($workflow)) {
                continue;
            }

            $normalized[] = [
                'id' => $this->stringValue($workflow['id'] ?? null, 'wf_' . ($index + 1)),
                'name' => $this->stringValue($workflow['name'] ?? null, 'Workflow ' . ($index + 1)),
                'description' => $this->stringValue($workflow['description'] ?? null, 'Laravel application workflow'),
                'modules' => $this->normalizeModules($workflow['modules'] ?? []),
                'edges' => $this->normalizeEdges($workflow['edges'] ?? []),
                'meta' => is_array($workflow['meta'] ?? null) ? $workflow['meta'] : [],
            ];
        }

        return $normalized;
    }

    /**
     * @param mixed $modules
     * @return array<int, array<string, mixed>>
     */
    private function normalizeModules(mixed $modules): array
    {
        if (! is_array($modules)) {
            return [];
        }

        $normalized = [];

        foreach ($modules as $index => $module) {
            if (! is_array($module)) {
                continue;
            }

            $config = $module['config'] ?? [];

            $normalized[] = [
                'id' => $this->stringValue($module['id'] ?? null, 'mod_' . ($index + 1)),
                'type' => $this->stringValue($module['type'] ?? null, 'service'),
                'label' => $this->stringValue($module['label'] ?? null, 'Module'),
                'description' => $this->stringValue($module['description'] ?? null, ''),
                'x' => (int) ($module['x'] ?? 80),
                'y' => (int) ($module['y'] ?? 120),
                'config' => is_array($config) ? $config : [],
            ];
        }

        return $normalized;
    }

    /**
     * @param mixed $edges
     * @return array<int, array<string, mixed>>
     */
    private function normalizeEdges(mixed $edges): array
    {
        if (! is_array($edges)) {
            return [];
        }

        $normalized = [];

        foreach ($edges as $index => $edge) {
            if (! is_array($edge)) {
                continue;
            }

            $normalized[] = [
                'id' => $this->stringValue($edge['id'] ?? null, 'edge_' . ($index + 1)),
                'from' => $this->stringValue($edge['from'] ?? null),
                'to' => $this->stringValue($edge['to'] ?? null),
                'label' => $this->stringValue($edge['label'] ?? null, ''),
                'type' => in_array(($edge['type'] ?? 'flex'), ['flex', 'stiff'], true) ? ($edge['type'] ?? 'flex') : 'flex',
            ];
        }

        return $normalized;
    }

    /**
     * @param array<int, array<string, mixed>> $workflows
     */
    private function writeWorkflows(array $workflows): void
    {
        $this->runtime->ensure();

        $encoded = json_encode([
            'workflows' => $workflows,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($encoded === false || file_put_contents($this->workflowPath(), $encoded . PHP_EOL, LOCK_EX) === false) {
            abort(500, 'Unable to write Appyhp workflows.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function starterWorkflow(): array
    {
        $now = now()->toISOString();

        return [
            'id' => 'wf_laravel_starter',
            'name' => 'Laravel App Workflow',
            'description' => 'A starter workflow for a typical Laravel application.',
            'modules' => [
                [
                    'id' => 'mod_route',
                    'type' => 'route',
                    'label' => 'Route',
                    'description' => 'HTTP route entry point.',
                    'x' => 70,
                    'y' => 140,
                    'config' => [
                        'method' => 'GET',
                        'uri' => '/users',
                        'middleware' => 'web, auth',
                    ],
                ],
                [
                    'id' => 'mod_controller',
                    'type' => 'controller',
                    'label' => 'Controller',
                    'description' => 'Handles request orchestration.',
                    'x' => 280,
                    'y' => 140,
                    'config' => [
                        'class' => 'UserController',
                        'actions' => 'index, store, show, update, destroy',
                    ],
                ],
                [
                    'id' => 'mod_model',
                    'type' => 'model',
                    'label' => 'Model',
                    'description' => 'Eloquent model and relationships.',
                    'x' => 500,
                    'y' => 80,
                    'config' => [
                        'class' => 'User',
                        'fillable' => 'name, email, password',
                        'relationships' => 'hasMany:Post',
                    ],
                ],
                [
                    'id' => 'mod_table',
                    'type' => 'table',
                    'label' => 'Table',
                    'description' => 'Database table and fields.',
                    'x' => 500,
                    'y' => 210,
                    'config' => [
                        'name' => 'users',
                        'columns' => "id:uuid\nname:string\nemail:string:unique\npassword:string\ntimestamps",
                    ],
                ],
            ],
            'edges' => [
                ['id' => 'edge_route_controller', 'from' => 'mod_route', 'to' => 'mod_controller', 'label' => 'dispatch', 'type' => 'flex'],
                ['id' => 'edge_controller_model', 'from' => 'mod_controller', 'to' => 'mod_model', 'label' => 'uses', 'type' => 'flex'],
                ['id' => 'edge_model_table', 'from' => 'mod_model', 'to' => 'mod_table', 'label' => 'persists', 'type' => 'stiff'],
            ],
            'meta' => [
                'createdAt' => $now,
                'updatedAt' => $now,
            ],
        ];
    }

    private function stringValue(mixed $value, string $fallback = ''): string
    {
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        return $fallback;
    }

    private function workflowPath(): string
    {
        return $this->runtime->path('workflows.json');
    }
}
