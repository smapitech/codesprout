<?php

namespace App\Services\Assignments;

use App\Enums\QuestionType;
use App\Models\AssignmentItem;
use App\Services\Assignments\Contracts\AssignmentQuestionHandler;
use App\Services\Assignments\QuestionHandlers\ChoiceQuestionHandler;
use App\Services\Assignments\QuestionHandlers\ManualQuestionHandler;
use App\Services\Assignments\QuestionHandlers\MatchingQuestionHandler;
use App\Services\Assignments\QuestionHandlers\OrderingQuestionHandler;
use App\Services\Assignments\QuestionHandlers\TypingQuestionHandler;
use Illuminate\Validation\ValidationException;

class AssignmentQuestionHandlerRegistry
{
    public function __construct(
        private readonly ChoiceQuestionHandler $choiceHandler,
        private readonly MatchingQuestionHandler $matchingHandler,
        private readonly OrderingQuestionHandler $orderingHandler,
        private readonly TypingQuestionHandler $typingHandler,
        private readonly ManualQuestionHandler $manualHandler,
    ) {}

    public function handlerFor(AssignmentItem|QuestionType|string $item): AssignmentQuestionHandler
    {
        $type = $item instanceof AssignmentItem
            ? ($item->question_type instanceof QuestionType ? $item->question_type : $this->questionTypeFromValue((string) $item->question_type))
            : ($item instanceof QuestionType ? $item : $this->questionTypeFromValue((string) $item));

        return match ($type) {
            QuestionType::MultipleChoice,
            QuestionType::ImageChoice,
            QuestionType::TrueFalse,
            QuestionType::FindComputerPart,
            QuestionType::FindKeyboardKey,
            QuestionType::PressRequestedKey,
            QuestionType::MatchSymbolToName,
            QuestionType::DragSymbolIntoPosition,
            QuestionType::DragAndDrop => $this->choiceHandler,
            QuestionType::MatchItems,
            QuestionType::MatchOpeningAndClosingHtmlTags => $this->matchingHandler,
            QuestionType::OrderSequence,
            QuestionType::ArrangeCodeIntoCorrectOrder => $this->orderingHandler,
            QuestionType::TypeLetter,
            QuestionType::TypeWord,
            QuestionType::TypeSentence,
            QuestionType::FillMissingSymbol,
            QuestionType::BuildHtmlTag,
            QuestionType::FillMissingHtml,
            QuestionType::FillMissingJavascript,
            QuestionType::RepairSimpleCodeError => $this->typingHandler,
            QuestionType::ShortChildResponse,
            QuestionType::CreativeProject,
            QuestionType::TeacherObservation => $this->manualHandler,
        };
    }

    /**
     * @return array<int, array{value: string, label: string, manual_review: bool, auto_gradable: bool}>
     */
    public function metadata(): array
    {
        return array_map(static fn (QuestionType $type): array => [
            'value' => $type->value,
            'label' => $type->label(),
            'manual_review' => $type->isManualReview(),
            'auto_gradable' => $type->isAutoGradable(),
        ], QuestionType::cases());
    }

    public function validate(AssignmentItem $item): void
    {
        $this->handlerFor($item)->validateConfiguration($item);
    }

    /**
     * @return array<string, mixed>
     */
    public function transformForLearner(AssignmentItem $item): array
    {
        return $this->handlerFor($item)->transformForLearner($item);
    }

    /**
     * @return array{is_correct: ?bool, score: float|int, maximum_score: float|int, feedback: string, manual_review: bool}
     */
    public function gradeResponse(AssignmentItem $item, mixed $response): array
    {
        return $this->handlerFor($item)->gradeResponse($item, $response);
    }

    public function requiresManualReview(AssignmentItem $item): bool
    {
        return $this->handlerFor($item)->requiresManualReview($item);
    }

    /**
     * @throws ValidationException
     */
    private function questionTypeFromValue(string $value): QuestionType
    {
        try {
            return QuestionType::from($value);
        } catch (\ValueError) {
            throw ValidationException::withMessages([
                'question_type' => 'The question type is not supported.',
            ]);
        }
    }
}
