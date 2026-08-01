<?php

namespace App\Services\Assignments\QuestionHandlers;

use App\Enums\QuestionType;
use App\Models\AssignmentItem;
use App\Services\Assignments\Contracts\AssignmentQuestionHandler;

class MatchingQuestionHandler extends AbstractQuestionHandler implements AssignmentQuestionHandler
{
    public function validateConfiguration(AssignmentItem $item): void
    {
        if ($item->options->count() < 2) {
            $this->fail(['options' => 'At least two items are required for matching.']);
        }

        if ($item->options->contains(fn ($option) => blank($option->matching_key))) {
            $this->fail(['options' => 'Every matching option needs a matching key.']);
        }
    }

    public function validateResponse(AssignmentItem $item, mixed $response): void
    {
        $payload = $this->responseArray($response);

        if (! isset($payload['pairs']) || ! is_array($payload['pairs'])) {
            $this->fail(['response' => 'Please match the pairs.']);
        }
    }

    public function transformForLearner(AssignmentItem $item): array
    {
        $options = $this->learnerOptions($item);

        return [
            'title' => $item->title,
            'prompt_text' => $item->prompt_text,
            'question_type' => $item->question_type instanceof QuestionType ? $item->question_type->value : (string) $item->question_type,
            'interaction_type' => $item->interaction_type?->value ?? (string) $item->interaction_type,
            'points' => $item->points,
            'hint_text' => $item->hint_text,
            'left_items' => collect($options)->values()->all(),
            'right_items' => $item->options
                ->sortByDesc('display_order')
                ->values()
                ->map(static fn ($option): array => [
                    'id' => $option->id,
                    'text' => $option->matching_key,
                    'value' => $option->matching_key,
                    'display_order' => $option->display_order,
                ])
                ->all(),
        ];
    }

    public function gradeResponse(AssignmentItem $item, mixed $response): array
    {
        $payload = $this->responseArray($response);
        $pairs = is_array($payload['pairs'] ?? null) ? $payload['pairs'] : [];
        $correctMap = $item->options->mapWithKeys(static function ($option): array {
            return [(string) $option->option_value => (string) $option->matching_key];
        })->all();

        $correct = true;
        foreach ($correctMap as $left => $right) {
            if ((string) ($pairs[$left] ?? '') !== $right) {
                $correct = false;
                break;
            }
        }

        return [
            'is_correct' => $correct,
            'score' => $correct ? $item->points : 0,
            'maximum_score' => $item->points,
            'feedback' => $correct ? 'You matched them well!' : 'Let us try matching again.',
            'manual_review' => false,
        ];
    }

    public function requiresManualReview(AssignmentItem $item): bool
    {
        return false;
    }
}
