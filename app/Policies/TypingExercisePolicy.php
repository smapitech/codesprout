<?php

namespace App\Policies;

use App\Enums\ContentStatus;
use App\Enums\RoleName;
use App\Models\TypingExercise;
use App\Models\User;

class TypingExercisePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([RoleName::Administrator->value, RoleName::Teacher->value]);
    }

    public function view(User $user, TypingExercise $exercise): bool
    {
        if ($user->hasRole(RoleName::Administrator->value)) {
            return true;
        }

        return $user->hasRole(RoleName::Teacher->value) && $exercise->status === ContentStatus::Published;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    public function update(User $user, TypingExercise $exercise): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    public function publish(User $user, TypingExercise $exercise): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }
}
