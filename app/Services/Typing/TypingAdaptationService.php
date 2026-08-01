<?php

namespace App\Services\Typing;

use App\Models\TypingResult;
use App\Models\User;

class TypingAdaptationService
{
    /**
     * @return array<string, mixed>
     */
    public function recommendationFor(User $child): array
    {
        $recent = TypingResult::query()
            ->where('child_id', $child->id)
            ->latest('completed_at')
            ->limit(5)
            ->get();

        if ($recent->count() < 3) {
            return [
                'action' => 'continue_supported_practice',
                'reason' => 'More calm practice evidence is needed before changing difficulty.',
            ];
        }

        $average = (float) $recent->avg('first_attempt_accuracy');

        if ($average < 70) {
            return [
                'action' => 'repeat_small_key_set',
                'reason' => 'Recent first-attempt accuracy suggests a short review set will help.',
            ];
        }

        if ($average >= 90) {
            return [
                'action' => 'introduce_one_new_key_or_word',
                'reason' => 'Accuracy has been strong across several validated sessions.',
            ];
        }

        return [
            'action' => 'maintain_current_level',
            'reason' => 'Accuracy is growing steadily; keep support consistent.',
        ];
    }
}
