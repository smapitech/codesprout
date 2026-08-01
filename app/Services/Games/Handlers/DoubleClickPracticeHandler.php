<?php

namespace App\Services\Games\Handlers;

use App\Enums\GameDifficulty;
use App\Enums\GameType;
use App\Models\GameSession;
use App\Models\GameVersion;

class DoubleClickPracticeHandler extends AbstractGameHandler
{
    public function type(): GameType
    {
        return GameType::DoubleClickPractice;
    }

    public function supportedInputMethods(): array
    {
        return ['mouse', 'touch'];
    }

    public function validateAction(GameSession $session, array $payload): array
    {
        $validated = parent::validateAction($session, $payload);
        $validated['response']['interval_ms'] = (int) ($validated['response']['interval_ms'] ?? 999999);

        return $validated;
    }

    public function generateRounds(GameVersion $version, GameDifficulty $difficulty): array
    {
        $rounds = parent::generateRounds($version, $difficulty);
        $tolerance = (int) (($version->difficulty_configuration[$difficulty->value]['double_click_tolerance_ms'] ?? 900));

        return array_map(static function (array $round) use ($tolerance): array {
            $round['expected'] = $tolerance;
            $round['safe']['tolerance_label'] = 'Two gentle taps close together';

            return $round;
        }, $rounds);
    }

    protected function rules(): array
    {
        return [
            'targets' => ['required', 'array', 'min:1'],
            'targets.*.label' => ['required', 'string', 'max:80'],
            'targets.*.value' => ['required', 'string', 'max:80'],
            'round_count' => ['nullable', 'integer', 'min:1', 'max:12'],
        ];
    }
}
