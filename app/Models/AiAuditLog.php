<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAuditLog extends Model
{
    protected $fillable = [
        'ai_request_id',
        'event',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function aiRequest()
    {
        return $this->belongsTo(AiRequest::class);
    }
}
