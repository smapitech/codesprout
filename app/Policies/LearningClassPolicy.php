<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\LearningClass;
use App\Models\User;

class LearningClassPolicy
{
    public function view(User $user, LearningClass $class): bool
    {
        return $user->hasRole(RoleName::Teacher->value)
            && $user->teachingClasses()->whereKey($class->id)->exists();
    }
}
