<?php

namespace Tests\Unit;

use App\Services\Ai\AiRouter;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\Ai\Providers\OpenAiClient;
use App\Services\Ai\Providers\OssAiClient;
use Tests\TestCase;

class AiRouterFallbackTest extends TestCase
{
    public function test_it_falls_back_to_secondary_provider(): void
    {
        $openAi = new class extends OpenAiClient {
            public function generate(array $messages, array $options = []): array
            {
                throw new AiProviderException('rate limit', 'openai', 429, 'rate_limit', true);
            }
        };

        $oss = new class extends OssAiClient {
            public function generate(array $messages, array $options = []): array
            {
                return [
                    'provider' => 'oss',
                    'model' => 'test-model',
                    'content' => 'ok',
                    'tokens_input' => 1,
                    'tokens_output' => 1,
                ];
            }
        };

        $router = new AiRouter($openAi, $oss);
        $result = $router->generate('announcement_draft', [['role' => 'user', 'content' => 'x']], [
            'provider' => 'openai',
            'routing' => ['providers' => ['oss']],
        ]);

        $this->assertSame('oss', $result['provider']);
        $this->assertSame('openai', $result['fallback_from']);
    }
}
