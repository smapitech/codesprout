<?php

namespace App\Services\Games\Handlers;

use App\Enums\GameType;

class SingleClickTargetHandler extends AbstractGameHandler
{
    public function type(): GameType
    {
        return GameType::SingleClickTarget;
    }

    public function supportedInputMethods(): array
    {
        return ['mouse', 'touch', 'keyboard'];
    }

    protected function rules(): array
    {
        return [
            'targets' => ['required', 'array', 'min:1'],
            'targets.*.label' => ['required', 'string', 'max:80'],
            'targets.*.value' => ['required', 'string', 'max:80'],
            'round_count' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }
}
