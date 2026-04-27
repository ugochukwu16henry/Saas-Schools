<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolAuditLog extends Model
{
    protected $fillable = [
        'school_id',
        'actor_type',
        'actor_id',
        'action',
        'changes',
        'meta',
    ];

    protected $casts = [
        'changes' => 'array',
        'meta' => 'array',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
