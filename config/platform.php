<?php

return [
    'digest' => [
        'enabled' => env('PLATFORM_DIGEST_ENABLED', true),
        'frequency' => env('PLATFORM_DIGEST_FREQUENCY', 'daily'), // daily|weekly
        'time' => env('PLATFORM_DIGEST_TIME', '08:00'),
        'weekly_day' => env('PLATFORM_DIGEST_WEEKLY_DAY', 'monday'),
        'recipients' => env('PLATFORM_DIGEST_RECIPIENTS', ''), // comma-separated emails
        'include_platform_admins' => env('PLATFORM_DIGEST_INCLUDE_PLATFORM_ADMINS', true),
    ],
    'webhooks' => [
        'auto_disable_after_failures' => (int) env('PLATFORM_WEBHOOK_DISABLE_AFTER_FAILURES', 10),
    ],
];
