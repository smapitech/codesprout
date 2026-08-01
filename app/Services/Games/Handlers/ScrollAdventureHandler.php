<?php

namespace App\Services\Games\Handlers;

use App\Enums\GameType;

class ScrollAdventureHandler extends AbstractGameHandler
{
    public function type(): GameType
    {
        return GameType::ScrollAdventure;
    }

    public function supportedInputMethods(): array
    {
        return ['mouse', 'touch', 'keyboard'];
    }

    protected function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:80'],
            'items.*.value' => ['required', 'string', 'max:80'],
            'round_count' => ['nullable', 'integer', 'min:1', 'max:12'],
        ];
    }
}
