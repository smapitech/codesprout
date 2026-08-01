<?php

namespace App\Services\Assignments\QuestionHandlers;

use App\Models\AssignmentItem;
use Illuminate\Validation\ValidationException;

abstract class AbstractQuestionHandler
{
    /**
     * @return array<string, mixed>
     */
    protected function configuration(AssignmentItem $item): array
    {
        return $item->configuration ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function gradingConfiguration(AssignmentItem $item): array
    {
        return $item->grading_configuration ?? [];
    }

    protected function fail(array $messages): never
    {
        throw ValidationException::withMessages($messages);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function learnerOptions(AssignmentItem $item): array
    {
        return $item->options
            ->sortBy('display_order')
            ->values()
            ->map(static fn ($option): array => [
                'id' => $option->id,
                'text' => $option->option_text,
                'image' => $option->image_path,
                'value' => $option->option_value,
                'display_order' => $option->display_order,
            ])
            ->all();
    }

    protected function normaliseText(string $value, array $settings): string
    {
        $trim = $settings['trim_spaces'] ?? true;
        $caseSensitive = $settings['case_sensitive'] ?? false;
        $requireCapitalisation = $settings['require_capitalisation'] ?? false;
        $requirePunctuation = $settings['require_punctuation'] ?? false;

        if ($trim) {
            $value = trim($value);
        }

        if (! $caseSensitive) {
            $value = mb_strtolower($value);
        }

        if (! $requireCapitalisation) {
            $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        }

        if (! $requirePunctuation) {
            $value = preg_replace('/[[:punct:]]+$/u', '', $value) ?? $value;
        }

        return $value;
    }

    protected function responseText(mixed $response): string
    {
        if (is_array($response)) {
            return (string) ($response['text'] ?? $response['value'] ?? '');
        }

        return (string) $response;
    }

    protected function responseArray(mixed $response): array
    {
        return is_array($response) ? $response : [];
    }
}
