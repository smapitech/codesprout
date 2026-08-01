<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypingEventBatch extends Model
{
    protected $fillable = [
        'typing_session_id',
        'batch_uuid',
        'first_sequence',
        'last_sequence',
        'event_count',
        'received_at',
        'payload_checksum',
        'processing_status',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TypingSession::class, 'typing_session_id');
    }
}
