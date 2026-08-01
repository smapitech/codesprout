<?php

namespace App\Enums;

enum TypingCorrectionPolicy: string
{
    case Allowed = 'allowed';
    case Disabled = 'disabled';
    case Review = 'review';

    public static function values(): array
    {
        return array_map(static fn (self $policy): string => $policy->value, self::cases());
    }
}
