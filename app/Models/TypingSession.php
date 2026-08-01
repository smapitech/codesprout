<?php

namespace App\Models;

use App\Enums\TypingInputMethod;
use App\Enums\TypingSessionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class TypingSession extends Model
{
    protected $fillable = [
        'uuid',
        'child_id',
        'preview_actor_id',
        'typing_exercise_version_id',
        'lesson_stage_id',
        'assignment_allocation_id',
        'assignment_attempt_id',
        'game_session_id',
        'session_type',
        'input_method',
        'keyboard_layout',
        'status',
        'current_item_position',
        'state_version',
        'started_at',
        'first_input_at',
        'paused_at',
        'resumed_at',
        'completed_at',
        'submitted_at',
        'last_activity_at',
        'abandoned_at',
        'expires_at',
        'active_duration_ms',
        'paused_duration_ms',
        'last_event_sequence',
        'idempotency_key',
        'state',
        'metadata',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $session): void {
            $session->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'input_method' => TypingInputMethod::class,
            'status' => TypingSessionStatus::class,
            'started_at' => 'datetime',
            'first_input_at' => 'datetime',
            'paused_at' => 'datetime',
            'resumed_at' => 'datetime',
            'completed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'abandoned_at' => 'datetime',
            'expires_at' => 'datetime',
            'state' => 'array',
            'metadata' => 'array',
            'current_item_position' => 'integer',
            'state_version' => 'integer',
            'active_duration_ms' => 'integer',
            'paused_duration_ms' => 'integer',
            'last_event_sequence' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function previewActor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'preview_actor_id');
    }

    public function exerciseVersion(): BelongsTo
    {
        return $this->belongsTo(TypingExerciseVersion::class, 'typing_exercise_version_id');
    }

    public function lessonStage(): BelongsTo
    {
        return $this->belongsTo(LessonStage::class);
    }

    public function assignmentAllocation(): BelongsTo
    {
        return $this->belongsTo(AssignmentAllocation::class);
    }

    public function assignmentAttempt(): BelongsTo
    {
        return $this->belongsTo(AssignmentAttempt::class);
    }

    public function eventBatches(): HasMany
    {
        return $this->hasMany(TypingEventBatch::class);
    }

    public function inputEvents(): HasMany
    {
        return $this->hasMany(TypingInputEvent::class)->orderBy('sequence_number');
    }

    public function result(): HasOne
    {
        return $this->hasOne(TypingResult::class);
    }
}
