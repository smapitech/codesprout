<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\AssignmentVersion;
use App\Models\User;

class AssignmentVersionPolicy
{
    public function view(User $user, AssignmentVersion $version): bool
    {
        if ($user->hasRole(RoleName::Administrator->value)) {
            return true;
        }

        if ($version->isPublished()) {
            return $user->hasRole(RoleName::Teacher->value);
        }

        return $version->assignment?->owner_id === $user->id || $version->assignment?->created_by === $user->id;
    }

    public function update(User $user, AssignmentVersion $version): bool
    {
        if ($user->hasRole(RoleName::Administrator->value)) {
            return true;
        }

        return $version->assignment?->owner_id === $user->id || $version->assignment?->created_by === $user->id;
    }

    public function publish(User $user, AssignmentVersion $version): bool
    {
        return $this->update($user, $version);
    }

    public function duplicate(User $user, AssignmentVersion $version): bool
    {
        return $this->update($user, $version);
    }

    public function allocate(User $user, AssignmentVersion $version): bool
    {
        if ($user->hasRole(RoleName::Administrator->value)) {
            return true;
        }

        return $version->isPublished()
            && (
                $version->assignment?->owner_id === $user->id
                || $version->assignment?->created_by === $user->id
                || $user->hasRole(RoleName::Teacher->value)
            );
    }
}
