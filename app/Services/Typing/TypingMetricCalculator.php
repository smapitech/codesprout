<?php

namespace App\Services\Typing;

use App\Enums\TypingValidityStatus;
use App\Models\TypingInputEvent;
use App\Models\TypingSession;
use Illuminate\Support\Collection;

class TypingMetricCalculator
{
    /**
     * @return array<string, mixed>
     */
    public function calculate(TypingSession $session): array
    {
        $session->loadMissing(['exerciseVersion.contentItems', 'inputEvents']);
        $expected = $this->expectedText($session);
        $finalText = $this->finalText($session->inputEvents);
        $events = $session->inputEvents;
        $inputEvents = $events->whereIn('event_type', ['input', 'paste', 'assistive_input'])->values();
        $firstAttempts = $this->firstAttempts($inputEvents);
        $expectedLength = mb_strlen($expected);
        $editDistance = $this->editDistance($this->normaliseExpected($session, $expected), $this->normaliseExpected($session, $finalText));
        $firstTotal = max(1, $firstAttempts->count());
        $correctFirst = $firstAttempts->filter(fn (TypingInputEvent $event): bool => $this->charactersMatch($session, $event->expected_character, $event->entered_character))->count();
        $incorrectFirst = $firstAttempts->count() - $correctFirst;
        $finalAccuracy = max(0, $expectedLength - $editDistance) / max(1, $expectedLength) * 100;
        $activeMs = max((int) $session->active_duration_ms, (int) ($events->max('elapsed_offset_ms') ?? 0));
        $enteredCount = mb_strlen($finalText);
        $speed = $this->speed($enteredCount, $activeMs);
        $classifications = $this->classifyErrors($expected, $finalText, (bool) ($session->exerciseVersion->case_sensitive === 'case_sensitive'));
        $validity = $this->validity($session, $events, $activeMs, $enteredCount);

        return array_merge([
            'child_id' => $session->child_id,
            'typing_exercise_version_id' => $session->typing_exercise_version_id,
            'expected_character_count' => $expectedLength,
            'entered_character_count' => $enteredCount,
            'total_keystrokes' => $events->whereIn('event_type', ['input', 'backspace', 'paste', 'assistive_input'])->count(),
            'correct_first_attempts' => $correctFirst,
            'incorrect_first_attempts' => $incorrectFirst,
            'corrected_errors' => $this->correctedErrors($session, $firstAttempts, $finalText),
            'uncorrected_errors' => max(0, $editDistance),
            'omitted_characters' => max(0, $expectedLength - $enteredCount),
            'extra_characters' => max(0, $enteredCount - $expectedLength),
            'backspace_count' => $events->where('event_type', 'backspace')->count(),
            'assistance_count' => $events->whereIn('event_type', ['assistive_input', 'paste'])->count(),
            'prompt_replay_count' => $events->where('event_type', 'prompt_replay')->count(),
            'active_duration_ms' => $activeMs,
            'first_attempt_accuracy' => round(($correctFirst / $firstTotal) * 100, 2),
            'final_text_accuracy' => round($finalAccuracy, 2),
            'completion_percentage' => round($expectedLength === 0 ? 0 : min(100, ($enteredCount / max(1, $expectedLength)) * 100), 2),
            'validity_status' => $validity,
            'completed_at' => now(),
            'calculation_version' => 'typing-metrics-v1',
            'metadata' => [
                'final_text_preview' => mb_substr($finalText, 0, 30),
                'input_method' => $session->input_method->value,
                'keyboard_layout' => $session->keyboard_layout,
                'speed_meaningful' => $speed['characters_per_minute'] !== null,
            ],
        ], $classifications, $speed, [
            'result_checksum' => hash('sha256', json_encode([$session->uuid, $expected, $finalText, $activeMs, $events->pluck('sequence_number')->all()], JSON_THROW_ON_ERROR)),
        ]);
    }

    public function expectedText(TypingSession $session): string
    {
        $items = $session->exerciseVersion->contentItems->where('is_active', true)->values();

        return $items->pluck('expected_text')->implode("\n");
    }

