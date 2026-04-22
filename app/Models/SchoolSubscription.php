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
    ];

    protected $dates = ['trial_ends_at', 'next_payment_date'];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialling']);
    }
}
