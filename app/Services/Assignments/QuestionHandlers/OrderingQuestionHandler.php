<?php

namespace App\Services\Assignments\QuestionHandlers;

use App\Enums\QuestionType;
use App\Models\AssignmentItem;
use App\Services\Assignments\Contracts\AssignmentQuestionHandler;

class OrderingQuestionHandler extends AbstractQuestionHandler implements AssignmentQuestionHandler
{
    public function validateConfiguration(AssignmentItem $item): void
    {
        if ($item->options->count() < 2) {
            $this->fail(['options' => 'At least two steps are required for ordering.']);
        }
    }

    public function validateResponse(AssignmentItem $item, mixed $response): void
    {
        $payload = $this->responseArray($response);

        if (! isset($payload['order']) || ! is_array($payload['order'])) {
            $this->fail(['response' => 'Please put the items in order.']);
        }
    }

    public function transformForLearner(AssignmentItem $item): array
    {
        return [
            'title' => $item->title,
            'prompt_text' => $item->prompt_text,
            'question_type' => $item->question_type instanceof QuestionType ? $item->question_type->value : (string) $item->question_type,
            'interaction_type' => $item->interaction_type?->value ?? (string) $item->interaction_type,
            'points' => $item->points,
            'hint_text' => $item->hint_text,
            'items' => $this->learnerOptions($item),
        ];
    }

    public function gradeResponse(AssignmentItem $item, mixed $response): array
    {
        $payload = $this->responseArray($response);
        $givenOrder = array_map('strval', is_array($payload['order'] ?? null) ? $payload['order'] : []);
        $correctOrder = $item->options->sortBy('display_order')->pluck('option_value')->map(static fn ($value): string => (string) $value)->values()->all();

        $correct = $givenOrder === $correctOrder;

        return [
            'is_correct' => $correct,
            'score' => $correct ? $item->points : 0,
            'maximum_score' => $item->points,
            'feedback' => $correct ? 'That sequence looks right!' : 'Let us arrange the steps again.',
            'manual_review' => false,
        ];
    }

    public function requiresManualReview(AssignmentItem $item): bool
    {
        return false;
    }
}
