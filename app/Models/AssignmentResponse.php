<?php

namespace App\Models;

use Database\Factories\AssignmentResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentResponse extends Model
{
    /** @use HasFactory<AssignmentResponseFactory> */
    use HasFactory;

    protected $fillable = [
        'assignment_attempt_id',
        'assignment_item_id',
        'response_data',
        'text_response',
        'is_correct',
        'auto_score',
        'manual_score',
        'marked_by',
        'marked_at',
        'teacher_comment',
    ];

    protected function casts(): array
    {
        return [
            'response_data' => 'array',
            'is_correct' => 'boolean',
            'auto_score' => 'decimal:2',
            'manual_score' => 'decimal:2',
            'marked_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AssignmentAttempt::class, 'assignment_attempt_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(AssignmentItem::class, 'assignment_item_id');
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
