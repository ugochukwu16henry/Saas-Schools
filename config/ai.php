<?php

return [
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'openai'),
    'fallback_provider' => env('AI_FALLBACK_PROVIDER', 'oss'),
    'timeout_seconds' => (int) env('AI_TIMEOUT_SECONDS', 20),
    'max_retries' => (int) env('AI_MAX_RETRIES', 1),

    'providers' => [
        'openai' => [
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        ],
        'oss' => [
            'base_url' => env('OSS_AI_BASE_URL'),
            'api_key' => env('OSS_AI_API_KEY'),
            'model' => env('OSS_AI_MODEL', 'llama-3.1-8b-instruct'),
        ],
    ],

    'features' => [
        'announcement_draft' => [
            'provider' => env('AI_ANNOUNCEMENT_PROVIDER', 'openai'),
            'model' => env('AI_ANNOUNCEMENT_MODEL', null),
            'temperature' => (float) env('AI_ANNOUNCEMENT_TEMPERATURE', 0.4),
            'max_tokens' => (int) env('AI_ANNOUNCEMENT_MAX_TOKENS', 500),
        ],
    ],
];
