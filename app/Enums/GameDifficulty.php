<?php

namespace App\Enums;

enum GameDifficulty: string
{
    case ExtraSlow = 'extra_slow';
    case Slow = 'slow';
    case Normal = 'normal';

    public static function values(): array
    {
        return array_map(static fn (self $difficulty): string => $difficulty->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::ExtraSlow => 'Extra Slow',
            self::Slow => 'Slow',
            self::Normal => 'Normal',
        };
    }
}
