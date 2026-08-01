<?php

namespace App\Services\Games\Handlers;

use App\Enums\GameType;

class ComputerPartMatchingHandler extends AbstractGameHandler
{
    public function type(): GameType
    {
        return GameType::ComputerPartMatching;
    }

    public function supportedInputMethods(): array
    {
        return ['mouse', 'touch', 'keyboard'];
    }

    protected function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:2'],
            'items.*.name' => ['required', 'string', 'max:80'],
            'items.*.value' => ['required', 'string', 'max:80'],
            'items.*.expected' => ['required', 'string', 'max:120'],
            'round_count' => ['nullable', 'integer', 'min:1', 'max:12'],
        ];
    }
}
