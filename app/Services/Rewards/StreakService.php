<?php

namespace App\Services\Rewards;

use App\Models\LearnerProgressProfile;
use App\Models\StreakRecord;
use App\Models\User;
use Carbon\CarbonImmutable;

class StreakService
{
    public function recordQualifyingDay(User $child, \DateTimeInterface $occurredAt, LearnerProgressProfile $profile, ?string $timezone = null): bool
    {
        $timezone = $timezone ?: config('app.timezone', 'UTC');
        $learningDate = CarbonImmutable::parse($occurredAt)->timezone($timezone)->startOfDay();

        $record = StreakRecord::query()->firstOrCreate(
            [
                'child_id' => $child->getKey(),
                'learning_date' => $learningDate,
                'timezone' => $timezone,
            ],
            [
                'qualifying_activity_count' => 0,
                'first_qualifying_activity_at' => $occurredAt,
                'last_qualifying_activity_at' => $occurredAt,
                'status' => 'qualifying',
            ],
        );

        $isNewDay = ! $record->wasRecentlyCreated;
        $record->increment('qualifying_activity_count');
        $record->forceFill(['last_qualifying_activity_at' => $occurredAt])->save();

        if ($isNewDay) {
            return false;
        }

        $previous = $profile->last_learning_date?->copy();
        $today = $learningDate;
        $current = 1;

        if ($previous && $previous->toDateString() === $today->subDay()->toDateString()) {
            $current = $profile->current_streak + 1;
        } elseif ($previous && $previous->toDateString() === $today->toDateString()) {
            $current = $profile->current_streak;
        }

        $profile->forceFill([
            'current_streak' => $current,
            'longest_streak' => max($profile->longest_streak, $current),
            'last_learning_date' => $learningDate->toDateString(),
            'progress_calculated_at' => now(),
        ])->save();

        return true;
    }
}
