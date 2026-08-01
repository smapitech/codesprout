<?php

namespace App\Services\Games\Handlers;

use App\Enums\GameType;

class DragAndDropHandler extends ComputerPartMatchingHandler
{
    public function type(): GameType
    {
        return GameType::DragAndDrop;
    }
}
