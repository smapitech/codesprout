<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\AssignmentAllocation;
use App\Models\LearnerGroup;
use App\Models\User;

class AssignmentAllocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            RoleName::Administrator->value,
            RoleName::Teacher->value,
            RoleName::Parent->value,
            RoleName::Child->value,
        ]);
    }

    public function view(User $user, AssignmentAllocation $allocation): bool
    {
        if ($user->hasRole(RoleName::Administrator->value)) {
            return true;
        }

        if ($user->hasRole(RoleName::Child->value)) {
            return $allocation->child_id === $user->id
                || $user->enrolledClasses()->whereKey($allocation->class_id)->exists()
                || $this->groupContainsChild($allocation, $user);
        }

        if ($user->hasRole(RoleName::Parent->value)) {
            return $allocation->child_id !== null && $user->children()->whereKey($allocation->child_id)->exists();
        }

        return $this->teacherMatchesAllocation($user, $allocation);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            RoleName::Administrator->value,
            RoleName::Teacher->value,
        ]);
    }

    public function update(User $user, AssignmentAllocation $allocation): bool
    {
        if ($user->hasRole(RoleName::Administrator->value)) {
            return true;
        }

        return $this->teacherMatchesAllocation($user, $allocation);
    }

    public function cancel(User $user, AssignmentAllocation $allocation): bool
    {
        return $this->update($user, $allocation);
    }

    public function close(User $user, AssignmentAllocation $allocation): bool
    {
        return $this->update($user, $allocation);
    }

    private function teacherMatchesAllocation(User $teacher, AssignmentAllocation $allocation): bool
    {
        $classIds = $teacher->teachingClasses()->pluck('classes.id')->all();

        if ($allocation->class_id && in_array((int) $allocation->class_id, $classIds, true)) {
            return true;
        }

        if ($allocation->group_id) {
            $group = LearnerGroup::query()->find($allocation->group_id);

            return $group && in_array((int) $group->class_id, $classIds, true);
        }

        if ($allocation->child_id) {
            return $teacher->teachingClasses()->whereHas('learners', fn ($query) => $query->whereKey($allocation->child_id))->exists();
        }

        return false;
    }

    private function groupContainsChild(AssignmentAllocation $allocation, User $child): bool
    {
        if (! $allocation->group_id) {
            return false;
        }

        return LearnerGroup::query()
            ->whereKey($allocation->group_id)
            ->whereHas('members', fn ($query) => $query->where('child_id', $child->id))
            ->exists();
    }
}
