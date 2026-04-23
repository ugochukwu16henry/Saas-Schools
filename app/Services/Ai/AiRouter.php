<?php

namespace App\Services\Ai;

use App\Services\Ai\Providers\OpenAiClient;
use App\Services\Ai\Providers\OssAiClient;
use RuntimeException;

class AiRouter
{
    private OpenAiClient $openAiClient;
    private OssAiClient $ossAiClient;

    public function __construct(OpenAiClient $openAiClient, OssAiClient $ossAiClient)
    {
        $this->openAiClient = $openAiClient;
        $this->ossAiClient = $ossAiClient;
    }

    /**
     * @param  array<int, array<string, string>>  $messages
     * @param  array<string, mixed>  $featureConfig
     * @return array<string, mixed>
     */
    public function generate(string $feature, array $messages, array $featureConfig = []): array
    {
        $provider = (string) ($featureConfig['provider'] ?? config('ai.default_provider', 'openai'));
        $fallback = (string) config('ai.fallback_provider', 'oss');
        $options = [
            'model' => $featureConfig['model'] ?? null,
            'temperature' => $featureConfig['temperature'] ?? 0.4,
            'max_tokens' => $featureConfig['max_tokens'] ?? 500,
        ];

        try {
            return $this->clientFor($provider)->generate($messages, $options);
        } catch (\Throwable $e) {
            if ($fallback === $provider) {
                throw $e;
            }

            $result = $this->clientFor($fallback)->generate($messages, $options);
            $result['fallback_from'] = $provider;
            $result['feature'] = $feature;

            return $result;
        }
    }

    private function clientFor(string $provider)
    {
        if ($provider === 'openai') {
            return $this->openAiClient;
        }
        if ($provider === 'oss') {
            return $this->ossAiClient;
        }

        throw new RuntimeException('Unsupported AI provider: '.$provider);
    }
}
