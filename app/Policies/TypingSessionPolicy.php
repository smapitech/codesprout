<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\LearningClass;
use App\Models\TypingSession;
use App\Models\User;

class TypingSessionPolicy
{
    public function view(User $user, TypingSession $session): bool
    {
        if ($session->child_id) {
            if ($user->id === $session->child_id) {
                return true;
            }

            if ($user->hasRole(RoleName::Parent->value)) {
                return $user->children()->where('users.id', $session->child_id)->exists();
            }

            if ($user->hasRole(RoleName::Teacher->value)) {
                return LearningClass::query()
                    ->whereHas('teachers', fn ($query) => $query->where('users.id', $user->id))
                    ->whereHas('learners', fn ($query) => $query->where('users.id', $session->child_id))
                    ->exists();
            }

            return $user->hasRole(RoleName::Administrator->value);
        }

        return $session->preview_actor_id === $user->id || $user->hasRole(RoleName::Administrator->value);
    }

    public function manage(User $user, TypingSession $session): bool
    {
        return $session->child_id === $user->id || $session->preview_actor_id === $user->id;
    }
}
