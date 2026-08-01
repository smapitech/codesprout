<?php

namespace App\Services\Typing;

use App\Enums\AllocationStatus;
use App\Enums\ContentStatus;
use App\Enums\TypingInputMethod;
use App\Enums\TypingSessionStatus;
use App\Enums\TypingValidityStatus;
use App\Events\Typing\TypingSessionCompleted;
use App\Models\AssignmentAllocation;
use App\Models\AssignmentAttempt;
use App\Models\AssignmentItem;
use App\Models\AssignmentResponse;
use App\Models\TypingEventBatch;
use App\Models\TypingExerciseVersion;
use App\Models\TypingInputEvent;
use App\Models\TypingKeyStatistic;
use App\Models\TypingResult;
use App\Models\TypingSession;
use App\Models\User;
use App\Services\Assignments\AssignmentAllocationService;
use App\Services\Assignments\AssignmentAttemptService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TypingSessionService
{
    public function __construct(
        private readonly TypingExerciseRegistry $registry,
        private readonly TypingMetricCalculator $metrics,
        private readonly AssignmentAllocationService $allocationService,
        private readonly AssignmentAttemptService $assignmentAttemptService,
        private readonly TypingAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function start(TypingExerciseVersion $version, User $child, array $context = []): TypingSession
    {
        $version->loadMissing(['exercise', 'contentItems', 'difficultyProfile']);
        $this->assertVersionCanStart($version);
        $this->assertContext($child, $version, $context);

        return DB::transaction(function () use ($version, $child, $context): TypingSession {
            if (! empty($context['client_session_identifier'])) {
                $existing = TypingSession::query()
                    ->where('child_id', $child->id)
                    ->where('typing_exercise_version_id', $version->id)
                    ->where('metadata->client_session_identifier', $context['client_session_identifier'])
                    ->whereIn('status', [TypingSessionStatus::Ready->value, TypingSessionStatus::InProgress->value, TypingSessionStatus::Paused->value, TypingSessionStatus::Resumed->value])
                    ->first();

                if ($existing) {
                    return $existing->load(['exerciseVersion.exercise', 'exerciseVersion.contentItems', 'result']);
                }
            }

            $state = $this->registry->handlerFor($version)->prepareSession($version);
            $session = TypingSession::query()->create([
                'child_id' => $child->id,
                'typing_exercise_version_id' => $version->id,
                'lesson_stage_id' => $context['lesson_stage_id'] ?? null,
                'assignment_allocation_id' => $context['assignment_allocation_id'] ?? null,
                'assignment_attempt_id' => $context['assignment_attempt_id'] ?? null,
                'game_session_id' => $context['game_session_id'] ?? null,
                'session_type' => $context['session_type'] ?? 'practice',
                'input_method' => $context['input_method'] ?? TypingInputMethod::Unknown->value,
                'keyboard_layout' => $context['keyboard_layout'] ?? 'qwerty',
                'status' => TypingSessionStatus::InProgress,
                'started_at' => now(),
                'expires_at' => now()->addMinutes((int) ($context['expires_in_minutes'] ?? 45)),
                'state' => $state,
                'metadata' => Arr::only($context, ['client_session_identifier', 'accessibility_accommodation', 'assignment_item_id']),
            ]);

            $this->audit->record('typing.session.started', $session, $child, [
                'typing_exercise_version_id' => $version->id,
                'session_type' => $session->session_type,
            ]);

            return $session->fresh(['exerciseVersion.exercise', 'exerciseVersion.contentItems']);
        });
    }

    public function preview(TypingExerciseVersion $version, User $actor, string $sessionType = 'teacher_preview'): TypingSession
    {
        abort_unless($actor->hasAnyRole(['administrator', 'teacher']), 403);
        $version->loadMissing(['exercise', 'contentItems', 'difficultyProfile']);

        return TypingSession::query()->create([
            'preview_actor_id' => $actor->id,
            'typing_exercise_version_id' => $version->id,
            'session_type' => $sessionType,
            'input_method' => TypingInputMethod::Unknown,
            'keyboard_layout' => 'qwerty',
            'status' => TypingSessionStatus::InProgress,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(30),
            'state' => $this->registry->handlerFor($version)->prepareSession($version),
            'metadata' => ['preview' => true],
        ]);
    }

    public function payload(TypingSession $session, User $actor): array
    {
        $this->assertCanAccessSession($session, $actor);

        return $this->registry->handlerFor($session->exerciseVersion)->learnerPayload($session);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordBatch(TypingSession $session, User $actor, array $payload): TypingEventBatch
    {
        $this->assertCanAccessSession($session, $actor);
        $this->assertAcceptingEvents($session);

        $events = array_values($payload['events'] ?? []);
        if (count($events) < 1 || count($events) > 50) {
            throw ValidationException::withMessages(['events' => 'Typing updates must be sent in small batches.']);
        }

        $checksum = hash('sha256', json_encode($events, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($session, $payload, $events, $checksum): TypingEventBatch {
            $session = TypingSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            $existing = TypingEventBatch::query()
                ->where('typing_session_id', $session->id)
                ->where('batch_uuid', $payload['batch_uuid'])
                ->first();

            if ($existing) {
                if ($existing->payload_checksum !== $checksum) {
                    throw ValidationException::withMessages(['batch_uuid' => 'This typing batch was already received with different data.']);
                }

                return $existing;
            }

            $handler = $this->registry->handlerFor($session->exerciseVersion);
            $validatedEvents = [];

            foreach ($events as $event) {
                $sequence = (int) ($event['sequence_number'] ?? 0);
                if ($sequence <= $session->last_event_sequence) {
                    throw ValidationException::withMessages(['events' => 'Typing events must arrive in order.']);
                }

                $validated = $handler->validateInputEvent($session, $event);
                if (TypingInputEvent::query()->where('typing_session_id', $session->id)->where('sequence_number', $sequence)->exists()) {
                    throw ValidationException::withMessages(['events' => 'That typing event was already saved.']);
                }
                $validatedEvents[] = $validated;
            }

            foreach ($validatedEvents as $validated) {
                $session->inputEvents()->create($validated);
            }

            $state = $session->state ?? [];
            if (collect($validatedEvents)->contains(fn (array $event): bool => $event['event_type'] === 'paste')) {
                $state['paste_detected'] = true;
                $state['needs_review'] = true;
            }

            $lastSequence = max(array_column($validatedEvents, 'sequence_number'));
            $firstSequence = min(array_column($validatedEvents, 'sequence_number'));
            $maxElapsed = max(array_column($validatedEvents, 'elapsed_offset_ms'));

            $session->forceFill([
                'first_input_at' => $session->first_input_at ?? now(),
                'last_event_sequence' => $lastSequence,
                'active_duration_ms' => max((int) $session->active_duration_ms, $maxElapsed),
                'last_activity_at' => now(),
                'state_version' => $session->state_version + 1,
                'state' => $state,
            ])->save();

            return $session->eventBatches()->create([
                'batch_uuid' => $payload['batch_uuid'],
                'first_sequence' => $firstSequence,
                'last_sequence' => $lastSequence,
                'event_count' => count($validatedEvents),
                'received_at' => now(),
                'payload_checksum' => $checksum,
                'processing_status' => 'processed',
            ]);
        });
    }

    public function pause(TypingSession $session, User $actor): TypingSession
    {
        $this->assertCanAccessSession($session, $actor);
        $session->loadMissing('exerciseVersion');

        if (($session->exerciseVersion->completion_criteria['allow_pause'] ?? true) === false) {
            throw ValidationException::withMessages(['session' => 'This typing assessment needs to be finished in one sitting.']);
        }

        $this->transition($session, [TypingSessionStatus::InProgress, TypingSessionStatus::Resumed], TypingSessionStatus::Paused, [
            'paused_at' => now(),
        ]);

        return $session->fresh(['exerciseVersion.exercise', 'exerciseVersion.contentItems']);
    }

    public function resume(TypingSession $session, User $actor, ?int $knownStateVersion = null): TypingSession
    {
        $this->assertCanAccessSession($session, $actor);

        if ($knownStateVersion !== null && $knownStateVersion < $session->state_version) {
            throw ValidationException::withMessages(['state_version' => 'Your saved typing screen is older than the server copy. Please reload this practice.']);
        }

        if ($session->expires_at && $session->expires_at->isPast()) {
            $session->forceFill(['status' => TypingSessionStatus::Expired])->save();
            throw ValidationException::withMessages(['session' => 'This typing session has expired.']);
        }

        $pausedMs = $session->paused_at ? max(0, now()->diffInMilliseconds($session->paused_at)) : 0;
        $this->transition($session, [TypingSessionStatus::Paused], TypingSessionStatus::Resumed, [
            'resumed_at' => now(),
            'paused_at' => null,
            'paused_duration_ms' => $session->paused_duration_ms + $pausedMs,
        ]);

        return $session->fresh(['exerciseVersion.exercise', 'exerciseVersion.contentItems']);
    }

    public function complete(TypingSession $session, User $actor, ?string $idempotencyKey = null): TypingSession
    {
        $this->assertCanAccessSession($session, $actor);

        if ($session->result) {
            return $session->fresh(['result', 'exerciseVersion.exercise']);
        }

        return DB::transaction(function () use ($session, $actor, $idempotencyKey): TypingSession {
            $session = TypingSession::query()->with(['exerciseVersion.exercise', 'exerciseVersion.contentItems', 'exerciseVersion.skills', 'inputEvents', 'result'])
                ->whereKey($session->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->result) {
                return $session->fresh(['result', 'exerciseVersion.exercise']);
            }

            $metrics = $this->metrics->calculate($session);
            $requiresReview = $this->registry->handlerFor($session->exerciseVersion)->requiresManualReview($session);
            $nextStatus = $requiresReview ? TypingSessionStatus::AwaitingReview : TypingSessionStatus::Completed;
            $validity = $requiresReview ? TypingValidityStatus::NeedsReview->value : $metrics['validity_status'];

            $result = $session->result()->create(array_merge($metrics, [
                'typing_session_id' => $session->id,
                'child_id' => $session->child_id,
                'validity_status' => $validity,
            ]));

            $session->forceFill([
                'status' => $nextStatus,
                'completed_at' => now(),
                'submitted_at' => $session->assignment_attempt_id ? now() : null,
                'last_activity_at' => now(),
                'state_version' => $session->state_version + 1,
                'idempotency_key' => $idempotencyKey ?? $session->idempotency_key,
            ])->save();

            $this->updateKeyStatistics($session, $result);

            if ($session->assignment_attempt_id && $session->metadata['assignment_item_id'] ?? null) {
                $this->recordAssignmentResponse($session, $result, $actor);
            }

            $this->audit->record('typing.session.completed', $session, $actor, [
                'first_attempt_accuracy' => (float) $result->first_attempt_accuracy,
                'final_text_accuracy' => (float) $result->final_text_accuracy,
                'validity_status' => $result->validity_status->value,
            ]);

            if (! ($session->metadata['preview'] ?? false) && $session->child_id && $result->validity_status !== TypingValidityStatus::Invalidated) {
                event(new TypingSessionCompleted($session->fresh(['result', 'exerciseVersion.exercise', 'exerciseVersion.skills'])));
            }

            return $session->fresh(['result', 'exerciseVersion.exercise', 'exerciseVersion.contentItems']);
        });
    }

    /**
     * @param  array<int, TypingSessionStatus>  $from
     * @param  array<string, mixed>  $extra
     */
    private function transition(TypingSession $session, array $from, TypingSessionStatus $to, array $extra = []): void
    {
        if (! in_array($session->status, $from, true)) {
            throw ValidationException::withMessages(['session' => 'That typing action is not available right now.']);
        }

        $session->forceFill(array_merge($extra, [
            'status' => $to,
            'last_activity_at' => now(),
            'state_version' => $session->state_version + 1,
        ]))->save();
    }

    private function assertVersionCanStart(TypingExerciseVersion $version): void
    {
        if ($version->status !== ContentStatus::Published || $version->exercise->status !== ContentStatus::Published) {
            throw ValidationException::withMessages(['typing_exercise_version_id' => 'Only published typing exercises can be started.']);
        }
    }

    private function assertAcceptingEvents(TypingSession $session): void
    {
        if (! in_array($session->status, [TypingSessionStatus::InProgress, TypingSessionStatus::Resumed], true)) {
            throw ValidationException::withMessages(['session' => 'This typing session is not accepting input.']);
        }

        if ($session->expires_at && $session->expires_at->isPast()) {
            $session->forceFill(['status' => TypingSessionStatus::Expired])->save();
            throw ValidationException::withMessages(['session' => 'This typing session has expired.']);
        }
    }

    private function assertCanAccessSession(TypingSession $session, User $actor): void
    {
        if ($session->child_id) {
            abort_unless($session->child_id === $actor->id, 403);

            return;
        }

        abort_unless($session->preview_actor_id === $actor->id && $actor->hasAnyRole(['administrator', 'teacher']), 403);
    }

    private function assertContext(User $child, TypingExerciseVersion $version, array $context): void
    {
        if (! empty($context['assignment_allocation_id'])) {
            $allocation = AssignmentAllocation::query()->findOrFail((int) $context['assignment_allocation_id']);
            $this->allocationService->assertChildCanAccessAllocation($child, $allocation);

            if ($allocation->status === AllocationStatus::Cancelled || ($allocation->closes_at && $allocation->closes_at->isPast() && ! $allocation->allow_late_submission)) {
                throw ValidationException::withMessages(['assignment_allocation_id' => 'This typing mission is not open.']);
            }
        }

        if (! empty($context['assignment_attempt_id'])) {
            $attempt = AssignmentAttempt::query()->with(['allocation.assignmentVersion.items'])->findOrFail((int) $context['assignment_attempt_id']);
            abort_unless($attempt->child_id === $child->id, 403);

            if (! empty($context['assignment_allocation_id']) && (int) $attempt->assignment_allocation_id !== (int) $context['assignment_allocation_id']) {
                throw ValidationException::withMessages(['assignment_attempt_id' => 'This typing attempt does not match the assignment allocation.']);
            }

            if (! empty($context['assignment_item_id'])) {
                $item = $attempt->allocation->assignmentVersion->items->firstWhere('id', (int) $context['assignment_item_id']);
                if (! $item || ($item->typing_exercise_version_id && (int) $item->typing_exercise_version_id !== $version->id)) {
                    throw ValidationException::withMessages(['assignment_item_id' => 'This assignment item is linked to a different typing exercise.']);
                }
            }
        }
    }

    private function updateKeyStatistics(TypingSession $session, TypingResult $result): void
    {
        $session->loadMissing('inputEvents');
        $firstAttempts = $session->inputEvents
            ->whereIn('event_type', ['input', 'paste', 'assistive_input'])
            ->filter(fn (TypingInputEvent $event): bool => $event->expected_character !== null)
            ->unique('character_position');

        foreach ($firstAttempts as $event) {
            $key = mb_strtolower((string) $event->expected_character);
            $stat = TypingKeyStatistic::query()->firstOrCreate([
                'child_id' => $session->child_id,
                'key_identifier' => $key,
                'keyboard_layout' => $session->keyboard_layout,
                'input_method' => $event->input_method,
            ]);

            $attempts = $stat->attempts + 1;
            $correct = $stat->first_attempt_correct + (($event->correctness_state === 'correct') ? 1 : 0);
            $accuracy = round(($correct / max(1, $attempts)) * 100, 2);

            $stat->forceFill([
                'attempts' => $attempts,
                'first_attempt_correct' => $correct,
                'corrected_attempts' => $stat->corrected_attempts + (($event->correctness_state === 'corrected') ? 1 : 0),
                'recent_accuracy' => $accuracy,
                'highest_supported_accuracy' => max((float) $stat->highest_supported_accuracy, $accuracy),
                'mastery_label' => $this->masteryLabel($attempts, $accuracy),
                'last_practised_at' => $result->completed_at,
                'calculated_at' => now(),
                'version' => $stat->version + 1,
            ])->save();
        }
    }

    private function masteryLabel(int $attempts, float $accuracy): string
    {
        return match (true) {
            $attempts >= 8 && $accuracy >= 90 => 'ready',
            $attempts >= 6 && $accuracy >= 80 => 'confident',
            $attempts >= 4 && $accuracy >= 65 => 'growing',
            $attempts >= 2 => 'practising',
            default => 'discovering',
        };
    }

    private function recordAssignmentResponse(TypingSession $session, TypingResult $result, User $actor): void
    {
        $itemId = (int) ($session->metadata['assignment_item_id'] ?? 0);
        $item = AssignmentItem::query()->findOrFail($itemId);
        AssignmentResponse::query()->updateOrCreate(
            [
                'assignment_attempt_id' => $session->assignment_attempt_id,
                'assignment_item_id' => $itemId,
            ],
            [
                'response_data' => [
                    'typing_session_uuid' => $session->uuid,
                    'first_attempt_accuracy' => (float) $result->first_attempt_accuracy,
                    'final_text_accuracy' => (float) $result->final_text_accuracy,
                    'validity_status' => $result->validity_status->value,
                ],
                'text_response' => 'Typing practice completed',
                'is_correct' => (float) $result->final_text_accuracy >= (float) ($session->exerciseVersion->accuracy_requirement ?? 0),
                'auto_score' => min((float) $item->points, (float) $item->points * ((float) $result->final_text_accuracy / 100)),
                'manual_score' => 0,
            ],
        );

        $attempt = AssignmentAttempt::query()->with(['responses.item', 'allocation.assignmentVersion'])->findOrFail($session->assignment_attempt_id);
        $this->assignmentAttemptService->recalculateAttempt($attempt, $actor);
    }
}
