<?php

namespace App\Services\Assignments\QuestionHandlers;

use App\Enums\QuestionType;
use App\Models\AssignmentItem;
use App\Services\Assignments\Contracts\AssignmentQuestionHandler;

class TypingQuestionHandler extends AbstractQuestionHandler implements AssignmentQuestionHandler
{
    public function validateConfiguration(AssignmentItem $item): void
    {
        $settings = $this->gradingConfiguration($item);

        if (blank($settings['accepted_answers'] ?? null) || ! is_array($settings['accepted_answers'])) {
            $this->fail(['grading_configuration.accepted_answers' => 'At least one accepted answer is required.']);
        }
    }

    public function validateResponse(AssignmentItem $item, mixed $response): void
    {
        $payload = $this->responseArray($response);

        if (! array_key_exists('text', $payload) && ! array_key_exists('value', $payload)) {
            $this->fail(['response' => 'Please type your answer.']);
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
            'placeholder' => $this->gradingConfiguration($item)['placeholder'] ?? 'Type your answer here',
            'keyboard_mode' => $this->gradingConfiguration($item)['keyboard_mode'] ?? 'text',
        ];
    }

    public function gradeResponse(AssignmentItem $item, mixed $response): array
    {
        $settings = $this->gradingConfiguration($item);
        $payload = $this->responseArray($response);
        $raw = (string) ($payload['text'] ?? $payload['value'] ?? '');
        $normalised = $this->normaliseText($raw, $settings);
        $accepted = collect($settings['accepted_answers'] ?? [])
            ->map(fn ($answer): string => $this->normaliseText((string) $answer, $settings))
            ->filter()
            ->values()
            ->all();

        $isCorrect = in_array($normalised, $accepted, true);

        return [
            'is_correct' => $isCorrect,
            'score' => $isCorrect ? $item->points : 0,
            'maximum_score' => $item->points,
            'feedback' => $isCorrect ? 'Nice typing!' : 'Try that one more time.',
            'manual_review' => false,
        ];
    }

    public function requiresManualReview(AssignmentItem $item): bool
    {
        return false;
    }
}
