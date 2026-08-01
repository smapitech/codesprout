<?php

namespace App\Services\Rewards;

use App\Enums\RewardLedgerStatus;
use App\Enums\RewardType;
use App\Models\LearnerProgressProfile;
use App\Models\RewardLedgerEntry;
use App\Models\User;

class ProgressRecalculationService
{
    public function __construct(private readonly ExperienceLevelService $levels) {}

    /**
     * @return array<string, mixed>
     */
    public function recalculate(User $child, bool $dryRun = true): array
    {
        $stars = (int) RewardLedgerEntry::query()
            ->where('child_id', $child->getKey())
            ->where('status', RewardLedgerStatus::Awarded)
            ->where('reward_type', RewardType::Stars)
            ->sum('amount');

        $experience = (int) RewardLedgerEntry::query()
            ->where('child_id', $child->getKey())
            ->where('status', RewardLedgerStatus::Awarded)
            ->where('reward_type', RewardType::Experience)
            ->sum('amount');

        $level = $this->levels->levelForExperience($experience);
        $profile = LearnerProgressProfile::query()->firstOrCreate(['child_id' => $child->getKey()]);

        $report = [
            'child_id' => $child->getKey(),
            'dry_run' => $dryRun,
            'previous' => [
                'stars' => $profile->total_stars,
                'experience' => $profile->total_experience,
                'level' => $profile->currentLevel?->name,
            ],
            'calculated' => [
                'stars' => $stars,
                'experience' => $experience,
                'level' => $level?->name,
            ],
        ];

        if (! $dryRun) {
            $profile->forceFill([
                'total_stars' => max(0, $stars),
                'total_experience' => max(0, $experience),
                'current_level_id' => $level?->getKey(),
                'progress_calculated_at' => now(),
            ])->save();
        }

        return $report;
    }
}
