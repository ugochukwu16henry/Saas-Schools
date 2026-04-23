<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class AiRequest extends Model
{
    protected $fillable = [
        'school_id',
        'user_id',
        'feature',
        'provider',
        'model',
        'status',
        'prompt_hash',
        'tokens_input',
        'tokens_output',
        'latency_ms',
        'error_code',
        'error_message',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AiAuditLog::class);
    }
}
