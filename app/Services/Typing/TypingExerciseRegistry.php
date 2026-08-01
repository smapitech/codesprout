<?php

namespace App\Services\Typing;

use App\Enums\TypingExerciseType;
use App\Models\TypingExerciseVersion;
use App\Services\Typing\Contracts\TypingExerciseHandler;
use App\Services\Typing\Handlers\KeyDiscoveryTypingHandler;
use App\Services\Typing\Handlers\TextTypingHandler;
use InvalidArgumentException;

class TypingExerciseRegistry
{
    /**
     * @var array<string, TypingExerciseHandler>
     */
    private array $handlers = [];

    public function __construct(TypingContentValidator $validator)
    {
        $textHandler = new TextTypingHandler($validator);

        $this->register(new KeyDiscoveryTypingHandler($validator));
        foreach ([
            TypingExerciseType::LetterPractice,
            TypingExerciseType::LetterSequence,
            TypingExerciseType::WordTyping,
            TypingExerciseType::SentenceTyping,
            TypingExerciseType::CapitalLetter,
            TypingExerciseType::NumberPractice,
            TypingExerciseType::SpecialKey,
            TypingExerciseType::Punctuation,
            TypingExerciseType::CopyTyping,
            TypingExerciseType::ListenAndType,
            TypingExerciseType::TimedPractice,
            TypingExerciseType::TypingAssessment,
        ] as $type) {
            $this->handlers[$type->value] = $textHandler->forType($type);
        }
    }

    public function register(TypingExerciseHandler $handler): void
    {
        $this->handlers[$handler->type()] = $handler;
    }

    public function handlerFor(TypingExerciseVersion|string $versionOrType): TypingExerciseHandler
    {
        $type = $versionOrType instanceof TypingExerciseVersion
            ? $versionOrType->exercise->exercise_type->value
            : $versionOrType;

        return $this->handlers[$type] ?? throw new InvalidArgumentException("No typing handler registered for [$type].");
    }
}
