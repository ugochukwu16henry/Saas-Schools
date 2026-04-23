<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateCommissionLedger extends Model
{
    protected $table = 'affiliate_commission_ledger';

    protected $fillable = [
        'affiliate_id',
        'school_id',
        'paystack_reference',
        'event_type',
        'billable_students_snapshot',
        'newly_added_students_snapshot',
        'one_time_commission_ngn',
        'monthly_commission_ngn',
        'total_commission_ngn',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
