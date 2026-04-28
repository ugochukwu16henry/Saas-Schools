<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class StudentTransfer extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'student_id',
        'from_school_id',
        'to_school_id',
        'requested_by',
        'accepted_by',
        'status',
        'from_class_id',
        'from_section_id',
        'from_session',
        'transfer_note',
        'rejected_reason',
        'transferred_at',
        'transfer_snapshot',
        'status_history',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
        'transfer_snapshot' => 'array',
        'status_history' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function fromSchool()
    {
        return $this->belongsTo(School::class, 'from_school_id');
    }

    public function toSchool()
    {
        return $this->belongsTo(School::class, 'to_school_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function acceptedBy()
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }
}
