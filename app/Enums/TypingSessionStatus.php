<?php

namespace App\Enums;

enum TypingSessionStatus: string
{
    case Created = 'created';
    case Ready = 'ready';
    case InProgress = 'in_progress';
    case Paused = 'paused';
    case Resumed = 'resumed';
    case Completed = 'completed';
    case Submitted = 'submitted';
    case AwaitingReview = 'awaiting_review';
    case Invalidated = 'invalidated';
    case Abandoned = 'abandoned';
    case Expired = 'expired';

    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Ready => 'Ready',
            self::InProgress => 'In progress',
            self::Paused => 'Paused',
            self::Resumed => 'Resumed',
            self::Completed => 'Completed',
            self::Submitted => 'Submitted',
            self::AwaitingReview => 'Awaiting review',
            self::Invalidated => 'Needs teacher review',
            self::Abandoned => 'Left unfinished',
            self::Expired => 'Expired',
        };
    }
}
