<?php

namespace App\Enums;

enum TypingExerciseType: string
{
    case KeyDiscovery = 'key_discovery';
    case LetterPractice = 'letter_practice';
    case LetterSequence = 'letter_sequence';
    case WordTyping = 'word_typing';
    case SentenceTyping = 'sentence_typing';
    case CapitalLetter = 'capital_letter';
    case NumberPractice = 'number_practice';
    case SpecialKey = 'special_key';
    case Punctuation = 'punctuation';
    case CopyTyping = 'copy_typing';
    case ListenAndType = 'listen_and_type';
    case TimedPractice = 'timed_practice';
    case TypingAssessment = 'typing_assessment';

    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }

    public static function options(): array
    {
        return array_map(static fn (self $type): array => [
            'value' => $type->value,
            'label' => $type->label(),
        ], self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::KeyDiscovery => 'Key Discovery',
            self::LetterPractice => 'Letter Practice',
            self::LetterSequence => 'Letter-sequence Practice',
            self::WordTyping => 'Word Typing',
            self::SentenceTyping => 'Sentence Typing',
            self::CapitalLetter => 'Capital-letter Practice',
            self::NumberPractice => 'Number Practice',
            self::SpecialKey => 'Special-key Practice',
            self::Punctuation => 'Punctuation Practice',
            self::CopyTyping => 'Copy Typing',
            self::ListenAndType => 'Listen and Type',
            self::TimedPractice => 'Calm Timed Practice',
            self::TypingAssessment => 'Typing Assessment',
        };
    }
}
