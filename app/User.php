<?php

namespace App;

use App\Traits\BelongsToSchool;
use App\Models\BloodGroup;
use App\Models\Lga;
use App\Models\Nationality;
use App\Models\StaffRecord;
use App\Models\State;
use App\Models\StudentRecord;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, BelongsToSchool;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'phone2',
        'dob',
        'gender',
        'photo',
        'address',
        'bg_id',
        'password',
        'nal_id',
        'state_id',
        'lga_id',
        'code',
        'user_type',
        'email_verified_at',
        'school_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function school()
    {
        return $this->belongsTo(\App\Models\School::class);
    }

    public function student_record()
    {
        return $this->hasOne(StudentRecord::class);
    }

    public function lga()
    {
        return $this->belongsTo(Lga::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nal_id');
    }

    public function blood_group()
    {
        return $this->belongsTo(BloodGroup::class, 'bg_id');
    }

    public function staff()
    {
        return $this->hasMany(StaffRecord::class);
    }

    public function getPhotoAttribute($value)
    {
        if (!$value) {
            return \App\Helpers\Qs::getDefaultUserImage();
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

        return $raw;
    }
}
