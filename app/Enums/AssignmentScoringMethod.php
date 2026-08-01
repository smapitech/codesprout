<?php

namespace App\Enums;

enum AssignmentScoringMethod: string
{
    case LatestAttempt = 'latest_attempt';
    case HighestAttempt = 'highest_attempt';
    case FirstAttempt = 'first_attempt';
    case TeacherSelected = 'teacher_selected';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $method): string => $method->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::LatestAttempt => 'Latest attempt',
            self::HighestAttempt => 'Highest attempt',
            self::FirstAttempt => 'First attempt',
            self::TeacherSelected => 'Teacher selected',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $method): array => [
                'value' => $method->value,
                'label' => $method->label(),
            ],
            self::cases(),
        );
    }
}
