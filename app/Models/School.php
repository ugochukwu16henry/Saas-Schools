<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class School extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'unique_code',
        'email',
        'phone',
        'address',
        'logo',
        'status',
        'free_student_limit',
        'billing_plan_id',
        'paystack_customer_code',
        'affiliate_id',
        'affiliate_attributed_at',
    ];

    protected $casts = [
        'affiliate_attributed_at' => 'datetime',
        'onboarding_completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $school): void {
            if (!$school->unique_code) {
                $school->unique_code = static::generateUniqueCode();
            }
        });
    }

    public function users()
    {
        return $this->hasMany(\App\User::class);
    }

    public function scopeSearchable($query, string $term)
    {
        $search = trim($term);

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('unique_code', 'like', "%{$search}%");
        });
    }

    public static function findByUniqueCode(string $code): ?self
    {
        return static::query()
            ->where('unique_code', strtoupper(trim($code)))
            ->first();
    }

    public function settings()
    {
        return $this->hasMany(Setting::class);
    }

    public function subscription()
    {
        return $this->hasOne(SchoolSubscription::class);
    }

    public function billingPlan()
    {
        return $this->belongsTo(BillingPlan::class, 'billing_plan_id');
    }

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(SchoolAuditLog::class);
    }

    /**
     * Number of students above the free limit (billable).
     */
    public function billableStudentCount(): int
    {
        $total = $this->users()->where('user_type', 'student')->count();
        return max(0, $total - $this->effectiveFreeStudentLimit());
    }

    public function effectiveFreeStudentLimit(): int
    {
        if ($this->free_student_limit !== null) {
            return (int) $this->free_student_limit;
        }

        if ($this->billingPlan) {
            return (int) $this->billingPlan->default_free_student_limit;
        }

        return BillingPlan::DEFAULT_FREE_STUDENT_LIMIT;
    }

    public function effectiveMonthlyRate(): int
    {
        if ($this->billingPlan) {
            return (int) $this->billingPlan->monthly_rate_per_student;
        }

        return BillingPlan::DEFAULT_MONTHLY_RATE_PER_STUDENT;
    }

    public function effectiveOneTimeAddRate(): int
    {
        if ($this->billingPlan) {
            return (int) $this->billingPlan->one_time_add_rate;
        }

        return BillingPlan::DEFAULT_ONE_TIME_ADD_RATE;
    }

    public function effectiveAffiliateOneTimeCommissionRate(): int
    {
        if ($this->billingPlan) {
            return (int) $this->billingPlan->affiliate_one_time_commission_per_student;
        }

        return (int) config('affiliate.one_time_per_new_billable_student', BillingPlan::DEFAULT_AFFILIATE_ONE_TIME_COMMISSION_NGN);
    }

    public function effectiveAffiliateMonthlyCommissionRate(): int
    {
        if ($this->billingPlan) {
            return (int) $this->billingPlan->affiliate_monthly_commission_per_student;
        }

        return (int) config('affiliate.monthly_per_billable_student', BillingPlan::DEFAULT_AFFILIATE_MONTHLY_COMMISSION_NGN);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trial'], true);
    }

    public function getLogoAttribute($value)
    {
        if (!$value) {
            return null;
        }

        $raw = (string) $value;

        if (filter_var($raw, FILTER_VALIDATE_URL)) {
            $path = (string) parse_url($raw, PHP_URL_PATH);
            if ($path !== '' && (Str::startsWith($path, '/storage/') || Str::startsWith($path, '/global_assets/'))) {
                return asset(ltrim($path, '/'));
            }

            return $raw;
        }

        $clean = ltrim($raw, '/');
        if (Str::startsWith($clean, 'storage/') || Str::startsWith($clean, 'global_assets/')) {
            return asset($clean);
        }

        if (Str::startsWith($clean, 'uploads/')) {
            return asset('storage/' . $clean);
        }

        return asset($clean);
    }

    private static function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
            $exists = static::query()->where('unique_code', $code)->exists();
        } while ($exists);

        return $code;
    }
}
