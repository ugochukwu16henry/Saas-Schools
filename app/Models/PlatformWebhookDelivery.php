<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformWebhookDelivery extends Model
{
    protected $fillable = [
        'endpoint_id',
        'event_type',
        'platform_notification_id',
        'request_id',
        'response_status',
        'is_success',
        'error_message',
        'attempt',
        'delivered_at',
    ];

    protected $casts = [
        'is_success' => 'bool',
        'delivered_at' => 'datetime',
    ];

    public function endpoint()
    {
        return $this->belongsTo(PlatformWebhookEndpoint::class, 'endpoint_id');
    }

    public function notification()
    {
        return $this->belongsTo(PlatformNotification::class, 'platform_notification_id');
    }
}
