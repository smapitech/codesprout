<?php

namespace App\Models;

use App\Enums\HtmlAttemptStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class HtmlAttempt extends Model
{
    protected $fillable = ['uuid', 'child_id', 'preview_actor_id', 'html_exercise_version_id', 'learner_webpage_project_id', 'lesson_stage_id', 'assignment_allocation_id', 'assignment_attempt_id', 'assignment_item_id', 'attempt_type', 'status', 'input_mode', 'state_version', 'started_at', 'first_interaction_at', 'paused_at', 'resumed_at', 'completed_at', 'submitted_at', 'active_duration_ms', 'assistance_count', 'idempotency_key', 'metadata'];

    protected static function booted(): void
    {
        static::creating(fn (self $attempt) => $attempt->uuid ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return [
            'status' => HtmlAttemptStatus::class,
            'started_at' => 'datetime',
            'first_interaction_at' => 'datetime',
            'paused_at' => 'datetime',
            'resumed_at' => 'datetime',
            'completed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function exerciseVersion(): BelongsTo
    {
        return $this->belongsTo(HtmlExerciseVersion::class, 'html_exercise_version_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(LearnerWebpageProject::class, 'learner_webpage_project_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(HtmlAttemptResponse::class);
    }

    public function validationResult(): HasOne
    {
        return $this->hasOne(HtmlValidationResult::class);
    }
}
