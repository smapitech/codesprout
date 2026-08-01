<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\ChildProfile;
use App\Models\User;

class ChildProfilePolicy
{
    public function view(User $user, ChildProfile $profile): bool
    {
        if ($user->hasRole(RoleName::Parent->value)) {
            return $user->children()->whereKey($profile->user_id)->exists();
        }

        if ($user->hasRole(RoleName::Teacher->value)) {
            return $user->teachingClasses()
                ->whereHas('learners', fn ($query) => $query->whereKey($profile->user_id))
                ->exists();
        }

        return $user->id === $profile->user_id;
    }
}
