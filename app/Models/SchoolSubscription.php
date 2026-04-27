<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSubscription extends Model
{
    protected $fillable = [
        'school_id',
        'paystack_subscription_code',
        'paystack_customer_code',
        'status',
        'trial_ends_at',
        'next_payment_date',
        'billed_students',
        'payment_failures_count',
        'last_payment_failed_at',
        'last_payment_failure_reason',
        'last_payment_reference',
        'grace_period_ends_at',
        'trial_warning_7d_sent_at',
        'trial_warning_1d_sent_at',
    ];

    protected $dates = [
        'trial_ends_at',
        'next_payment_date',
        'last_payment_failed_at',
        'grace_period_ends_at',
        'trial_warning_7d_sent_at',
        'trial_warning_1d_sent_at',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialling']);
    }
}
