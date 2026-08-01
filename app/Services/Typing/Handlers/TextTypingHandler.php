<?php

namespace App\Services\Typing\Handlers;

use App\Enums\TypingExerciseType;
use App\Models\TypingExerciseVersion;
use App\Models\TypingSession;
use App\Services\Typing\Contracts\TypingExerciseHandler;
use App\Services\Typing\TypingContentValidator;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class TextTypingHandler implements TypingExerciseHandler
{
    public function __construct(
        private readonly TypingContentValidator $validator,
        private readonly ?TypingExerciseType $exerciseType = null,
    ) {}

    public function forType(TypingExerciseType $type): self
    {
        return new self($this->validator, $type);
    }

    public function type(): string
    {
        return ($this->exerciseType ?? TypingExerciseType::WordTyping)->value;
    }

    public function validateConfiguration(array $configuration, array $items): void
    {
        $this->validator->rejectUnsafeText((string) ($configuration['feedback_message'] ?? ''), 'content_configuration.feedback_message');
        $this->validator->validateItems($items, (bool) ($configuration['case_sensitive'] ?? false));

        $minimumItems = (int) ($configuration['minimum_items'] ?? 1);
        $maximumItems = (int) ($configuration['maximum_items'] ?? max(1, count($items)));

        if ($minimumItems < 1 || $maximumItems < $minimumItems) {
            throw ValidationException::withMessages(['content_configuration.minimum_items' => 'Completion rules must be possible.']);
        }
    }

    public function prepareSession(TypingExerciseVersion $version): array
    {
        $items = $version->contentItems()->where('is_active', true)->orderBy('display_order')->get();

        return [
            'items' => $items->pluck('id')->all(),
            'expected_text' => $items->pluck('expected_text')->implode("\n"),
            'prompt_count' => $items->count(),
        ];
    }

    public function learnerPayload(TypingSession $session): array
    {
        $session->loadMissing(['exerciseVersion.exercise', 'exerciseVersion.contentItems', 'exerciseVersion.difficultyProfile']);
        $version = $session->exerciseVersion;

        return [
            'session' => [
                'uuid' => $session->uuid,
                'status' => $session->status->value,
                'stateVersion' => $session->state_version,
                'currentItemPosition' => $session->current_item_position,
                'inputMethod' => $session->input_method->value,
                'keyboardLayout' => $session->keyboard_layout,
                'expiresAt' => $session->expires_at?->toISOString(),
            ],
            'exercise' => [
                'title' => $version->exercise->title,
                'type' => $version->exercise->exercise_type->value,
                'instructions' => $version->exercise->child_instructions,
                'difficulty' => $version->difficultyProfile?->name,
                'caseSensitive' => $version->case_sensitive === 'case_sensitive',
                'backspacePolicy' => $version->backspace_policy->value,
                'correctionPolicy' => $version->correction_policy->value,
                'timer' => $version->timer_configuration ?? [],
                'completionCriteria' => Arr::only($version->completion_criteria ?? [], ['minimum_items', 'minimum_accuracy', 'allow_pause']),
            ],
            'items' => $version->contentItems->where('is_active', true)->values()->map(fn ($item): array => [
                'id' => $item->id,
                'promptText' => $item->prompt_text,
                'displayText' => $item->display_text ?? $item->expected_text,
                'itemType' => $item->item_type,
                'targetKeys' => $item->target_keys ?? [],
                'audioPath' => $item->audio_path,
                'imagePath' => $item->image_path,
            ])->all(),
        ];
    }

    public function validateInputEvent(TypingSession $session, array $event): array
    {
        $eventType = (string) ($event['event_type'] ?? 'input');
        if (! in_array($eventType, ['input', 'backspace', 'paste', 'prompt_replay', 'assistive_input'], true)) {
            throw ValidationException::withMessages(['events' => 'That typing action is not supported.']);
        }

        if (in_array($eventType, ['input', 'paste', 'assistive_input'], true)) {
            $entered = (string) ($event['entered_character'] ?? '');
            if ($entered === '' || mb_strlen($entered) > 20) {
                throw ValidationException::withMessages(['events' => 'Typing entries must be short and related to the prompt.']);
            }
        }

        return [
            'typing_content_item_id' => $event['typing_content_item_id'] ?? null,
            'sequence_number' => (int) $event['sequence_number'],
            'character_position' => isset($event['character_position']) ? (int) $event['character_position'] : null,
            'event_type' => $eventType,
            'expected_character' => isset($event['expected_character']) ? mb_substr((string) $event['expected_character'], 0, 20) : null,
            'entered_character' => isset($event['entered_character']) ? mb_substr((string) $event['entered_character'], 0, 20) : null,
            'normalised_character' => isset($event['entered_character']) ? $this->normalise((string) $event['entered_character'], $session) : null,
            'correctness_state' => $event['correctness_state'] ?? null,
            'correction_sequence' => $event['correction_sequence'] ?? null,
            'input_method' => $event['input_method'] ?? $session->input_method->value,
            'modifier_state' => Arr::only($event['modifier_state'] ?? [], ['shift', 'capsLock', 'altGraph']),
            'elapsed_offset_ms' => max(0, min(3_600_000, (int) ($event['elapsed_offset_ms'] ?? 0))),
            'server_received_at' => now(),
            'retained_until' => now()->addDays(90),
            'metadata' => Arr::only($event['metadata'] ?? [], ['source', 'visibility_change', 'touch']),
        ];
    }

    public function requiresManualReview(TypingSession $session): bool
    {
        $type = $session->exerciseVersion->exercise->exercise_type;

        return in_array($type, [TypingExerciseType::TypingAssessment, TypingExerciseType::CopyTyping], true)
            && (bool) (($session->state['paste_detected'] ?? false) || ($session->state['needs_review'] ?? false));
    }

    protected function normalise(string $value, TypingSession $session): string
    {
        if ($session->exerciseVersion->case_sensitive === 'case_sensitive') {
            return $value;
        }

        return mb_strtolower($value);
    }
}
