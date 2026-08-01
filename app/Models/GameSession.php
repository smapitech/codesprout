<?php

namespace App\Models;

use App\Enums\GameDifficulty;
use App\Enums\GameSessionStatus;
use Database\Factories\GameSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class GameSession extends Model
{
    /** @use HasFactory<GameSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'child_id',
        'game_version_id',
        'lesson_stage_id',
        'assignment_allocation_id',
        'assignment_attempt_id',
        'assignment_item_id',
        'status',
        'difficulty',
        'started_at',
        'last_activity_at',
        'paused_at',
        'completed_at',
        'abandoned_at',
        'client_session_identifier',
        'idempotency_key',
        'rounds',
        'progress_data',
        'current_round',
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
            'status' => GameSessionStatus::class,
            'difficulty' => GameDifficulty::class,
            'started_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'paused_at' => 'datetime',
            'completed_at' => 'datetime',
            'abandoned_at' => 'datetime',
            'rounds' => 'array',
            'progress_data' => 'array',
            'current_round' => 'integer',
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

    public function gameVersion(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class);
    }

    public function assignmentAllocation(): BelongsTo
    {
        return $this->belongsTo(AssignmentAllocation::class);
    }

    public function assignmentAttempt(): BelongsTo
    {
        return $this->belongsTo(AssignmentAttempt::class);
    }

    public function assignmentItem(): BelongsTo
    {
        return $this->belongsTo(AssignmentItem::class);
    }

    public function lessonStage(): BelongsTo
    {
        return $this->belongsTo(LessonStage::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(GameResult::class);
    }

    public function roundRecords(): HasMany
    {
        return $this->hasMany(GameSessionRound::class)->orderBy('round_number');
    }
}
