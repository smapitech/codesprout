<?php

namespace App\Enums;

enum GameCategory: string
{
    case ComputerDiscovery = 'computer_discovery';
    case MouseControl = 'mouse_control';
    case KeyboardDiscovery = 'keyboard_discovery';

    public static function values(): array
    {
        return array_map(static fn (self $category): string => $category->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::ComputerDiscovery => 'Computer Discovery',
            self::MouseControl => 'Mouse Control',
            self::KeyboardDiscovery => 'Keyboard Discovery',
        };
    }
}
