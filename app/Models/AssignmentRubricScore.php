<?php

namespace App\Models;

use Database\Factories\AssignmentRubricScoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentRubricScore extends Model
{
    /** @use HasFactory<AssignmentRubricScoreFactory> */
    use HasFactory;

    protected $fillable = [
        'assignment_attempt_id',
        'rubric_criterion_id',
        'awarded_points',
        'teacher_comment',
        'marked_by',
    ];

    protected function casts(): array
    {
        return [
            'awarded_points' => 'decimal:2',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AssignmentAttempt::class, 'assignment_attempt_id');
    }

    public function rubricCriterion(): BelongsTo
    {
        return $this->belongsTo(AssessmentRubricCriterion::class, 'rubric_criterion_id');
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
