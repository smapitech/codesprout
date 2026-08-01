<?php

namespace App\Services\Rewards;

use App\Enums\ContentStatus;
use App\Models\LearnerLevel;

class ExperienceLevelService
{
    public function levelForExperience(int $experience): ?LearnerLevel
    {
        return LearnerLevel::query()
            ->where('status', ContentStatus::Published)
            ->where('xp_threshold', '<=', max(0, $experience))
            ->orderByDesc('xp_threshold')
            ->orderByDesc('level_number')
            ->first();
    }

    public function nextLevelForExperience(int $experience): ?LearnerLevel
    {
        return LearnerLevel::query()
            ->where('status', ContentStatus::Published)
            ->where('xp_threshold', '>', max(0, $experience))
            ->orderBy('xp_threshold')
            ->orderBy('level_number')
            ->first();
    }
}
