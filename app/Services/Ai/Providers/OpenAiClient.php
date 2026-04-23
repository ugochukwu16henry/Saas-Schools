<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Contracts\AiClientInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiClient implements AiClientInterface
{
    public function generate(array $messages, array $options = []): array
    {
        $provider = config('ai.providers.openai');
        $baseUrl = rtrim((string) data_get($provider, 'base_url', 'https://api.openai.com/v1'), '/');
        $apiKey = (string) data_get($provider, 'api_key');
        $model = (string) ($options['model'] ?? data_get($provider, 'model', 'gpt-4o-mini'));

        if (! $apiKey) {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $response = Http::timeout((int) config('ai.timeout_seconds', 20))
            ->retry((int) config('ai.max_retries', 1), 250)
            ->withToken($apiKey)
            ->acceptJson()
            ->post($baseUrl.'/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => (float) ($options['temperature'] ?? 0.4),
                'max_tokens' => (int) ($options['max_tokens'] ?? 500),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI request failed: '.$response->status());
        }

        $data = $response->json();
        $content = (string) data_get($data, 'choices.0.message.content', '');

        return [
            'provider' => 'openai',
            'model' => $model,
            'content' => $content,
            'tokens_input' => (int) data_get($data, 'usage.prompt_tokens', 0),
            'tokens_output' => (int) data_get($data, 'usage.completion_tokens', 0),
            'raw' => $data,
        ];
    }
}
