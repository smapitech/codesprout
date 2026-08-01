<?php

namespace App\Enums;

enum AssignmentType: string
{
    case Mission = 'mission';
    case Practice = 'practice';
    case Assessment = 'assessment';
    case Project = 'project';
    case Observation = 'observation';
    case Library = 'library';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
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

    public function label(): string
    {
        return match ($this) {
            self::Mission => 'Mission',
            self::Practice => 'Practice',
            self::Assessment => 'Assessment',
            self::Project => 'Project',
            self::Observation => 'Observation',
            self::Library => 'Library',
        };
    }
}
