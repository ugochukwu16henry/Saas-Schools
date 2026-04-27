<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Affiliate extends Authenticatable
{
    use Notifiable;

    protected $table = 'affiliates';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'country',
        'bio',
        'password',
        'status',
        'code',
        'photo_path',
        'bank_name',
        'account_number',
        'account_name',
        'admin_notes',
        'approved_at',
        'approved_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'account_number' => 'encrypted',
    ];

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(PlatformAdmin::class, 'approved_by');
    }

    public function schools(): HasMany
    {
        return $this->hasMany(School::class, 'affiliate_id');
    }

    public function commissionLedger(): HasMany
    {
        return $this->hasMany(AffiliateCommissionLedger::class, 'affiliate_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(AffiliatePayout::class, 'affiliate_id');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved' && $this->code !== null;
    }
}
