<?php

namespace App\Policies;

use App\Models\BadgeDefinition;
use App\Models\User;

class BadgeDefinitionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('administrator');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('administrator');
    }

    public function update(User $user, BadgeDefinition $badgeDefinition): bool
    {
        return $user->hasRole('administrator');
    }

    public function publish(User $user, BadgeDefinition $badgeDefinition): bool
    {
        return $user->hasRole('administrator');
    }
}
