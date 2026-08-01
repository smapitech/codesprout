<?php

namespace App\Enums;

enum HtmlValidationStatus: string
{
    case Valid = 'valid';
    case NeedsChanges = 'needs_changes';
    case Unsafe = 'unsafe';
    case NeedsReview = 'needs_review';

    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
