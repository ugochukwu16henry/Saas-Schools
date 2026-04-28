<?php

return [
    'enabled' => (bool) env('AI_ENABLED', true),
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'openai'),
    'fallback_provider' => env('AI_FALLBACK_PROVIDER', 'oss'),
    'timeout_seconds' => (int) env('AI_TIMEOUT_SECONDS', 20),
    'max_retries' => (int) env('AI_MAX_RETRIES', 1),
    'guard' => [
        'max_output_chars' => (int) env('AI_GUARD_MAX_OUTPUT_CHARS', 4000),
    ],
    'budgets' => [
        'max_tokens_per_request' => (int) env('AI_MAX_TOKENS_PER_REQUEST', 2000),
    ],

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
            'response_format' => env('AI_ANNOUNCEMENT_RESPONSE_FORMAT', 'json_object'),
            'routing' => [
                'providers' => array_values(array_filter(explode(',', (string) env('AI_ANNOUNCEMENT_PROVIDER_CHAIN', '')))),
            ],
        ],
        'ops_summary' => [
            'provider' => env('AI_OPS_SUMMARY_PROVIDER', env('AI_DEFAULT_PROVIDER', 'openai')),
            'model' => env('AI_OPS_SUMMARY_MODEL', null),
            'temperature' => (float) env('AI_OPS_SUMMARY_TEMPERATURE', 0.2),
            'max_tokens' => (int) env('AI_OPS_SUMMARY_MAX_TOKENS', 500),
            'response_format' => env('AI_OPS_SUMMARY_RESPONSE_FORMAT', 'json_object'),
            'routing' => [
                'providers' => array_values(array_filter(explode(',', (string) env('AI_OPS_SUMMARY_PROVIDER_CHAIN', '')))),
            ],
        ],
    ],
];
