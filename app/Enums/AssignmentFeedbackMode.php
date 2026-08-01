<?php

namespace App\Enums;

enum AssignmentFeedbackMode: string
{
    case Immediate = 'immediate';
    case AfterSubmission = 'after_submission';
    case AfterDueDate = 'after_due_date';
    case TeacherRelease = 'teacher_release';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $mode): string => $mode->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Immediate => 'Immediate',
            self::AfterSubmission => 'After submission',
            self::AfterDueDate => 'After due date',
            self::TeacherRelease => 'Teacher release',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $mode): array => [
                'value' => $mode->value,
                'label' => $mode->label(),
            ],
            self::cases(),
        );
    }
}
