<?php

namespace Alliswell\Appyhp\Http\Controllers;

use Alliswell\Appyhp\Support\AiGateway;
use Alliswell\Appyhp\Support\AiSettings;
use Alliswell\Appyhp\Support\GenerationResult;
use Alliswell\Appyhp\Support\ProjectFiles;
use Alliswell\Appyhp\Support\WorkflowContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiController
{
    public function settings(AiSettings $settings, ProjectFiles $files): JsonResponse
    {
        return response()->json(['settings' => $settings->publicSettings(), 'project' => $files->project()]);
    }

    public function updateSettings(Request $request, AiSettings $settings): JsonResponse
    {
        return response()->json(['settings' => $settings->save($settings->resolve($request->all()))]);
    }

    public function test(Request $request, AiSettings $settings, AiGateway $gateway): JsonResponse
    {
        $resolved = $settings->resolve($request->all());
        try {
            $gateway->stream($resolved, 'Reply with the single word OK.', 'Connection test.', fn () => true, 1024);
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (\Throwable $exception) {
            return response()->json(['message' => 'The connection test failed. Check the provider and try again.'], 502);
        }

        return response()->json(['connected' => true, 'message' => 'Connected to ' . $resolved['model'] . '.']);
    }

    public function generate(Request $request, AiSettings $settings, WorkflowContext $builder, AiGateway $gateway): StreamedResponse
    {
        abort_if(strlen($request->getContent()) > 1048576, 413, 'The workflow is too large for one generation.');
        $input = $request->validate([
            'moduleId' => ['required', 'string', 'max:160'],
            'workflow' => ['required', 'array'],
            'workflow.id' => ['required', 'string', 'max:160'],
            'workflow.name' => ['required', 'string', 'max:300'],
            'workflow.description' => ['nullable', 'string', 'max:10000'],
            'workflow.meta' => ['sometimes', 'array'],
            'workflow.meta.frontend' => ['sometimes', Rule::in(['blade', 'vue', 'react', 'svelte'])],
            'workflow.modules' => ['required', 'array', 'max:150'],
            'workflow.modules.*.id' => ['required', 'string', 'distinct', 'max:160'],
            'workflow.modules.*.type' => ['required', 'string', 'max:80'],
            'workflow.modules.*.label' => ['required', 'string', 'max:300'],
            'workflow.modules.*.description' => ['nullable', 'string', 'max:10000'],
            'workflow.modules.*.config' => ['present', 'array'],
            'workflow.modules.*.config.*' => ['nullable'],
            'workflow.modules.*.config.prompt' => ['sometimes', 'nullable', 'string', 'max:16000'],
            'workflow.modules.*.config.folder' => ['sometimes', 'string', 'max:500'],
            'workflow.modules.*.config.filename' => ['sometimes', 'string', 'max:200'],
            'workflow.modules.*.config.ai' => ['sometimes', 'array'],
            'workflow.modules.*.config.ai.code' => ['sometimes', 'string', 'max:' . ProjectFiles::MAX_BYTES],
            'workflow.edges' => ['present', 'array', 'max:500'],
            'workflow.edges.*.from' => ['required', 'string', 'max:160'],
            'workflow.edges.*.to' => ['required', 'string', 'max:160'],
            'workflow.edges.*.label' => ['nullable', 'string', 'max:300'],
        ]);
        $selected = collect($input['workflow']['modules'])->firstWhere('id', $input['moduleId']);
        abort_unless($selected && ! empty($selected['config']['folder']) && ! empty($selected['config']['filename']), 422, 'Choose a target folder and filename.');
        abort_if(trim($selected['config']['prompt'] ?? '') === '', 422, 'Describe what this module should do.');
        $resolved = $settings->read();
        abort_unless($settings->publicSettings($resolved)['configured'], 422, 'Configure an AI provider and model in Settings first.');
        $context = $builder->build($input['workflow'], $input['moduleId']);

        return response()->stream(function () use ($gateway, $resolved, $builder, $context): void {
            set_time_limit((int) config('appyhp.ai.timeout', 120) + 15);
            $emit = function (string $event, array $payload): void {
                echo 'event: ' . $event . "\n" . 'data: ' . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };
            $emit('start', ['moduleId' => $context['selected_module_id']]);
            try {
                $output = $gateway->stream($resolved, $builder->instructions(), json_encode($context, JSON_THROW_ON_ERROR), function (string $text) use ($emit): bool {
                    if (connection_aborted()) {
                        return false;
                    }
                    $emit('delta', ['text' => $text]);

                    return ! connection_aborted();
                });
                $emit('done', GenerationResult::parse($output, $context));
            } catch (\Throwable $exception) {
                if (! connection_aborted()) {
                    $message = $exception instanceof \RuntimeException ? $exception->getMessage() : 'Generation failed. The previous draft has been kept. Try again.';
                    $emit('error', ['message' => $message]);
                }
            }
        }, 200, ['Content-Type' => 'text/event-stream', 'Cache-Control' => 'no-cache, no-store', 'X-Accel-Buffering' => 'no']);
    }

    public function writeFile(Request $request, ProjectFiles $files): JsonResponse
    {
        $input = $request->validate([
            'path' => ['required', 'string', 'max:700'],
            'content' => ['required', 'string', 'max:' . ProjectFiles::MAX_BYTES],
            'expectedHash' => ['present', 'nullable', 'string', 'size:64'],
        ]);

        return response()->json($files->write($input['path'], $input['content'], $input['expectedHash']));
    }
}
