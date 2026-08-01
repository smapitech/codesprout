<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\ProjectTemplate;
use App\Models\User;

class ProjectTemplatePolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    public function update(User $user, ProjectTemplate $template): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    public function publish(User $user, ProjectTemplate $template): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }
}
