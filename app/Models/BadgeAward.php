<?php

namespace App\Models;

use App\Enums\BadgeAwardStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BadgeAward extends Model
{
    protected $fillable = [
        'uuid',
        'child_id',
        'badge_definition_id',
        'badge_snapshot',
        'source_type',
        'source_id',
        'progress_event_id',
        'awarded_at',
        'awarded_by',
        'status',
        'displayed_at',
        'acknowledged_at',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'badge_snapshot' => 'array',
            'awarded_at' => 'datetime',
            'status' => BadgeAwardStatus::class,
            'displayed_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $award): void {
            $award->uuid ??= (string) Str::uuid();
        });
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(BadgeDefinition::class, 'badge_definition_id');
    }
}
