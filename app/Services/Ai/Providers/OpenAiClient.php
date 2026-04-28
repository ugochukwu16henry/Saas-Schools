<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Contracts\AiClientInterface;
use App\Services\Ai\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OpenAiClient implements AiClientInterface
{
    public function generate(array $messages, array $options = []): array
    {
        $provider = config('ai.providers.openai');
        $baseUrl = rtrim((string) data_get($provider, 'base_url', 'https://api.openai.com/v1'), '/');
        $apiKey = (string) data_get($provider, 'api_key');
        $model = (string) ($options['model'] ?? data_get($provider, 'model', 'gpt-4o-mini'));

        if (! $apiKey) {
            throw new AiProviderException('OPENAI_API_KEY is not configured.', 'openai', 0, 'config_error', false);
        }

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => (float) ($options['temperature'] ?? 0.4),
            'max_tokens' => (int) ($options['max_tokens'] ?? 500),
        ];

        if (($options['response_format'] ?? '') === 'json_object') {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = Http::timeout((int) config('ai.timeout_seconds', 20))
            ->retry((int) config('ai.max_retries', 1), 250)
            ->withToken($apiKey)
            ->acceptJson()
            ->post($baseUrl.'/chat/completions', $payload);

        if (! $response->successful()) {
            $status = $response->status();
            $errorType = (string) data_get($response->json(), 'error.type', 'provider_http_error');
            $message = (string) data_get($response->json(), 'error.message', 'OpenAI request failed');
            $retryable = $status === 429 || ($status >= 500 && $status <= 599);
            throw new AiProviderException('OpenAI request failed: '.$message, 'openai', $status, $errorType, $retryable);
        }

        $data = $response->json();
        $content = (string) data_get($data, 'choices.0.message.content', '');

        return [
            'provider' => 'openai',
            'model' => $model,
            'content' => $content,
            'tokens_input' => (int) data_get($data, 'usage.prompt_tokens', 0),
            'tokens_output' => (int) data_get($data, 'usage.completion_tokens', 0),
            'trace_id' => (string) Str::uuid(),
            'raw' => $data,
        ];
    }
}
