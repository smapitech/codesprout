<?php

namespace App\Enums;

enum InteractionType: string
{
    case Watch = 'watch';
    case Listen = 'listen';
    case Find = 'find';
    case Select = 'select';
    case Match = 'match';
    case DragDrop = 'drag_drop';
    case OrderSequence = 'order_sequence';
    case Type = 'type';
    case FillBlank = 'fill_blank';
    case Debug = 'debug';
    case Build = 'build';
    case Explain = 'explain';

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
            self::Watch => 'Watch',
            self::Listen => 'Listen',
            self::Find => 'Find',
            self::Select => 'Select',
            self::Match => 'Match',
            self::DragDrop => 'Drag and drop',
            self::OrderSequence => 'Order sequence',
            self::Type => 'Type',
            self::FillBlank => 'Fill in the blank',
            self::Debug => 'Debug',
            self::Build => 'Build',
            self::Explain => 'Explain',
        };
    }
}
