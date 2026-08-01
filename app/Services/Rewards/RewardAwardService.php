<?php

namespace App\Services\Rewards;

use App\Enums\BadgeAwardStatus;
use App\Enums\CelebrationType;
use App\Enums\ContentStatus;
use App\Enums\RewardLedgerStatus;
use App\Enums\RewardRepeatPolicy;
use App\Enums\RewardType;
use App\Models\AuditLog;
use App\Models\BadgeAward;
use App\Models\BadgeDefinition;
use App\Models\Celebration;
use App\Models\LearnerProgressProfile;
use App\Models\ProgressEvent;
use App\Models\RewardLedgerEntry;
use App\Models\RewardRule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RewardAwardService
{
    public function __construct(private readonly ExperienceLevelService $levels) {}

    public function profileFor(User $child): LearnerProgressProfile
    {
        return LearnerProgressProfile::query()->firstOrCreate(
            ['child_id' => $child->getKey()],
            ['progress_calculated_at' => now()],
        );
    }

    public function awardForRule(ProgressEvent $event, RewardRule $rule): ?RewardLedgerEntry
    {
        if (! $this->eligibleForRepeat($event, $rule)) {
            return null;
        }

        $key = implode(':', ['reward', $event->idempotency_key, $rule->getKey()]);
        $existing = RewardLedgerEntry::query()->where('idempotency_key', $key)->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($event, $rule, $key): ?RewardLedgerEntry {
            $child = User::query()->findOrFail($event->child_id);
            $profile = LearnerProgressProfile::query()->where('child_id', $child->getKey())->lockForUpdate()->first()
                ?? $this->profileFor($child);

            $entry = RewardLedgerEntry::query()->create([
                'child_id' => $child->getKey(),
                'reward_type' => $rule->reward_type,
                'amount' => $rule->reward_type === RewardType::Badge ? 0 : $rule->reward_amount,
                'badge_definition_id' => $rule->badge_definition_id,
                'reward_rule_id' => $rule->getKey(),
                'source_type' => $event->source_type,
                'source_id' => $event->source_id,
                'progress_event_id' => $event->getKey(),
                'reason' => $rule->name,
                'status' => RewardLedgerStatus::Awarded,
                'awarded_at' => $event->occurred_at ?? now(),
                'awarded_by' => $event->actor_id,
                'idempotency_key' => $key,
                'metadata' => [
                    'rule_version' => $rule->version,
                    'event_type' => $event->event_type,
                ],
            ]);

            match ($rule->reward_type) {
                RewardType::Stars => $profile->increment('total_stars', $rule->reward_amount),
                RewardType::Experience => $profile->increment('total_experience', $rule->reward_amount),
                RewardType::Badge => $this->awardBadge($event, $rule),
                RewardType::MissionCompletion => $profile->increment('completed_missions'),
                RewardType::LessonCompletion => $profile->increment('completed_lessons'),
                RewardType::UnitCompletion => $profile->increment('completed_units'),
                RewardType::WorldCompletion => $profile->increment('completed_worlds'),
                default => null,
            };

            $this->refreshLevel($profile->fresh(), $event);
            $this->celebrateReward($event, $rule);

            return $entry;
        });
    }

    public function adjust(User $child, int $amount, RewardType $type, User $actor, string $reason): RewardLedgerEntry
    {
        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'A reason is required for reward adjustments.']);
        }

        return DB::transaction(function () use ($child, $amount, $type, $actor, $reason): RewardLedgerEntry {
            $profile = LearnerProgressProfile::query()->where('child_id', $child->getKey())->lockForUpdate()->first()
                ?? $this->profileFor($child);

            if ($type === RewardType::Stars && $profile->total_stars + $amount < 0) {
                throw ValidationException::withMessages(['amount' => 'Star totals cannot become negative.']);
            }

            if ($type === RewardType::Experience && $profile->total_experience + $amount < 0) {
                throw ValidationException::withMessages(['amount' => 'XP totals cannot become negative.']);
            }

            $entry = RewardLedgerEntry::query()->create([
                'child_id' => $child->getKey(),
                'reward_type' => $type,
                'amount' => $amount,
                'source_type' => 'manual_adjustment',
                'source_id' => $actor->getKey(),
                'reason' => $reason,
                'status' => RewardLedgerStatus::Adjustment,
                'awarded_at' => now(),
                'awarded_by' => $actor->getKey(),
                'adjustment_reason' => $reason,
                'idempotency_key' => 'adjustment:'.$child->getKey().':'.$type->value.':'.sha1($reason.now()->toIso8601String()),
            ]);

            if ($type === RewardType::Stars) {
                $profile->increment('total_stars', $amount);
            } elseif ($type === RewardType::Experience) {
                $profile->increment('total_experience', $amount);
                $this->refreshLevel($profile->fresh(), null);
            }

            AuditLog::query()->create([
                'actor_user_id' => $actor->getKey(),
                'action' => 'reward.adjusted',
                'subject_type' => RewardLedgerEntry::class,
                'subject_id' => $entry->getKey(),
                'metadata' => [
                    'child_id' => $child->getKey(),
                    'reward_type' => $type->value,
                    'amount' => $amount,
                    'reason' => $reason,
                ],
                'created_at' => now(),
            ]);

            return $entry;
        });
    }

    private function eligibleForRepeat(ProgressEvent $event, RewardRule $rule): bool
    {
        $query = RewardLedgerEntry::query()
            ->where('child_id', $event->child_id)
            ->where('reward_rule_id', $rule->getKey())
            ->where('status', RewardLedgerStatus::Awarded);

        if ($rule->repeat_policy === RewardRepeatPolicy::OncePerSource) {
            return ! (clone $query)
                ->where('source_type', $event->source_type)
                ->where('source_id', $event->source_id)
                ->exists();
        }

        if ($rule->repeat_policy === RewardRepeatPolicy::OncePerDay) {
            return ! (clone $query)->whereDate('awarded_at', $event->occurred_at)->exists();
        }

        if ($rule->repeat_policy === RewardRepeatPolicy::Limited && $rule->maximum_awards) {
            return (clone $query)->count() < $rule->maximum_awards;
        }

        if ($rule->daily_cap) {
            $awardedToday = (clone $query)->whereDate('awarded_at', $event->occurred_at)->sum('amount');

            return $awardedToday < $rule->daily_cap;
        }

        return true;
    }

    private function awardBadge(ProgressEvent $event, RewardRule $rule): ?BadgeAward
    {
        $badge = BadgeDefinition::query()->find($rule->badge_definition_id);
        if (! $badge || $badge->status !== ContentStatus::Published || $badge->archived_at) {
            return null;
        }

        if (! $badge->repeatable && BadgeAward::query()->where('child_id', $event->child_id)->where('badge_definition_id', $badge->getKey())->exists()) {
            return null;
        }

        $key = implode(':', ['badge', $event->idempotency_key, $badge->getKey()]);

        return BadgeAward::query()->firstOrCreate(
            ['idempotency_key' => $key],
            [
                'child_id' => $event->child_id,
                'badge_definition_id' => $badge->getKey(),
                'badge_snapshot' => [
                    'name' => $badge->name,
                    'short_description' => $badge->short_description,
                    'badge_image_path' => $badge->badge_image_path,
                    'alt_text' => $badge->alt_text,
                ],
                'source_type' => $event->source_type,
                'source_id' => $event->source_id,
                'progress_event_id' => $event->getKey(),
                'awarded_at' => $event->occurred_at ?? now(),
                'awarded_by' => $event->actor_id,
                'status' => BadgeAwardStatus::Earned,
            ],
        );
    }

    private function refreshLevel(LearnerProgressProfile $profile, ?ProgressEvent $event): void
    {
        $level = $this->levels->levelForExperience($profile->total_experience);

        if (! $level || (int) $profile->current_level_id === (int) $level->getKey()) {
            return;
        }

        $profile->forceFill(['current_level_id' => $level->getKey(), 'progress_calculated_at' => now()])->save();

        if ($event) {
            Celebration::query()->firstOrCreate(
                ['idempotency_key' => 'level:'.$event->idempotency_key.':'.$level->getKey()],
                [
                    'child_id' => $profile->child_id,
                    'celebration_type' => CelebrationType::LevelAdvanced,
                    'heading' => 'You reached '.$level->name.'!',
                    'message' => 'Every small mission helps your skills grow.',
                    'reward_summary' => ['level' => $level->name],
                    'progress_event_id' => $event->getKey(),
                    'display_after' => now(),
                ],
            );
        }
    }

    private function celebrateReward(ProgressEvent $event, RewardRule $rule): void
    {
        $type = $rule->reward_type === RewardType::Badge ? CelebrationType::BadgeEarned : CelebrationType::MissionCompleted;

        Celebration::query()->firstOrCreate(
            ['idempotency_key' => 'celebration:'.$event->idempotency_key.':'.$rule->getKey()],
            [
                'child_id' => $event->child_id,
                'celebration_type' => $type,
                'heading' => $rule->reward_type === RewardType::Badge ? 'New badge earned!' : 'Mission complete!',
                'message' => 'Great job. Your CodeSprout adventure is growing.',
                'reward_summary' => [
                    'reward_type' => $rule->reward_type->value,
                    'amount' => $rule->reward_amount,
                ],
                'progress_event_id' => $event->getKey(),
                'display_after' => now(),
            ],
        );
    }
}
