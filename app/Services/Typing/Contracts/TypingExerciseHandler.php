<?php

namespace App\Services\Typing\Contracts;

use App\Models\TypingExerciseVersion;
use App\Models\TypingSession;

interface TypingExerciseHandler
{
    public function type(): string;

    /**
     * @param  array<string, mixed>  $configuration
     * @param  array<int, array<string, mixed>>  $items
     */
    public function validateConfiguration(array $configuration, array $items): void;

    /**
     * @return array<string, mixed>
     */
    public function prepareSession(TypingExerciseVersion $version): array;

    /**
     * @return array<string, mixed>
     */
    public function learnerPayload(TypingSession $session): array;

    /**
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>
     */
    public function validateInputEvent(TypingSession $session, array $event): array;

    public function requiresManualReview(TypingSession $session): bool;
}
