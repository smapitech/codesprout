<?php

namespace App\Enums;

enum GameSessionStatus: string
{
    case Ready = 'ready';
    case InProgress = 'in_progress';
    case Paused = 'paused';
    case Completed = 'completed';
    case Abandoned = 'abandoned';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
