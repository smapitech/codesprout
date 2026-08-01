<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypingInputEvent extends Model
{
    protected $fillable = [
        'typing_session_id',
        'typing_content_item_id',
        'sequence_number',
        'character_position',
        'event_type',
        'expected_character',
        'entered_character',
        'normalised_character',
        'correctness_state',
        'correction_sequence',
        'input_method',
        'modifier_state',
        'elapsed_offset_ms',
        'server_received_at',
        'retained_until',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'modifier_state' => 'array',
            'metadata' => 'array',
            'server_received_at' => 'datetime',
            'retained_until' => 'datetime',
            'sequence_number' => 'integer',
            'character_position' => 'integer',
            'elapsed_offset_ms' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TypingSession::class, 'typing_session_id');
    }

    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(TypingContentItem::class, 'typing_content_item_id');
    }
}
