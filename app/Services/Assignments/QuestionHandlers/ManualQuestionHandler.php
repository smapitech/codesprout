<?php

namespace App\Services\Assignments\QuestionHandlers;

use App\Enums\QuestionType;
use App\Models\AssignmentItem;
use App\Services\Assignments\Contracts\AssignmentQuestionHandler;

class ManualQuestionHandler extends AbstractQuestionHandler implements AssignmentQuestionHandler
{
    public function validateConfiguration(AssignmentItem $item): void
    {
        // Manual activities can be configured without strict automatic answer data.
    }

    public function validateResponse(AssignmentItem $item, mixed $response): void
    {
        $payload = $this->responseArray($response);

        if (! array_key_exists('text', $payload) && ! array_key_exists('value', $payload) && ! array_key_exists('notes', $payload)) {
            $this->fail(['response' => 'Please add your response.']);
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
            'requires_text' => true,
        ];
    }

    public function gradeResponse(AssignmentItem $item, mixed $response): array
    {
        return [
            'is_correct' => null,
            'score' => 0,
            'maximum_score' => $item->points,
            'feedback' => 'Your teacher will review this mission.',
            'manual_review' => true,
        ];
    }

    public function requiresManualReview(AssignmentItem $item): bool
    {
        return true;
    }
}
