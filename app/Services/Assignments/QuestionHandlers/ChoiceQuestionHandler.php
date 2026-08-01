<?php

namespace App\Services\Assignments\QuestionHandlers;

use App\Enums\QuestionType;
use App\Models\AssignmentItem;
use App\Services\Assignments\Contracts\AssignmentQuestionHandler;

class ChoiceQuestionHandler extends AbstractQuestionHandler implements AssignmentQuestionHandler
{
    public function validateConfiguration(AssignmentItem $item): void
    {
        $options = $item->options->values();

        if ($options->isEmpty()) {
            $this->fail(['options' => 'At least one answer option is required.']);
        }

        if ($options->where('is_correct', true)->isEmpty()) {
            $this->fail(['options' => 'At least one correct answer option is required.']);
        }
    }

    public function validateResponse(AssignmentItem $item, mixed $response): void
    {
        $payload = $this->responseArray($response);

        if (
            ! array_key_exists('selected_option_value', $payload)
            && ! array_key_exists('selected_option_values', $payload)
            && ! array_key_exists('selected_option_id', $payload)
            && ! array_key_exists('selected_option_ids', $payload)
        ) {
            $this->fail(['response' => 'Please choose an answer.']);
        }
    }

    public function transformForLearner(AssignmentItem $item): array
    {
        return [
            'title' => $item->title,
            'prompt_text' => $item->prompt_text,
            'image_path' => $item->image_path,
            'question_type' => $item->question_type instanceof QuestionType ? $item->question_type->value : (string) $item->question_type,
            'interaction_type' => $item->interaction_type?->value ?? (string) $item->interaction_type,
            'points' => $item->points,
            'is_required' => $item->is_required,
            'hint_text' => $item->hint_text,
            'options' => $this->learnerOptions($item),
            'answer_mode' => $this->configuration($item)['allow_multiple'] ?? false ? 'multiple' : 'single',
        ];
    }

    public function gradeResponse(AssignmentItem $item, mixed $response): array
    {
        $selectedValues = [];
        $payload = $this->responseArray($response);

        if (isset($payload['selected_option_values']) && is_array($payload['selected_option_values'])) {
            $selectedValues = array_values(array_map('strval', $payload['selected_option_values']));
        } elseif (isset($payload['selected_option_ids']) && is_array($payload['selected_option_ids'])) {
            $selectedValues = $item->options
                ->whereIn('id', array_map('intval', $payload['selected_option_ids']))
                ->pluck('option_value')
                ->filter()
                ->map(static fn ($value): string => (string) $value)
                ->values()
                ->all();
        } else {
            $value = $payload['selected_option_value'] ?? $payload['selected_option_id'] ?? null;

            if ($value !== null) {
                $selectedValues = [is_numeric($value)
                    ? (string) ($item->options->firstWhere('id', (int) $value)?->option_value ?? $value)
                    : (string) $value];
            }
        }

        $correctValues = $item->options
            ->where('is_correct', true)
            ->pluck('option_value')
            ->filter()
            ->map(static fn ($value): string => (string) $value)
            ->values()
            ->all();

        sort($selectedValues);
        sort($correctValues);

        $isCorrect = $selectedValues === $correctValues;

        return [
            'is_correct' => $isCorrect,
            'score' => $isCorrect ? $item->points : 0,
            'maximum_score' => $item->points,
            'feedback' => $isCorrect ? 'Great choice!' : 'Try another adventure answer.',
            'manual_review' => false,
        ];
    }

    public function requiresManualReview(AssignmentItem $item): bool
    {
        return false;
    }
}
