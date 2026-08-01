<?php

namespace App\Services\Games\Handlers;

use App\Enums\GameType;
use App\Models\GameSession;

class ComputerPartIdentificationHandler extends AbstractGameHandler
{
    public function type(): GameType
    {
        return GameType::ComputerPartIdentification;
    }

    public function supportedInputMethods(): array
    {
        return ['mouse', 'touch', 'keyboard'];
    }

    public function validateAction(GameSession $session, array $payload): array
    {
        $validated = parent::validateAction($session, $payload);
        $validated['response']['selected_part'] = (string) ($validated['response']['selected_part'] ?? '');

        return $validated;
    }

    protected function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:80'],
            'items.*.value' => ['required', 'string', 'max:80'],
            'items.*.purpose' => ['nullable', 'string', 'max:255'],
            'round_count' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }
}
