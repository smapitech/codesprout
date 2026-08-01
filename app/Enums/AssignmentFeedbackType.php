<?php

namespace App\Enums;

enum AssignmentFeedbackType: string
{
    case Encouragement = 'encouragement';
    case Correction = 'correction';
    case Achievement = 'achievement';
    case RetryGuidance = 'retry_guidance';
    case General = 'general';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Encouragement => 'Encouragement',
            self::Correction => 'Correction',
            self::Achievement => 'Achievement',
            self::RetryGuidance => 'Retry guidance',
            self::General => 'General',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            self::cases(),
        );
    }
}
