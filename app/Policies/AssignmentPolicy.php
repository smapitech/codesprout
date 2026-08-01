<?php

namespace App\Policies;

use App\Enums\ContentStatus;
use App\Enums\RoleName;
use App\Models\Assignment;
use App\Models\User;

class AssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            RoleName::Administrator->value,
            RoleName::Teacher->value,
        ]);
    }

    public function view(User $user, Assignment $assignment): bool
    {
        if ($user->hasRole(RoleName::Administrator->value)) {
            return true;
        }

        if ($assignment->status === ContentStatus::Published) {
            return $user->hasRole(RoleName::Teacher->value);
        }

        return $assignment->owner_id === $user->id || $assignment->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            RoleName::Administrator->value,
            RoleName::Teacher->value,
        ]);
    }

    public function update(User $user, Assignment $assignment): bool
    {
        if ($user->hasRole(RoleName::Administrator->value)) {
            return true;
        }

        return $assignment->owner_id === $user->id || $assignment->created_by === $user->id;
    }

    public function archive(User $user, Assignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }

    public function restore(User $user, Assignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }

    public function allocate(User $user, Assignment $assignment): bool
    {
        if ($user->hasRole(RoleName::Administrator->value)) {
            return true;
        }

        return $assignment->owner_id === $user->id || $assignment->created_by === $user->id || $assignment->status === ContentStatus::Published;
    }
}
