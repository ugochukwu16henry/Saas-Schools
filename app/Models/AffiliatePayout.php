<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliatePayout extends Model
{
    protected $fillable = [
        'affiliate_id',
        'approved_by',
        'paid_by',
        'amount_ngn',
        'status',
        'notes',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(PlatformAdmin::class, 'approved_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(PlatformAdmin::class, 'paid_by');
    }
}
