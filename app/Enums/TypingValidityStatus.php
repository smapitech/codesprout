<?php

namespace App\Enums;

enum TypingValidityStatus: string
{
    case Valid = 'valid';
    case NeedsReview = 'needs_review';
    case Invalidated = 'invalidated';
    case InsufficientData = 'insufficient_data';

    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
