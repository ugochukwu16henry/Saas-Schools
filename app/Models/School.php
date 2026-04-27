<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class School extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'logo',
        'status',
        'free_student_limit',
        'paystack_customer_code',
        'affiliate_id',
        'affiliate_attributed_at',
    ];

    protected $casts = [
        'affiliate_attributed_at' => 'datetime',
    ];

    public function users()
    {
        return $this->hasMany(\App\User::class);
    }

    public function settings()
    {
        return $this->hasMany(Setting::class);
    }

    public function subscription()
    {
        return $this->hasOne(SchoolSubscription::class);
    }

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    /**
     * Number of students above the free limit (billable).
     */
    public function billableStudentCount(): int
    {
        $total = $this->users()->where('user_type', 'student')->count();
        return max(0, $total - $this->free_student_limit);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trial']);
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
}
