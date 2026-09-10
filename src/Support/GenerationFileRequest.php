<?php

namespace Alliswell\Appyhp\Support;

use Illuminate\Support\Facades\Validator;
use RuntimeException;

class GenerationFileRequest
{
    /**
     * @return array{path: string, reason: string}|null
     */
    public static function parse(string $output): ?array
    {
        if (! str_contains($output, '```appyhp-file-request')) {
            return null;
        }

        if (! preg_match('/\A\s*```appyhp-file-request\r?\n(.*?)\r?\n```\s*\z/sD', $output, $parts)) {
            throw new RuntimeException('The provider returned an invalid file request. Generate again or choose a model that follows the requested format.');
        }

        $request = json_decode($parts[1], true);
        $validator = Validator::make(is_array($request) ? $request : [], [
            'path' => ['required', 'string', 'max:700'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        if ($validator->fails() || array_diff(array_keys($request), ['path', 'reason']) !== []) {
            throw new RuntimeException('The provider returned an invalid file request. Generate again or choose a model that follows the requested format.');
        }

        return [
            'path' => trim($request['path']),
            'reason' => trim($request['reason']),
        ];
    }
}
