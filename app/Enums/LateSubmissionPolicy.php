<?php

namespace App\Enums;

enum LateSubmissionPolicy: string
{
    case Block = 'block';
    case Allow = 'allow';
    case AllowWithPenalty = 'allow_with_penalty';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $policy): string => $policy->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Block => 'Block',
            self::Allow => 'Allow',
            self::AllowWithPenalty => 'Allow with penalty',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $policy): array => [
                'value' => $policy->value,
                'label' => $policy->label(),
            ],
            self::cases(),
        );
    }
}
