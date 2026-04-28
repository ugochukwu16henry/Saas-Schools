<?php

return [
    'notifications' => [
        'queue' => env('TRANSFER_NOTIFICATIONS_QUEUE', 'mail-notifications'),
        'tries' => (int) env('TRANSFER_NOTIFICATIONS_TRIES', 5),
        'backoff_seconds' => [60, 300, 900],
    ],

    'policies' => [
        'max_pending_per_student' => (int) env('TRANSFER_MAX_PENDING_PER_STUDENT', 1),
        'require_transfer_note' => (bool) env('TRANSFER_REQUIRE_NOTE', false),
        'require_acceptance_checklist' => (bool) env('TRANSFER_REQUIRE_ACCEPTANCE_CHECKLIST', true),
        'require_snapshot_fields_before_accept' => (bool) env('TRANSFER_REQUIRE_SNAPSHOT_FIELDS', true),
        'require_destination_mapping' => (bool) env('TRANSFER_REQUIRE_DESTINATION_MAPPING', true),
    ],
];
