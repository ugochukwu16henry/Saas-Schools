<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

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
        'consecutive_failures',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'events' => 'array',
        'last_success_at' => 'datetime',
        'last_failure_at' => 'datetime',
        'consecutive_failures' => 'int',
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

    public function getSecretAttribute($value): string
    {
        if (! is_string($value) || $value === '') {
            return '';
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return $value;
        }
    }

    public function setSecretAttribute($value): void
    {
        $raw = (string) $value;
        $this->attributes['secret'] = $raw === '' ? '' : Crypt::encryptString($raw);
    }
}
