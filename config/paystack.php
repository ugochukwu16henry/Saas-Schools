<?php

return [
    'public_key'  => env('PAYSTACK_PUBLIC_KEY', ''),
    'secret_key'  => env('PAYSTACK_SECRET_KEY', ''),
    'payment_url' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
    'payment_failure_threshold' => (int) env('PAYSTACK_PAYMENT_FAILURE_THRESHOLD', 3),
    'payment_failure_grace_days' => (int) env('PAYSTACK_PAYMENT_FAILURE_GRACE_DAYS', 7),
];
