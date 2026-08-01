<?php

namespace App\Models;

use App\Enums\RewardLedgerStatus;
use App\Enums\RewardType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RewardLedgerEntry extends Model
{
    protected $fillable = [
        'uuid',
        'child_id',
        'reward_type',
        'amount',
        'badge_definition_id',
        'reward_rule_id',
        'source_type',
        'source_id',
        'progress_event_id',
        'reason',
        'status',
        'awarded_at',
        'awarded_by',
        'reversed_entry_id',
        'adjustment_reason',
        'idempotency_key',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'reward_type' => RewardType::class,
            'amount' => 'integer',
            'status' => RewardLedgerStatus::class,
            'awarded_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $entry): void {
            $entry->uuid ??= (string) Str::uuid();
        });
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(BadgeDefinition::class, 'badge_definition_id');
    }
}
