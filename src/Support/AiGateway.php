<?php

namespace Alliswell\Appyhp\Support;

use Illuminate\Support\Facades\Http;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class AiGateway
{
    public function stream(array $settings, string $instructions, string $input, callable $onDelta, ?int $limit = null): string
    {
        if ($settings['model'] === '' || ($settings['api_key'] === '' && $settings['provider'] !== 'compatible')) {
            throw new RuntimeException('Enter a model and API key in AI settings first.');
        }
        $request = Http::accept('text/event-stream')->asJson()
            ->connectTimeout(10)->timeout((int) config('appyhp.ai.timeout', 120))
            ->withOptions(['stream' => true, 'read_timeout' => 45, 'allow_redirects' => false]);
        $tokens = $limit ?? (int) config('appyhp.ai.max_output_tokens', 8192);
        $payload = ['model' => $settings['model'], 'stream' => true];

        if ($settings['provider'] === 'anthropic') {
            $request = $request->withHeaders(['x-api-key' => $settings['api_key'], 'anthropic-version' => '2023-06-01']);
            $endpoint = '/messages';
            $payload += ['system' => $instructions, 'max_tokens' => $tokens, 'messages' => [['role' => 'user', 'content' => $input]]];
        } elseif ($settings['provider'] === 'openai') {
            $request = $request->withToken($settings['api_key']);
            $endpoint = '/responses';
            $payload += ['instructions' => $instructions, 'input' => $input, 'max_output_tokens' => $tokens, 'store' => false];
        } else {
            if ($settings['api_key'] !== '') {
                $request = $request->withToken($settings['api_key']);
            }
            $endpoint = '/chat/completions';
            $payload += ['max_tokens' => $tokens, 'messages' => [['role' => 'system', 'content' => $instructions], ['role' => 'user', 'content' => $input]]];
        }

        try {
            $response = $request->post(rtrim($settings['base_url'], '/') . $endpoint, $payload);
        } catch (\Exception $exception) {
            throw new RuntimeException('Unable to connect to the AI provider. Check its API address and network connection.');
        }
        $body = $response->toPsrResponse()->getBody();
        $output = '';
        $complete = false;

        try {
            if (! $response->successful()) {
                throw new RuntimeException(match ($response->status()) {
                    401, 403 => 'The provider rejected the API key or model access. Check AI settings.',
                    404 => 'The provider endpoint or model was not found. Check the API base URL, protocol and model.',
                    429 => 'The provider rate limit or quota was reached. Wait before generating again.',
                    default => 'The AI provider rejected the request (HTTP ' . $response->status() . '). Check its protocol, model and availability.',
                });
            }

            foreach ($this->events($body) as $data) {
                if ($data === '[DONE]') {
                    $complete = true;
                    break;
                }
                $event = json_decode($data, true);
                if (! is_array($event)) {
                    throw new RuntimeException('The provider returned an invalid stream. Check its API protocol.');
                }
                $type = $event['type'] ?? '';
                if (isset($event['error']) || in_array($type, ['error', 'response.failed', 'response.incomplete'], true)) {
                    throw new RuntimeException('The provider could not complete generation. Check its limits and try again.');
                }
                if ($type === 'response.refusal.delta' || isset($event['choices'][0]['delta']['refusal'])) {
                    throw new RuntimeException('The provider declined this request. Revise the module description.');
                }
                $finish = $event['choices'][0]['finish_reason'] ?? $event['delta']['stop_reason'] ?? null;
                if (in_array($finish, ['length', 'max_tokens', 'content_filter', 'refusal'], true)) {
                    throw new RuntimeException('Generation stopped before the code was complete. Shorten the request or increase the configured output limit.');
                }

                $text = '';
                if ($type === 'response.output_text.delta') {
                    $text = $event['delta'] ?? '';
                } elseif ($type === 'content_block_delta' && ($event['delta']['type'] ?? '') === 'text_delta') {
                    $text = $event['delta']['text'] ?? '';
                } elseif (isset($event['choices'][0]['delta']['content'])) {
                    $text = $event['choices'][0]['delta']['content'];
                }
                if (is_string($text) && $text !== '') {
                    $output .= $text;
                    if (strlen($output) > ProjectFiles::MAX_BYTES) {
                        throw new RuntimeException('The generated output is too large. Narrow the module description.');
                    }
                    if ($onDelta($text) === false) {
                        throw new RuntimeException('Generation cancelled.');
                    }
                }
                if (in_array($type, ['response.completed', 'message_stop'], true)) {
                    $complete = true;
                    break;
                }
            }
        } finally {
            $body->close();
        }
        if (! $complete || trim($output) === '') {
            throw new RuntimeException('The provider stream ended without a complete response. Try generating again.');
        }

        return $output;
    }

    private function events(StreamInterface $body): \Generator
    {
        $buffer = '';
        $data = [];
        while (! $body->eof()) {
            $buffer .= $body->read(1024);
            if (strlen($buffer) > ProjectFiles::MAX_BYTES) {
                throw new RuntimeException('The provider did not return a valid event stream.');
            }
            while (($newline = strpos($buffer, "\n")) !== false) {
                $line = rtrim(substr($buffer, 0, $newline), "\r");
                $buffer = substr($buffer, $newline + 1);
                if ($line === '') {
                    if ($data !== []) {
                        yield implode("\n", $data);
                        $data = [];
                    }
                } elseif (str_starts_with($line, 'data:')) {
                    $data[] = ltrim(substr($line, 5), ' ');
                }
            }
        }
        if (str_starts_with($buffer, 'data:')) {
            $data[] = ltrim(substr($buffer, 5), ' ');
        }
        if ($data !== []) {
            yield implode("\n", $data);
        }
    }
}
