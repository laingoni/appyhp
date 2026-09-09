<?php

namespace Alliswell\Appyhp\Support;

use Illuminate\Support\Facades\Validator;
use RuntimeException;

class GenerationResult
{
    public static function parse(string $output, array $context): array
    {
        $pattern = '/\A\s*```(php|vue|jsx|tsx|svelte|js|ts|css|json)\r?\n(.*?)\r?\n```\s*```appyhp\r?\n(.*?)\r?\n```\s*\z/sD';
        if (! preg_match($pattern, $output, $parts) || trim($parts[2]) === '') {
            throw new RuntimeException('The provider did not return a complete code draft. Generate again or choose a model that follows the requested format.');
        }

        $metadata = json_decode($parts[3], true);
        $validator = Validator::make(is_array($metadata) ? $metadata : [], [
            'summary' => ['required', 'string', 'max:2000'],
            'config' => ['present', 'array'],
            'suggestions' => ['present', 'array', 'max:20'],
            'suggestions.*.moduleId' => ['present', 'nullable', 'string', 'max:160'],
            'suggestions.*.message' => ['required', 'string', 'max:2000'],
            'table' => ['present', 'nullable', 'array'],
            'table.name' => ['required_with:table', 'string', 'max:160'],
            'table.columns' => ['sometimes', 'array', 'max:200'],
            'table.columns.*.name' => ['required', 'string', 'max:160'],
            'table.columns.*.type' => ['required', 'string', 'max:200'],
            'table.columns.*.nullable' => ['required', 'boolean'],
            'table.columns.*.key' => ['present', 'nullable', 'string', 'max:200'],
            'table.columns.*.default' => ['present', 'nullable', 'string', 'max:500'],
        ]);
        if ($validator->fails() || (is_array($metadata['table'] ?? null) && ! array_key_exists('columns', $metadata['table']))) {
            throw new RuntimeException('The provider returned invalid configuration metadata. Generate again; the previous draft has been kept.');
        }

        $modules = $context['workflow']['modules'];
        $module = collect($modules)->firstWhere('id', $context['selected_module_id']);
        if (in_array($module['type'], ['table', 'migration'], true) && $metadata['table'] === null) {
            throw new RuntimeException('The provider omitted the table schema. Generate again to produce code and its matching preview.');
        }
        $config = array_intersect_key($metadata['config'], $module['config']);
        unset($config['ai'], $config['prompt'], $config['folder'], $config['filename'], $config['live']);
        $config = array_filter($config, fn ($value) => is_string($value) || is_numeric($value) || is_bool($value));
        $ids = array_column($modules, 'id');
        $suggestions = array_values(array_filter($metadata['suggestions'], fn ($suggestion) => $suggestion['moduleId'] === null || in_array($suggestion['moduleId'], $ids, true)));

        return [
            'code' => $parts[2] . "\n",
            'language' => $parts[1],
            'summary' => $metadata['summary'],
            'config' => $config,
            'table' => $metadata['table'],
            'suggestions' => $suggestions,
            'path' => $context['target_file']['path'],
            'baseHash' => $context['target_file']['hash'],
            'generatedAt' => now()->toISOString(),
        ];
    }
}
