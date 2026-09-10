<?php

namespace Alliswell\Appyhp\Support;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ModuleFileGenerator
{
    /**
     * @var array<string, array{command: string, root: string}>
     */
    private const ARTISAN_GENERATORS = [
        'controller' => ['command' => 'make:controller', 'root' => 'app/Http/Controllers'],
        'middleware' => ['command' => 'make:middleware', 'root' => 'app/Http/Middleware'],
        'request' => ['command' => 'make:request', 'root' => 'app/Http/Requests'],
        'resource' => ['command' => 'make:resource', 'root' => 'app/Http/Resources'],
        'model' => ['command' => 'make:model', 'root' => 'app/Models'],
        'factory' => ['command' => 'make:factory', 'root' => 'database/factories'],
        'seeder' => ['command' => 'make:seeder', 'root' => 'database/seeders'],
        'policy' => ['command' => 'make:policy', 'root' => 'app/Policies'],
        'job' => ['command' => 'make:job', 'root' => 'app/Jobs'],
        'command' => ['command' => 'make:command', 'root' => 'app/Console/Commands'],
        'event' => ['command' => 'make:event', 'root' => 'app/Events'],
        'listener' => ['command' => 'make:listener', 'root' => 'app/Listeners'],
        'notification' => ['command' => 'make:notification', 'root' => 'app/Notifications'],
        'mail' => ['command' => 'make:mail', 'root' => 'app/Mail'],
        'component' => ['command' => 'make:component', 'root' => 'app/View/Components'],
        'view' => ['command' => 'make:view', 'root' => 'resources/views'],
    ];

    public function __construct(private Kernel $artisan) {}

    /**
     * Generate a module file at its configured target using Laravel's own command
     * whenever one exists. Returns the generation method used.
     *
     * @param array<string, mixed> $config
     */
    public function generate(string $relativePath, string $type, array $config): string
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');
        $target = base_path($relativePath);
        $this->ensureParentDirectory(dirname($target));

        if ($this->generateWithArtisan($relativePath, $type, $config) && is_file($target)) {
            return 'artisan';
        }

        $content = $this->fallbackContent($relativePath, $type, $config);
        if (file_put_contents($target, $content, LOCK_EX) === false) {
            throw new \RuntimeException('Unable to create the module file.');
        }

