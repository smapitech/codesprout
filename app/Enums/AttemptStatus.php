<?php

namespace App\Enums;

enum AttemptStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case AwaitingReview = 'awaiting_review';
    case Marked = 'marked';
    case Returned = 'returned';
    case Completed = 'completed';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not started',
            self::InProgress => 'In progress',
            self::Submitted => 'Submitted',
            self::AwaitingReview => 'Awaiting review',
            self::Marked => 'Marked',
            self::Returned => 'Returned',
            self::Completed => 'Completed',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases(),
        );
    }
}
