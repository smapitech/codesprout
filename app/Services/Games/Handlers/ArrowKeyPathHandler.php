<?php

namespace App\Services\Games\Handlers;

use App\Enums\GameType;
use App\Models\GameSession;

class ArrowKeyPathHandler extends AbstractGameHandler
{
    public function type(): GameType
    {
        return GameType::ArrowKeyPath;
    }

    public function supportedInputMethods(): array
    {
        return ['keyboard', 'touch'];
    }

    public function validateAction(GameSession $session, array $payload): array
    {
        $validated = parent::validateAction($session, $payload);
        $validated['response']['move'] = $this->normaliseKey((string) ($validated['response']['move'] ?? ''));

        return $validated;
    }

    protected function expectedFor(mixed $item): mixed
    {
        return $this->normaliseKey((string) parent::expectedFor($item));
    }

    protected function rules(): array
    {
        return [
            'path' => ['required', 'array', 'min:1', 'max:20'],
            'path.*.key' => ['required', 'string', 'max:40'],
            'path.*.name' => ['nullable', 'string', 'max:80'],
            'round_count' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }
}
