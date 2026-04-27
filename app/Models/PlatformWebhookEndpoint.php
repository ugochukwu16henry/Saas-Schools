<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformWebhookEndpoint extends Model
{
    protected $fillable = [
        'name',
        'url',
        'secret',
        'is_active',
        'events',
        'last_success_at',
        'last_failure_at',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'events' => 'array',
        'last_success_at' => 'datetime',
        'last_failure_at' => 'datetime',
    ];

    public function deliveries()
    {
        return $this->hasMany(PlatformWebhookDelivery::class, 'endpoint_id');
    }

    public function supportsEvent(string $eventType): bool
    {
        $events = $this->events;
        if (! is_array($events) || empty($events)) {
            return true;
        }

        return in_array($eventType, $events, true) || in_array('*', $events, true);
    }
}
