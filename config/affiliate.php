<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Affiliate commission rates (NGN)
    |--------------------------------------------------------------------------
    |
    | Applied when a referred school completes a successful Paystack charge.
    | One-time: per newly billable student in that charge metadata.
    | Monthly component: per billable student snapshot in that charge metadata.
    |
    */

    'one_time_per_new_billable_student' => env('AFFILIATE_ONE_TIME_NGN', 60),

    'monthly_per_billable_student' => env('AFFILIATE_MONTHLY_NGN', 20),

    'referral_code_length' => 8,
];
