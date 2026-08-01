<?php

namespace App\Listeners\Rewards;

use App\Events\Typing\TypingSessionCompleted;
use App\Models\TypingSession;
use App\Services\Rewards\ProgressEventProcessor;

class ProcessCompletedTypingProgress
{
    public function __construct(private readonly ProgressEventProcessor $processor) {}

    public function handle(TypingSessionCompleted $event): void
    {
        $session = $event->session->loadMissing([
            'result',
            'exerciseVersion.exercise',
            'exerciseVersion.skills',
            'lessonStage.lesson.unit.world.curriculum',
        ]);

        if (! $session->child_id || ($session->metadata['preview'] ?? false) === true || ! $session->result) {
            return;
        }

        $this->processor->process($this->payload($session));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(TypingSession $session): array
    {
        $result = $session->result;
        $stage = $session->lessonStage;

        return [
            'event_type' => 'typing.completed',
            'child_id' => $session->child_id,
            'source_type' => TypingSession::class,
            'source_id' => $session->getKey(),
            'curriculum_id' => $stage?->lesson?->unit?->world?->curriculum_id,
            'curriculum_world_id' => $stage?->lesson?->unit?->world_id,
            'curriculum_unit_id' => $stage?->lesson?->unit_id,
            'curriculum_lesson_id' => $stage?->lesson_id,
            'lesson_stage_id' => $stage?->getKey(),
            'skill_id' => $session->exerciseVersion->skills->first()?->id,
            'occurred_at' => $session->completed_at ?? now(),
            'performance_summary' => [
                'accuracy' => (float) $result->first_attempt_accuracy,
                'score' => (float) $result->final_text_accuracy,
                'maximum_score' => 100,
                'completion_status' => $result->validity_status->value,
                'completion_time' => (int) $result->active_duration_ms,
            ],
            'metadata' => [
                'source_slug' => $session->exerciseVersion->exercise->slug,
                'skill_slug' => $session->exerciseVersion->skills->first()?->slug,
                'preview' => false,
                'released_to_parent' => true,
            ],
            'idempotency_key' => 'typing.completed:'.$session->getKey(),
        ];
    }
}
