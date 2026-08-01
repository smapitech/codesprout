<?php

namespace App\Policies;

use App\Models\RewardRule;
use App\Models\User;

class RewardRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('administrator');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('administrator');
    }

    public function update(User $user, RewardRule $rewardRule): bool
    {
        return $user->hasRole('administrator');
    }

    public function publish(User $user, RewardRule $rewardRule): bool
    {
        return $user->hasRole('administrator');
    }
}
