<?php

namespace App\Enums;

enum DifficultyLevel: string
{
    case Introductory = 'introductory';
    case Easy = 'easy';
    case Developing = 'developing';
    case Independent = 'independent';
    case Challenge = 'challenge';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $level): string => $level->value, self::cases());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $level): array => [
                'value' => $level->value,
                'label' => $level->label(),
            ],
            self::cases(),
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::Introductory => 'Introductory',
            self::Easy => 'Easy',
            self::Developing => 'Developing',
            self::Independent => 'Independent',
            self::Challenge => 'Challenge',
        };
    }
}
