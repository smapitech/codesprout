<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\LearnerWebpageProject;
use App\Models\User;

class LearnerWebpageProjectPolicy
{
    public function view(User $user, LearnerWebpageProject $project): bool
    {
        if ($user->hasRole(RoleName::Administrator->value)) {
            return true;
        }

        if ($project->child_id === $user->id) {
            return true;
        }

        if ($user->hasRole(RoleName::Parent->value)) {
            return $user->children()->whereKey($project->child_id)->exists();
        }

        if ($user->hasRole(RoleName::Teacher->value)) {
            return $user->teachingClasses()
                ->whereHas('learners', fn ($query) => $query->whereKey($project->child_id))
                ->exists();
        }

        return false;
    }

    public function update(User $user, LearnerWebpageProject $project): bool
    {
        return $project->child_id === $user->id;
    }

    public function review(User $user, LearnerWebpageProject $project): bool
    {
        return $user->hasRole(RoleName::Teacher->value)
            && $user->teachingClasses()
                ->whereHas('learners', fn ($query) => $query->whereKey($project->child_id))
                ->exists();
    }
}
