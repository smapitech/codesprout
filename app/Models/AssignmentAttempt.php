<?php

namespace App\Models;

use App\Enums\AttemptStatus;
use Database\Factories\AssignmentAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssignmentAttempt extends Model
{
    /** @use HasFactory<AssignmentAttemptFactory> */
    use HasFactory;

    protected $fillable = [
        'assignment_allocation_id',
        'assignment_version_id',
        'child_id',
        'attempt_number',
        'status',
        'started_at',
        'last_activity_at',
        'submitted_at',
        'auto_score',
        'manual_score',
        'final_score',
        'maximum_score',
        'time_spent_seconds',
        'hints_used',
        'is_late',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'status' => AttemptStatus::class,
            'started_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'submitted_at' => 'datetime',
            'auto_score' => 'decimal:2',
            'manual_score' => 'decimal:2',
            'final_score' => 'decimal:2',
            'maximum_score' => 'decimal:2',
            'time_spent_seconds' => 'integer',
            'hints_used' => 'integer',
            'is_late' => 'boolean',
        ];
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(AssignmentAllocation::class, 'assignment_allocation_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(AssignmentVersion::class, 'assignment_version_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(AssignmentResponse::class);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(AssignmentFeedback::class);
    }

    public function rubricScores(): HasMany
    {
        return $this->hasMany(AssignmentRubricScore::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SubmissionAttachment::class);
    }
}
