<?php

namespace App\Services\Games\Contracts;

use App\Enums\GameDifficulty;
use App\Enums\GameType;
use App\Models\GameSession;
use App\Models\GameVersion;
use Illuminate\Validation\ValidationException;

interface GameHandler
{
    public function type(): GameType;

    /**
     * @throws ValidationException
     */
    public function validateConfiguration(array $configuration): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function generateRounds(GameVersion $version, GameDifficulty $difficulty): array;

    /**
     * @return array<string, mixed>
     */
    public function sessionPayload(GameSession $session): array;

    /**
     * @throws ValidationException
     */
    public function validateAction(GameSession $session, array $payload): array;

    /**
     * @return array<string, mixed>
     */
    public function calculatePerformance(GameSession $session): array;

    public function isComplete(GameSession $session): bool;

    public function feedbackFor(bool $correct): string;

    /**
     * @return array<int, string>
     */
    public function supportedInputMethods(): array;
}
