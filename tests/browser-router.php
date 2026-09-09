<?php

// Isolated browser fixture. All upstream AI requests are intercepted by Laravel's HTTP fake.
require __DIR__ . '/bootstrap.php';

use Alliswell\Appyhp\Tests\FixtureApplication;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

$root = getenv('APPYHP_BROWSER_PROJECT');
if (! $root || ! str_starts_with($root, sys_get_temp_dir() . '/appyhp-browser-')) {
    throw new RuntimeException('Set APPYHP_BROWSER_PROJECT to an isolated /tmp/appyhp-browser-* directory.');
}
$app = FixtureApplication::create($root);
$app['env'] = 'local';
Http::preventStrayRequests();
Http::fake(function ($request) use ($root) {
    try {
    $provider = str_ends_with($request->url(), '/responses') ? 'openai' : (str_ends_with($request->url(), '/messages') ? 'anthropic' : 'compatible');
    $input = $request['input'] ?? $request['messages'][count($request['messages']) - 1]['content'];
    $context = json_decode($input, true);
    if (! is_array($context)) return Http::response(FixtureApplication::sse('OK', $provider), 200, ['Content-Type' => 'text/event-stream']);
    file_put_contents($root . '/last-ai-context.json', json_encode($context));
    $module = collect($context['workflow']['modules'])->firstWhere('id', $context['selected_module_id']);
    $prompt = $module['config']['prompt'];
    if (str_contains($prompt, 'simulate failure')) return Http::response(['error' => 'Test quota reached'], 429);
    $language = 'php';
    $metadata = ['summary' => $prompt, 'config' => new stdClass, 'suggestions' => [], 'table' => null];
    $code = "<?php\n// " . str_replace(["\r", "\n"], ' ', $prompt) . "\nreturn [];\n";
    if ($module['type'] === 'table') {
        $name = $module['config']['name'];
        $columns = [
            ['name' => 'id', 'type' => 'bigint', 'nullable' => false, 'key' => 'primary', 'default' => null],
            ['name' => 'name', 'type' => 'string', 'nullable' => false, 'key' => '', 'default' => null],
            ['name' => 'email', 'type' => 'string', 'nullable' => false, 'key' => 'unique', 'default' => null],
        ];
        $status = str_contains(strtolower($prompt), 'status');
        if ($status) $columns[] = ['name' => 'status', 'type' => 'string', 'nullable' => false, 'key' => '', 'default' => 'active'];
        $code = "<?php\n\nuse Illuminate\\Database\\Migrations\\Migration;\nuse Illuminate\\Database\\Schema\\Blueprint;\nuse Illuminate\\Support\\Facades\\Schema;\n\nreturn new class extends Migration\n{\n    public function up(): void\n    {\n        Schema::create('$name', function (Blueprint \$table): void {\n            \$table->id();\n            \$table->string('name');\n            \$table->string('email')->unique();\n";
        if ($status) $code .= "            \$table->string('status')->default('active');\n";
        $code .= "        });\n    }\n\n    public function down(): void\n    {\n        Schema::dropIfExists('$name');\n    }\n};\n";
        $metadata['table'] = ['name' => $name, 'columns' => $columns];
        $metadata['config'] = ['name' => $name, 'columns' => "id:bigint:primary\nname:string\nemail:string:unique" . ($status ? "\nstatus:string" : '')];
        $model = collect($context['workflow']['modules'])->firstWhere('type', 'model');
        if ($model) $metadata['suggestions'][] = ['moduleId' => $model['id'], 'message' => 'Keep the model fillable fields aligned with ' . $name . '.'];
    }
    if ($module['type'] === 'inertia-page') {
        $framework = $module['config']['framework'] === 'inherit' ? $context['frontend'] : $module['config']['framework'];
        $language = $framework === 'react' ? ($module['config']['language'] === 'typescript' ? 'tsx' : 'jsx') : $framework;
        $code = match ($framework) {
            'react' => "import { Head } from '@inertiajs/react';\nexport default function Index() { return <><Head title=\"Users\" /><h1>Users</h1></>; }\n",
            'svelte' => "<script>\nimport { Head } from '@inertiajs/svelte';\n</script>\n<Head title=\"Users\" /><h1>Users</h1>\n",
            default => "<script setup>\nimport { Head } from '@inertiajs/vue3';\n</script>\n<template><Head title=\"Users\" /><h1>Users</h1></template>\n",
        };
    }
    $output = '```' . $language . "\n" . rtrim($code) . "\n```\n```appyhp\n" . json_encode($metadata) . "\n```";
    $source = Utils::streamFor(FixtureApplication::sse($output, $provider));
    $slow = FnStream::decorate($source, ['read' => function ($length) use ($source, $prompt) {
        usleep(str_contains($prompt, 'slow') ? 70000 : 10000);

        return $source->read(128);
    }]);

    return Http::response($slow, 200, ['Content-Type' => 'text/event-stream']);
    } catch (Throwable $exception) {
        error_log('AI fixture error: ' . $exception->getMessage());
        throw $exception;
    }
});
$app->handleRequest(Request::capture());
