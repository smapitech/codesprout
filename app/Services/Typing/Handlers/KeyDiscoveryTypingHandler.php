<?php

namespace App\Services\Typing\Handlers;

use App\Enums\TypingExerciseType;
use App\Models\TypingSession;

class KeyDiscoveryTypingHandler extends TextTypingHandler
{
    public function type(): string
    {
        return TypingExerciseType::KeyDiscovery->value;
    }

    public function requiresManualReview(TypingSession $session): bool
    {
        return false;
    }
}
