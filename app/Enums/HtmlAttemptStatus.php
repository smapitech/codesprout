<?php

namespace App\Enums;

enum HtmlAttemptStatus: string
{
    case InProgress = 'in_progress';
    case Paused = 'paused';
    case Completed = 'completed';
    case Submitted = 'submitted';
    case AwaitingReview = 'awaiting_review';
    case Invalidated = 'invalidated';
    case Abandoned = 'abandoned';
    case Expired = 'expired';
    case Preview = 'preview';

    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
