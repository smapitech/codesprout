<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProjectReview extends Model
{
    protected $fillable = ['uuid', 'learner_webpage_project_id', 'reviewed_revision_id', 'reviewer_id', 'review_status', 'rubric_result', 'child_feedback', 'teacher_only_notes', 'requested_changes', 'reviewed_at', 'released_at'];

    protected static function booted(): void
    {
        static::creating(fn (self $review) => $review->uuid ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return [
            'rubric_result' => 'array',
            'requested_changes' => 'array',
            'reviewed_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(LearnerWebpageProject::class, 'learner_webpage_project_id');
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(ProjectRevision::class, 'reviewed_revision_id');
    }
}
