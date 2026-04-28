<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingPlan extends Model
{
    public const DEFAULT_FREE_STUDENT_LIMIT = 50;
    public const DEFAULT_MONTHLY_RATE_PER_STUDENT = 500;
    public const DEFAULT_ONE_TIME_ADD_RATE = 1000;
    public const DEFAULT_AFFILIATE_ONE_TIME_COMMISSION_NGN = 200;
    public const DEFAULT_AFFILIATE_MONTHLY_COMMISSION_NGN = 100;

    protected $fillable = [
        'name',
        'monthly_rate_per_student',
        'one_time_add_rate',
        'affiliate_one_time_commission_per_student',
        'affiliate_monthly_commission_per_student',
        'default_free_student_limit',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'is_default' => 'bool',
    ];

    public function schools(): HasMany
    {
        return $this->hasMany(School::class, 'billing_plan_id');
    }

    public static function defaultActive(): ?self
    {
        return static::query()
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }
}
