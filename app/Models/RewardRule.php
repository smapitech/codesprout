<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\RewardRepeatPolicy;
use App\Enums\RewardType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RewardRule extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'event_type',
        'source_type',
        'eligibility_conditions',
        'reward_type',
        'reward_amount',
        'badge_definition_id',
        'repeat_policy',
        'maximum_awards',
        'daily_cap',
        'effective_from',
        'expires_at',
        'priority',
        'status',
        'version',
        'created_by',
        'published_by',
        'published_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'eligibility_conditions' => 'array',
            'reward_type' => RewardType::class,
            'repeat_policy' => RewardRepeatPolicy::class,
            'reward_amount' => 'integer',
            'maximum_awards' => 'integer',
            'daily_cap' => 'integer',
            'priority' => 'integer',
            'status' => ContentStatus::class,
            'version' => 'integer',
            'effective_from' => 'datetime',
            'expires_at' => 'datetime',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $rule): void {
            $rule->uuid ??= (string) Str::uuid();
        });
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(BadgeDefinition::class, 'badge_definition_id');
    }
}
