<?php

namespace App\Policies;

use App\Enums\ContentStatus;
use App\Enums\RoleName;
use App\Models\User;

class CurriculumPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            RoleName::Administrator->value,
            RoleName::Teacher->value,
        ]);
    }

    public function view(User $user, object $model): bool
    {
        if ($user->hasRole(RoleName::Teacher->value)) {
            return $this->isPublished($model);
        }

        return $user->hasRole(RoleName::Administrator->value);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    public function update(User $user, object $model): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    public function delete(User $user, object $model): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    public function publish(User $user, object $model): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    public function archive(User $user, object $model): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    public function restore(User $user, object $model): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    public function duplicate(User $user, object $model): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    public function reorder(User $user, object $model): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    public function import(User $user): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    public function export(User $user, object $model): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    public function preview(User $user, object $model): bool
    {
        return $user->hasAnyRole([
            RoleName::Administrator->value,
            RoleName::Teacher->value,
        ]);
    }

    public function manageSkills(User $user): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    private function isPublished(object $model): bool
    {
        if (! method_exists($model, 'getAttribute')) {
            return false;
        }

        $status = data_get($model, 'status');

        return $status instanceof ContentStatus
            ? $status === ContentStatus::Published
            : $status === ContentStatus::Published->value;
    }
}
