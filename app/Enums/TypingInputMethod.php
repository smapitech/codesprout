<?php

namespace App\Enums;

enum TypingInputMethod: string
{
    case PhysicalKeyboard = 'physical_keyboard';
    case NativeTouchKeyboard = 'native_touch_keyboard';
    case OnScreenKeyboard = 'on_screen_keyboard';
    case AssistiveInput = 'assistive_input';
    case Unknown = 'unknown';

    public static function values(): array
    {
        return array_map(static fn (self $method): string => $method->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::PhysicalKeyboard => 'Physical keyboard',
            self::NativeTouchKeyboard => 'Touch keyboard',
            self::OnScreenKeyboard => 'On-screen keyboard',
            self::AssistiveInput => 'Assistive input',
            self::Unknown => 'Not sure yet',
        };
    }
}
