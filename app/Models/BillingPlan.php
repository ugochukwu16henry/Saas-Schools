<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingPlan extends Model
{
    protected $fillable = [
        'name',
        'monthly_rate_per_student',
        'one_time_add_rate',
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
}
