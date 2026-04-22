<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
