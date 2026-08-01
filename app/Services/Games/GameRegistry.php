<?php

namespace App\Services\Games;

use App\Enums\GameType;
use App\Models\GameVersion;
use App\Services\Games\Contracts\GameHandler;
use App\Services\Games\Handlers\ArrowKeyPathHandler;
use App\Services\Games\Handlers\ComputerPartIdentificationHandler;
use App\Services\Games\Handlers\ComputerPartMatchingHandler;
use App\Services\Games\Handlers\DoubleClickPracticeHandler;
use App\Services\Games\Handlers\DragAndDropHandler;
use App\Services\Games\Handlers\FallingLettersHandler;
use App\Services\Games\Handlers\KeyboardKeyExplorerHandler;
use App\Services\Games\Handlers\ScrollAdventureHandler;
use App\Services\Games\Handlers\SingleClickTargetHandler;
use Illuminate\Validation\ValidationException;

class GameRegistry
{
    /** @var array<string, GameHandler> */
    private array $handlers;

    public function __construct(
        ComputerPartIdentificationHandler $computerPartIdentification,
        ComputerPartMatchingHandler $computerPartMatching,
        SingleClickTargetHandler $singleClickTarget,
        DoubleClickPracticeHandler $doubleClickPractice,
        DragAndDropHandler $dragAndDrop,
        ScrollAdventureHandler $scrollAdventure,
        KeyboardKeyExplorerHandler $keyboardKeyExplorer,
        FallingLettersHandler $fallingLetters,
        ArrowKeyPathHandler $arrowKeyPath,
    ) {
        $this->handlers = collect([
            $computerPartIdentification,
            $computerPartMatching,
            $singleClickTarget,
            $doubleClickPractice,
            $dragAndDrop,
            $scrollAdventure,
            $keyboardKeyExplorer,
            $fallingLetters,
            $arrowKeyPath,
        ])->mapWithKeys(fn (GameHandler $handler): array => [$handler->type()->value => $handler])->all();
    }

    public function handlerFor(GameVersion|GameType|string $target): GameHandler
    {
        $type = $target instanceof GameVersion
            ? $target->definition->game_type
            : ($target instanceof GameType ? $target : GameType::tryFrom((string) $target));

        if (! $type || ! isset($this->handlers[$type->value])) {
            throw ValidationException::withMessages(['game_type' => 'This game type is not supported.']);
        }

        return $this->handlers[$type->value];
    }
}
