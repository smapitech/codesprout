<?php

namespace App\Models;

use App\Enums\CelebrationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Celebration extends Model
{
    protected $fillable = [
        'uuid',
        'child_id',
        'celebration_type',
        'heading',
        'message',
        'reward_summary',
        'badge_award_id',
        'progress_event_id',
        'display_after',
        'acknowledged_at',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'celebration_type' => CelebrationType::class,
            'reward_summary' => 'array',
            'display_after' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $celebration): void {
            $celebration->uuid ??= (string) Str::uuid();
        });
    }

    public function badgeAward(): BelongsTo
    {
        return $this->belongsTo(BadgeAward::class);
    }
}
