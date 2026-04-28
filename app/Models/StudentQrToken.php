<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class StudentQrToken extends Model
{
    protected $fillable = [
        'student_id',
        'school_id',
        'token',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
