<?php

namespace App\Policies;

use App\Enums\ContentStatus;
use App\Enums\RoleName;
use App\Models\HtmlExercise;
use App\Models\User;

class HtmlExercisePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([RoleName::Administrator->value, RoleName::Teacher->value]);
    }

    public function view(User $user, HtmlExercise $exercise): bool
    {
        return $user->hasRole(RoleName::Administrator->value)
            || ($user->hasRole(RoleName::Teacher->value) && $exercise->status === ContentStatus::Published);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    public function update(User $user, HtmlExercise $exercise): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    public function publish(User $user, HtmlExercise $exercise): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }
}
