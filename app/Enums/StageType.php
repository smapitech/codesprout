<?php

namespace App\Enums;

enum StageType: string
{
    case Introduction = 'introduction';
    case Demonstration = 'demonstration';
    case GuidedPractice = 'guided_practice';
    case GameMission = 'game_mission';
    case IndependentPractice = 'independent_practice';
    case Review = 'review';
    case Assessment = 'assessment';
    case Project = 'project';

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
            self::Introduction => 'Introduction',
            self::Demonstration => 'Demonstration',
            self::GuidedPractice => 'Guided practice',
            self::GameMission => 'Game mission',
            self::IndependentPractice => 'Independent practice',
            self::Review => 'Review',
            self::Assessment => 'Assessment',
            self::Project => 'Project',
        };
    }
}
