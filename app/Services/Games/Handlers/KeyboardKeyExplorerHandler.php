<?php

namespace App\Services\Games\Handlers;

use App\Enums\GameType;
use App\Models\GameSession;

class KeyboardKeyExplorerHandler extends AbstractGameHandler
{
    public function type(): GameType
    {
        return GameType::KeyboardKeyExplorer;
    }

    public function supportedInputMethods(): array
    {
        return ['keyboard', 'touch', 'mouse'];
    }

    public function validateAction(GameSession $session, array $payload): array
    {
        $validated = parent::validateAction($session, $payload);
        $validated['response']['key'] = $this->normaliseKey((string) ($validated['response']['key'] ?? ''));

        return $validated;
    }

    protected function expectedFor(mixed $item): mixed
    {
        return $this->normaliseKey((string) parent::expectedFor($item));
    }

    protected function rules(): array
    {
        return [
            'keys' => ['required', 'array', 'min:1'],
            'keys.*.key' => ['required', 'string', 'max:40'],
            'keys.*.name' => ['required', 'string', 'max:80'],
            'keys.*.purpose' => ['nullable', 'string', 'max:255'],
            'round_count' => ['nullable', 'integer', 'min:1', 'max:30'],
        ];
    }
}
