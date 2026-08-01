<?php

namespace App\Models;

use App\Enums\AssignmentFeedbackType;
use Database\Factories\AssignmentFeedbackFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentFeedback extends Model
{
    /** @use HasFactory<AssignmentFeedbackFactory> */
    use HasFactory;

    protected $fillable = [
        'assignment_attempt_id',
        'teacher_id',
        'feedback_text',
        'audio_feedback_path',
        'feedback_type',
        'returned_for_retry',
        'visible_to_child',
        'visible_to_parent',
    ];

    protected function casts(): array
    {
        return [
            'feedback_type' => AssignmentFeedbackType::class,
            'returned_for_retry' => 'boolean',
            'visible_to_child' => 'boolean',
            'visible_to_parent' => 'boolean',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AssignmentAttempt::class, 'assignment_attempt_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
