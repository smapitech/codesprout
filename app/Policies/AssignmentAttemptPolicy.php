<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\AssignmentAttempt;
use App\Models\User;

class AssignmentAttemptPolicy
{
    public function view(User $user, AssignmentAttempt $attempt): bool
    {
        if ($user->hasRole(RoleName::Administrator->value)) {
            return true;
        }

        if ($user->hasRole(RoleName::Child->value)) {
            return $attempt->child_id === $user->id;
        }

        if ($user->hasRole(RoleName::Parent->value)) {
            return $user->children()->whereKey($attempt->child_id)->exists();
        }

        return $this->teacherCanReview($user, $attempt);
    }

    public function start(User $user, object $allocation): bool
    {
        return $user->hasRole(RoleName::Child->value);
    }

    public function update(User $user, AssignmentAttempt $attempt): bool
    {
        return $user->hasRole(RoleName::Administrator->value)
            || ($user->hasRole(RoleName::Child->value) && $attempt->child_id === $user->id);
    }

    public function mark(User $user, AssignmentAttempt $attempt): bool
    {
        return $user->hasRole(RoleName::Administrator->value) || $this->teacherCanReview($user, $attempt);
    }

    public function complete(User $user, AssignmentAttempt $attempt): bool
    {
        return $this->mark($user, $attempt);
    }

    private function teacherCanReview(User $user, AssignmentAttempt $attempt): bool
    {
        if (! $user->hasRole(RoleName::Teacher->value)) {
            return false;
        }

        $classIds = $user->teachingClasses()->pluck('classes.id')->all();
        $allocation = $attempt->allocation()->with(['group', 'child.enrolledClasses'])->first();

        if ($allocation?->class_id && in_array((int) $allocation->class_id, $classIds, true)) {
            return true;
        }

        if ($allocation?->group_id && $allocation?->group?->class_id && in_array((int) $allocation->group->class_id, $classIds, true)) {
            return true;
        }

        return $allocation?->child?->enrolledClasses?->contains(fn ($class) => in_array((int) $class->id, $classIds, true)) ?? false;
    }
}
