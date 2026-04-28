<?php

namespace App\Services\Ai;

use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\Ai\Providers\OpenAiClient;
use App\Services\Ai\Providers\OssAiClient;
use Illuminate\Support\Facades\Log;
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
        $providerChain = array_values(array_unique(array_filter([
            $provider,
            ...((array) data_get($featureConfig, 'routing.providers', [])),
            $fallback,
        ])));
        $options = [
            'model' => $featureConfig['model'] ?? null,
            'temperature' => $featureConfig['temperature'] ?? 0.4,
            'max_tokens' => $featureConfig['max_tokens'] ?? 500,
            'response_format' => data_get($featureConfig, 'response_format'),
        ];

        $lastException = null;
        foreach ($providerChain as $index => $candidateProvider) {
            try {
                $result = $this->clientFor($candidateProvider)->generate($messages, $options);
                $result['feature'] = $feature;
                if ($index > 0) {
                    $result['fallback_from'] = $providerChain[0];
                }
                return $result;
            } catch (\Throwable $e) {
                $lastException = $e;
                $retryable = !($e instanceof AiProviderException) || $e->isRetryable();
                Log::warning('ai.provider_failed', [
                    'feature' => $feature,
                    'provider' => $candidateProvider,
                    'retryable' => $retryable,
                    'error' => $e->getMessage(),
                ]);
                if (! $retryable) {
                    break;
                }
            }
        }

        throw $lastException ?: new RuntimeException('No AI provider available for feature: '.$feature);
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
