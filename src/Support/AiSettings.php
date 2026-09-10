<?php

namespace Alliswell\Appyhp\Support;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AiSettings
{
    public function __construct(private RuntimeStorage $runtime) {}

    public function read(): array
    {
        $settings = array_intersect_key(config('appyhp.ai', []), array_flip([
            'provider', 'base_url', 'model', 'api_key', 'live', 'debounce_ms',
        ]));
        $path = $this->runtime->path('ai-settings.json');

        if (is_file($path)) {
            $stored = json_decode(file_get_contents($path), true);
            abort_unless(is_array($stored), 500, 'Unable to read AI settings.');
            $encryptedKey = $stored['api_key'] ?? '';
            $stored['api_key'] = $encryptedKey === '' ? '' : Crypt::decryptString($encryptedKey);
            $settings = array_replace($settings, $stored);
        }

        return $settings;
    }

    public function publicSettings(?array $settings = null): array
    {
        $settings ??= $this->read();
        $settings['has_key'] = ($settings['api_key'] ?? '') !== '';
        $settings['configured'] = ($settings['model'] ?? '') !== ''
            && ($settings['has_key'] || $settings['provider'] === 'compatible');
        unset($settings['api_key']);

        return $settings;
    }

    public function resolve(array $input): array
    {
        $values = Validator::make($input, [
            'provider' => ['required', Rule::in(['openai', 'compatible', 'anthropic'])],
            'base_url' => ['required', 'string', 'max:2048'],
            'model' => ['required', 'string', 'max:200'],
            'api_key' => ['nullable', 'string', 'max:4096', 'not_regex:/[\r\n]/'],
            'clear_key' => ['sometimes', 'boolean'],
            'live' => ['required', 'boolean'],
            'debounce_ms' => ['required', 'integer', 'min:600', 'max:5000'],
        ])->validate();

        $values['base_url'] = rtrim(trim($values['base_url']), '/');
        $values['model'] = trim($values['model']);
        $url = parse_url($values['base_url']);
        $local = in_array(strtolower($url['host'] ?? ''), ['localhost', '127.0.0.1', '[::1]'], true);

        if (! filter_var($values['base_url'], FILTER_VALIDATE_URL) || ! isset($url['host'])
            || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])
            || (($url['scheme'] ?? '') !== 'https' && !(($url['scheme'] ?? '') === 'http' && $local))) {
            throw ValidationException::withMessages(['base_url' => 'Use an HTTPS API base URL, or HTTP for a local provider.']);
        }

        $current = $this->read();
        $sameProvider = $values['provider'] === $current['provider'] && $values['base_url'] === $current['base_url'];
        $key = trim($values['api_key'] ?? '');
        $values['api_key'] = ($values['clear_key'] ?? false) ? '' : ($key !== '' ? $key : ($sameProvider ? $current['api_key'] : ''));
        unset($values['clear_key']);

        if ($values['model'] === '') {
            throw ValidationException::withMessages(['model' => 'Enter a model ID.']);
        }

        return $values;
    }

    public function save(array $settings): array
    {
        $directory = $this->runtime->ensure();

        $stored = $settings;
        $stored['api_key'] = $settings['api_key'] === '' ? '' : Crypt::encryptString($settings['api_key']);
        $temporary = tempnam($directory, 'ai-');
        abort_if($temporary === false, 500, 'Unable to save AI settings.');

        try {
            chmod($temporary, 0600);
            $encoded = json_encode($stored, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            abort_if(file_put_contents($temporary, $encoded . PHP_EOL, LOCK_EX) === false, 500, 'Unable to save AI settings.');
            abort_unless(rename($temporary, $directory . '/ai-settings.json'), 500, 'Unable to save AI settings.');
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }

        return $this->publicSettings($settings);
    }
}
