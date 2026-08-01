<?php

namespace App\Models;

use App\Enums\ProgressEventStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProgressEvent extends Model
{
    protected $fillable = [
        'event_uuid',
        'event_type',
        'child_id',
        'source_type',
        'source_id',
        'curriculum_id',
        'curriculum_world_id',
        'curriculum_unit_id',
        'curriculum_lesson_id',
        'lesson_stage_id',
        'skill_id',
        'occurred_at',
        'performance_summary',
        'metadata',
        'actor_id',
        'idempotency_key',
        'status',
        'processed_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'performance_summary' => 'array',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
            'status' => ProgressEventStatus::class,
            'processed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $event): void {
            $event->event_uuid ??= (string) Str::uuid();
        });
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }
}
