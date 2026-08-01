<?php

namespace App\Enums;

enum GameCompletionStatus: string
{
    case Completed = 'completed';
    case Partial = 'partial';
    case NeedsPractice = 'needs_practice';

    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