    /**
     * @param  Collection<int, TypingInputEvent>  $events
     */
    public function finalText(Collection $events): string
    {
        $characters = [];

        foreach ($events->sortBy('sequence_number') as $event) {
            if ($event->event_type === 'backspace') {
                array_pop($characters);

                continue;
            }

            if (in_array($event->event_type, ['input', 'paste', 'assistive_input'], true)) {
                foreach (preg_split('//u', (string) $event->entered_character, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $char) {
                    $characters[] = $char;
                }
            }
        }

        return implode('', $characters);
    }

    private function normaliseExpected(TypingSession $session, string $value): string
    {
        return $session->exerciseVersion->case_sensitive === 'case_sensitive' ? $value : mb_strtolower($value);
    }

    private function charactersMatch(TypingSession $session, ?string $expected, ?string $entered): bool
    {
        if ($expected === null || $entered === null) {
            return false;
        }

        return $this->normaliseExpected($session, $expected) === $this->normaliseExpected($session, $entered);
    }

    /**
     * @param  Collection<int, TypingInputEvent>  $events
     * @return Collection<int, TypingInputEvent>
     */
    private function firstAttempts(Collection $events): Collection
    {
        return $events
            ->filter(fn (TypingInputEvent $event): bool => $event->character_position !== null)
            ->sortBy('sequence_number')
            ->unique('character_position')
            ->values();
    }

    /**
     * @param  Collection<int, TypingInputEvent>  $firstAttempts
     */
    private function correctedErrors(TypingSession $session, Collection $firstAttempts, string $finalText): int
    {
        $corrected = 0;
        foreach ($firstAttempts as $event) {
            if ($this->charactersMatch($session, $event->expected_character, $event->entered_character)) {
                continue;
            }

            $position = (int) $event->character_position;
            if (mb_substr($finalText, $position, 1) === (string) $event->expected_character) {
                $corrected++;
            }
        }

        return $corrected;
    }

    /**
     * @return array{characters_per_minute: float|null, gross_words_per_minute: float|null, adjusted_words_per_minute: float|null}
     */
    private function speed(int $enteredCount, int $activeMs): array
    {
        if ($activeMs < 15_000 || $enteredCount < 5) {
            return [
                'characters_per_minute' => null,
                'gross_words_per_minute' => null,
                'adjusted_words_per_minute' => null,
            ];
        }

        $minutes = $activeMs / 60_000;
        $cpm = $enteredCount / max(0.001, $minutes);

        return [
            'characters_per_minute' => round($cpm, 2),
            'gross_words_per_minute' => round(($enteredCount / 5) / max(0.001, $minutes), 2),
            'adjusted_words_per_minute' => round(($enteredCount / 5) / max(0.001, $minutes), 2),
        ];
    }

    /**
     * @return array{case_errors: int, spacing_errors: int, punctuation_errors: int}
     */
    private function classifyErrors(string $expected, string $finalText, bool $caseSensitive): array
    {
        $case = 0;
        $spacing = 0;
        $punctuation = 0;
        $length = min(mb_strlen($expected), mb_strlen($finalText));

        for ($i = 0; $i < $length; $i++) {
            $expectedChar = mb_substr($expected, $i, 1);
            $actualChar = mb_substr($finalText, $i, 1);

            if ($expectedChar === $actualChar) {
                continue;
            }

            if ($caseSensitive && mb_strtolower($expectedChar) === mb_strtolower($actualChar)) {
                $case++;
            }

            if (trim($expectedChar) === '' || trim($actualChar) === '') {
                $spacing++;
            }

            if (preg_match('/[.,?!:;\'"()\-]/u', $expectedChar.$actualChar) === 1) {
                $punctuation++;
            }
        }

        return [
            'case_errors' => $case,
            'spacing_errors' => $spacing,
            'punctuation_errors' => $punctuation,
        ];
    }

    private function validity(TypingSession $session, Collection $events, int $activeMs, int $enteredCount): string
    {
        if (($session->state['paste_detected'] ?? false) === true || $events->where('event_type', 'paste')->isNotEmpty()) {
            return TypingValidityStatus::NeedsReview->value;
        }

        if ($activeMs < 1_000 || $enteredCount === 0) {
            return TypingValidityStatus::InsufficientData->value;
        }

        return TypingValidityStatus::Valid->value;
    }

    private function editDistance(string $expected, string $actual): int
    {
        if (strlen($expected) === mb_strlen($expected) && strlen($actual) === mb_strlen($actual)) {
            return levenshtein($expected, $actual);
        }

        $a = preg_split('//u', $expected, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $b = preg_split('//u', $actual, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $matrix = [];
        for ($i = 0; $i <= count($a); $i++) {
            $matrix[$i][0] = $i;
        }
        for ($j = 0; $j <= count($b); $j++) {
            $matrix[0][$j] = $j;
        }
        for ($i = 1; $i <= count($a); $i++) {
            for ($j = 1; $j <= count($b); $j++) {
                $cost = $a[$i - 1] === $b[$j - 1] ? 0 : 1;
                $matrix[$i][$j] = min($matrix[$i - 1][$j] + 1, $matrix[$i][$j - 1] + 1, $matrix[$i - 1][$j - 1] + $cost);
            }
        }

        return $matrix[count($a)][count($b)];
    }
}
