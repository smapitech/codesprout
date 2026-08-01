<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\ParentChildRelationship;
use App\Models\User;

class ParentChildRelationshipPolicy
{
    public function view(User $user, ParentChildRelationship $relationship): bool
    {
        if ($user->hasRole(RoleName::Parent->value)) {
            return $user->id === $relationship->parent_user_id;
        }

        if ($user->hasRole(RoleName::Child->value)) {
            return $user->id === $relationship->child_user_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleName::Parent->value);
    }
}
