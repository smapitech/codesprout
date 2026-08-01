<?php

namespace App\Services\Games\Handlers;

use App\Enums\GameType;

class FallingLettersHandler extends KeyboardKeyExplorerHandler
{
    public function type(): GameType
    {
        return GameType::FallingLetters;
    }
}