        return 'appyhp';
    }

    /**
     * @param array<string, mixed> $config
     */
    private function generateWithArtisan(string $relativePath, string $type, array $config): bool
    {
        if (in_array($type, ['table', 'migration'], true)) {
            return $this->generateMigration($relativePath, $type, $config);
        }

        if ($type === 'inertia-middleware') {
            return $relativePath === 'app/Http/Middleware/HandleInertiaRequests.php'
                && $this->call('inertia:middleware');
        }

        if (in_array($type, ['auth', 'queue', 'cache', 'storage'], true)) {
            $name = $type === 'storage' ? 'filesystems' : $type;

            return $relativePath === "config/{$name}.php"
                && $this->call('config:publish', ['name' => $name]);
        }

        $generator = self::ARTISAN_GENERATORS[$type] ?? null;
        if ($generator === null) {
            return false;
        }

        $name = $this->nameWithinRoot($relativePath, $generator['root'], $type === 'view');
        if ($name === null) {
            return false;
        }

        $arguments = ['name' => $name];
        if ($type === 'controller') {
            $actions = array_values(array_filter(array_map('trim', preg_split('/[,\r\n]+/', (string) ($config['actions'] ?? '')) ?: [])));
            if (array_intersect(['create', 'edit'], $actions) !== []) {
                $arguments['--resource'] = true;
            } elseif (array_intersect(['index', 'store', 'show', 'update', 'destroy'], $actions) !== []) {
                $arguments['--api'] = true;
            } elseif ($actions === ['__invoke']) {
                $arguments['--invokable'] = true;
            }
        } elseif (in_array($type, ['factory', 'policy'], true) && ! empty($config['model'])) {
            $arguments['--model'] = (string) $config['model'];
        } elseif ($type === 'listener') {
            if (! empty($config['listensTo'])) {
                $arguments['--event'] = (string) $config['listensTo'];
            }
            if (($config['queued'] ?? 'no') === 'yes') {
                $arguments['--queued'] = true;
            }
        }

        return $this->call($generator['command'], $arguments);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function generateMigration(string $relativePath, string $type, array $config): bool
    {
        if (! str_starts_with($relativePath, 'database/migrations/') || ! str_ends_with($relativePath, '.php')) {
            return false;
        }

        $name = $type === 'table'
            ? 'create_' . ($config['name'] ?? 'records') . '_table'
            : ($config['name'] ?? 'update_records_table');
        $name = Str::snake((string) $name);
        $before = glob(database_path('migrations/*_' . $name . '.php')) ?: [];
        $arguments = ['name' => $name, '--path' => 'database/migrations'];
        $operation = (string) ($config['operation'] ?? ($type === 'table' ? 'create' : 'alter'));
        $table = (string) ($config['table'] ?? $config['name'] ?? 'records');

        if ($operation === 'create' || $type === 'table') {
            $arguments['--create'] = $table;
        } else {
            $arguments['--table'] = $table;
        }

        if (! $this->call('make:migration', $arguments)) {
            return false;
        }

        if (is_file(base_path($relativePath))) {
            return true;
        }

        $after = glob(database_path('migrations/*_' . $name . '.php')) ?: [];
        $created = array_values(array_diff($after, $before));
        if (count($created) !== 1 || ! rename($created[0], base_path($relativePath))) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function call(string $command, array $arguments = []): bool
    {
        try {
            return $this->artisan->call($command, $arguments) === 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function nameWithinRoot(string $path, string $root, bool $view): ?string
    {
        $prefix = rtrim($root, '/') . '/';
        if (! str_starts_with($path, $prefix)) {
            return null;
        }

        $name = substr($path, strlen($prefix));
        $suffix = $view ? '.blade.php' : '.php';
        if (! str_ends_with($name, $suffix)) {
            return null;
        }

        $name = substr($name, 0, -strlen($suffix));

        return $view ? str_replace('/', '.', $name) : str_replace('/', '\\', $name);
    }

    private function ensureParentDirectory(string $directory): void
    {
        $base = rtrim(str_replace('\\', '/', base_path()), '/');
        $directory = rtrim(str_replace('\\', '/', $directory), '/');
        if ($directory !== $base && ! str_starts_with($directory, $base . '/')) {
            throw new \RuntimeException('The module folder must be inside the project.');
        }

        $current = $base;
        $relative = trim(substr($directory, strlen($base)), '/');
        foreach (array_filter(explode('/', $relative)) as $part) {
            $current .= '/' . $part;
            if (is_link($current)) {
                throw ValidationException::withMessages(['path' => 'Module files cannot target symbolic links.']);
            }
            if (file_exists($current) && ! is_dir($current)) {
                throw new \RuntimeException('A module folder path is occupied by a file.');
            }
            if (! file_exists($current) && ! mkdir($current, 0755)) {
                throw new \RuntimeException('Unable to create the module folder.');
            }
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function fallbackContent(string $path, string $type, array $config): string
    {
        if ($type === 'route') {
            $facade = match ($config['routeType'] ?? 'web') {
                'console' => 'Artisan',
                'channels' => 'Broadcast',
                default => 'Route',
            };

            return "<?php\n\nuse Illuminate\\Support\\Facades\\{$facade};\n\n";
        }

        if (in_array($type, ['auth', 'queue', 'cache', 'storage'], true)) {
            return "<?php\n\nreturn [\n    //\n];\n";
        }

        if ($type === 'view') {
            return "<div>\n    {{-- --}}\n</div>\n";
        }

        if ($type === 'inertia-page') {
            $framework = (string) ($config['framework'] ?? 'vue');
            if ($framework === 'inherit') {
                $framework = (string) ($config['frontend'] ?? 'vue');
            }
            if ($framework === 'blade') {
                $framework = 'vue';
            }
            if ($framework === 'react') {
                return "export default function Page() {\n    return <div />;\n}\n";
            }
            if ($framework === 'svelte') {
                return "<main></main>\n";
            }

            return "<script setup>\n</script>\n\n<template>\n    <main></main>\n</template>\n";
        }

        if ($type === 'inertia-middleware') {
            $class = pathinfo($path, PATHINFO_FILENAME);

            return "<?php\n\nnamespace App\\Http\\Middleware;\n\nuse Illuminate\\Http\\Request;\nuse Inertia\\Middleware;\n\nclass {$class} extends Middleware\n{\n    protected \$rootView = '" . addslashes((string) ($config['rootView'] ?? 'app')) . "';\n\n    public function share(Request \$request): array\n    {\n        return [\n            ...parent::share(\$request),\n        ];\n    }\n}\n";
        }

        if (in_array($type, ['table', 'migration'], true)) {
            $table = addslashes((string) ($config['table'] ?? $config['name'] ?? 'records'));
            $method = (($config['operation'] ?? 'create') === 'create' || $type === 'table') ? 'create' : 'table';

            return "<?php\n\nuse Illuminate\\Database\\Migrations\\Migration;\nuse Illuminate\\Database\\Schema\\Blueprint;\nuse Illuminate\\Support\\Facades\\Schema;\n\nreturn new class extends Migration\n{\n    public function up(): void\n    {\n        Schema::{$method}('{$table}', function (Blueprint \$table) {\n            //\n        });\n    }\n\n    public function down(): void\n    {\n        //\n    }\n};\n";
        }

        $class = pathinfo($path, PATHINFO_FILENAME);
        $namespace = 'App';
        if (str_starts_with($path, 'app/')) {
            $directory = trim(str_replace('/', '\\', dirname(substr($path, 4))), '.\\');
            $namespace .= $directory === '' ? '' : '\\' . $directory;
        }

        return "<?php\n\nnamespace {$namespace};\n\nclass {$class}\n{\n    //\n}\n";
    }
}
