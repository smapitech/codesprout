<?php

namespace App\Enums;

enum GameType: string
{
    case ComputerPartIdentification = 'computer_part_identification';
    case ComputerPartMatching = 'computer_part_matching';
    case SingleClickTarget = 'single_click_target';
    case DoubleClickPractice = 'double_click_practice';
    case DragAndDrop = 'drag_and_drop';
    case ScrollAdventure = 'scroll_adventure';
    case KeyboardKeyExplorer = 'keyboard_key_explorer';
    case FallingLetters = 'falling_letters';
    case ArrowKeyPath = 'arrow_key_path';

    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::ComputerPartIdentification => 'Computer-part identification',
            self::ComputerPartMatching => 'Computer-part matching',
            self::SingleClickTarget => 'Single-click target',
            self::DoubleClickPractice => 'Double-click practice',
            self::DragAndDrop => 'Drag and drop',
            self::ScrollAdventure => 'Scroll adventure',
            self::KeyboardKeyExplorer => 'Keyboard key explorer',
            self::FallingLetters => 'Falling letters',
            self::ArrowKeyPath => 'Arrow-key path',
        };
    }
}
