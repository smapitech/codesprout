<?php

namespace App\Models;

use App\Enums\AllocationStatus;
use App\Enums\AssignmentScoringMethod;
use App\Enums\LateSubmissionPolicy;
use Database\Factories\AssignmentAllocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssignmentAllocation extends Model
{
    /** @use HasFactory<AssignmentAllocationFactory> */
    use HasFactory;

    protected $fillable = [
        'assignment_version_id',
        'assigned_by',
        'class_id',
        'group_id',
        'child_id',
        'available_from',
        'due_at',
        'closes_at',
        'attempt_limit',
        'scoring_method',
        'show_score_to_child',
        'show_correct_answers',
        'allow_late_submission',
        'late_submission_policy',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'available_from' => 'datetime',
            'due_at' => 'datetime',
            'closes_at' => 'datetime',
            'attempt_limit' => 'integer',
            'scoring_method' => AssignmentScoringMethod::class,
            'show_score_to_child' => 'boolean',
            'show_correct_answers' => 'boolean',
            'allow_late_submission' => 'boolean',
            'late_submission_policy' => LateSubmissionPolicy::class,
            'status' => AllocationStatus::class,
        ];
    }

    public function assignmentVersion(): BelongsTo
    {
        return $this->belongsTo(AssignmentVersion::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(LearningClass::class, 'class_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(LearnerGroup::class, 'group_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(AssignmentAttempt::class);
    }

    public function targetLabel(): string
    {
        return $this->classroom?->name
            ?? $this->group?->name
            ?? $this->child?->name
            ?? 'Unassigned';
    }
}
