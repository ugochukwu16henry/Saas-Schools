<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferNotificationEvent extends Model
{
    protected $fillable = [
        'transfer_id',
        'school_id',
        'notifiable_id',
        'notifiable_email',
        'notification_class',
        'channel',
        'status',
        'error',
        'payload',
        'resolved_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'resolved_at' => 'datetime',
    ];
}
