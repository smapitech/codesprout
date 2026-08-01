<?php

namespace App\Services\Games;

use App\Enums\AllocationStatus;
use App\Enums\ContentStatus;
use App\Enums\GameCompletionStatus;
use App\Enums\GameDifficulty;
use App\Enums\GameSessionStatus;
use App\Enums\GameType;
use App\Events\Games\GamePerformanceRecorded;
use App\Events\Games\GameRoundCompleted;
use App\Events\Games\GameSessionAbandoned;
use App\Events\Games\GameSessionCompleted;
use App\Events\Games\GameSessionPaused;
use App\Events\Games\GameSessionResumed;
use App\Events\Games\GameSessionStarted;
use App\Models\AssignmentAllocation;
use App\Models\AssignmentAttempt;
use App\Models\AssignmentItem;
use App\Models\AssignmentResponse;
use App\Models\GameResult;
use App\Models\GameSession;
use App\Models\GameSessionRound;
use App\Models\GameVersion;
use App\Models\User;
use App\Services\Assignments\AssignmentAllocationService;
use App\Services\Assignments\AssignmentAttemptService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GameSessionService
{
    public function __construct(
        private readonly GameRegistry $registry,
        private readonly AssignmentAllocationService $allocationService,
        private readonly AssignmentAttemptService $assignmentAttemptService,
        private readonly GameAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function start(GameVersion $version, User $child, GameDifficulty|string $difficulty = GameDifficulty::Slow, array $context = []): GameSession
    {
        $version->loadMissing('definition');
        $difficulty = $difficulty instanceof GameDifficulty ? $difficulty : GameDifficulty::from((string) $difficulty);
        $this->assertVersionCanStart($version);
        $context['game_version_id'] = $version->getKey();
        $this->assertContext($child, $context);

        return DB::transaction(function () use ($version, $child, $difficulty, $context): GameSession {
            $clientId = $context['client_session_identifier'] ?? null;

            if ($clientId) {
                $existing = GameSession::query()
                    ->where('child_id', $child->id)
                    ->where('game_version_id', $version->id)
                    ->where('client_session_identifier', $clientId)
                    ->first();

                if ($existing) {
                    return $existing->load(['gameVersion.definition', 'roundRecords', 'result']);
                }
            }

            $handler = $this->registry->handlerFor($version);
            $rounds = $handler->generateRounds($version, $difficulty);

            $session = GameSession::query()->create([
                'child_id' => $child->getKey(),
                'game_version_id' => $version->getKey(),
                'lesson_stage_id' => $context['lesson_stage_id'] ?? null,
                'assignment_allocation_id' => $context['assignment_allocation_id'] ?? null,
                'assignment_attempt_id' => $context['assignment_attempt_id'] ?? null,
                'assignment_item_id' => $context['assignment_item_id'] ?? null,
                'status' => GameSessionStatus::InProgress,
                'difficulty' => $difficulty,
                'started_at' => now(),
                'last_activity_at' => now(),
                'client_session_identifier' => $clientId,
                'rounds' => $rounds,
                'progress_data' => ['difficulty' => $difficulty->value, 'hints_used' => 0, 'assistance_used' => 0],
                'current_round' => 1,
            ]);

            foreach ($rounds as $round) {
                $session->roundRecords()->create([
                    'round_number' => (int) $round['round'],
                    'round_data' => [
                        'prompt' => $round['prompt'],
                        'safe' => $round['safe'],
                        'expected' => $round['expected'],
                    ],
                    'status' => 'ready',
                ]);
            }

            $this->audit->record('game.session.started', $session, $child, ['game_version_id' => $version->id]);
            event(new GameSessionStarted($session));

            return $session->fresh(['gameVersion.definition', 'roundRecords']);
        });
    }

    public function payload(GameSession $session, User $child): array
    {
        abort_unless($session->child_id === $child->id, 403);

        return $this->registry->handlerFor($session->gameVersion)->sessionPayload($session);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordAction(GameSession $session, User $child, array $payload): array
    {
        abort_unless($session->child_id === $child->id, 403);

        if ($session->status === GameSessionStatus::Completed) {
            throw ValidationException::withMessages(['session' => 'This game is already complete.']);
        }

        if ($session->status === GameSessionStatus::Paused) {
            throw ValidationException::withMessages(['session' => 'This game is paused.']);
        }

        $handler = $this->registry->handlerFor($session->gameVersion);
        $validated = $handler->validateAction($session, $payload);

        return DB::transaction(function () use ($session, $validated, $handler): array {
            /** @var GameSessionRound $round */
            $round = $session->roundRecords()->where('round_number', $validated['round_number'])->lockForUpdate()->firstOrFail();
            $correct = $this->isCorrect($session, $round, $validated['response']);
            $progress = $session->progress_data ?? [];
            $progress['hints_used'] = (int) ($progress['hints_used'] ?? 0) + (int) ($validated['hint_used'] ?? false);
            $progress['assistance_used'] = (int) ($progress['assistance_used'] ?? 0) + (int) ($validated['assistance_used'] ?? false);
            $progress['longest_sequence'] = $correct ? (int) ($progress['longest_sequence'] ?? 0) + 1 : 0;

            $round->forceFill([
                'response_data' => $this->safeResponse($validated['response']),
                'is_correct' => $correct,
                'response_time_ms' => $validated['response_time_ms'] ?? null,
                'status' => 'completed',
            ])->save();

            $nextRound = min($session->roundRecords()->count(), $round->round_number + 1);
            $session->forceFill([
                'progress_data' => $progress,
                'current_round' => $nextRound,
                'last_activity_at' => now(),
            ])->save();

            event(new GameRoundCompleted($session, $round));

            return [
                'correct' => $correct,
                'feedback' => $handler->feedbackFor($correct),
                'complete' => $handler->isComplete($session->fresh(['roundRecords'])),
            ];
        });
    }

    public function pause(GameSession $session, User $child): GameSession
    {
        abort_unless($session->child_id === $child->id, 403);
        $session->forceFill(['status' => GameSessionStatus::Paused, 'paused_at' => now(), 'last_activity_at' => now()])->save();
        event(new GameSessionPaused($session));

        return $session->fresh(['gameVersion.definition', 'roundRecords']);
    }

    public function resume(GameSession $session, User $child): GameSession
    {
        abort_unless($session->child_id === $child->id, 403);
        $session->forceFill(['status' => GameSessionStatus::InProgress, 'paused_at' => null, 'last_activity_at' => now()])->save();
        event(new GameSessionResumed($session));

        return $session->fresh(['gameVersion.definition', 'roundRecords']);
    }

    public function abandon(GameSession $session, User $child): GameSession
    {
        abort_unless($session->child_id === $child->id, 403);
        $session->forceFill(['status' => GameSessionStatus::Abandoned, 'abandoned_at' => now(), 'last_activity_at' => now()])->save();
        event(new GameSessionAbandoned($session));

        return $session->fresh(['gameVersion.definition', 'roundRecords']);
    }

    public function complete(GameSession $session, User $child, ?string $idempotencyKey = null): GameSession
    {
        abort_unless($session->child_id === $child->id, 403);

        if ($session->status === GameSessionStatus::Completed) {
            return $session->fresh(['result', 'gameVersion.definition']);
        }

        return DB::transaction(function () use ($session, $child, $idempotencyKey): GameSession {
            $session->refresh()->load(['gameVersion.definition', 'roundRecords']);
            $handler = $this->registry->handlerFor($session->gameVersion);
            $metrics = $handler->calculatePerformance($session);

            $result = $session->result()->updateOrCreate([], array_merge($metrics, [
                'completion_status' => $handler->isComplete($session) ? GameCompletionStatus::Completed : GameCompletionStatus::Partial,
            ]));

            $session->forceFill([
                'status' => GameSessionStatus::Completed,
                'completed_at' => now(),
                'last_activity_at' => now(),
                'idempotency_key' => $idempotencyKey ?? $session->idempotency_key,
            ])->save();

            if ($session->assignment_attempt_id && $session->assignment_item_id) {
                $this->recordAssignmentResponse($session, $result, $child);
            }

            $this->audit->record('game.session.completed', $session, $child, [
                'score' => $result->score,
                'maximum_score' => $result->maximum_score,
            ]);

            event(new GamePerformanceRecorded($session, $result));
            event(new GameSessionCompleted($session));

            return $session->fresh(['result', 'gameVersion.definition', 'assignmentAttempt.responses']);
        });
    }

    private function assertVersionCanStart(GameVersion $version): void
    {
        if ($version->status !== ContentStatus::Published || $version->definition->status !== ContentStatus::Published) {
            throw ValidationException::withMessages(['game_version_id' => 'Only published games can be started.']);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function assertContext(User $child, array $context): void
    {
        if (! empty($context['assignment_allocation_id'])) {
            $allocation = AssignmentAllocation::query()->findOrFail((int) $context['assignment_allocation_id']);
            $this->allocationService->assertChildCanAccessAllocation($child, $allocation);

            if ($allocation->status === AllocationStatus::Cancelled || ($allocation->closes_at && $allocation->closes_at->isPast() && ! $allocation->allow_late_submission)) {
                throw ValidationException::withMessages(['assignment_allocation_id' => 'This mission is not open for games.']);
            }
        }

        if (! empty($context['assignment_attempt_id'])) {
            $attempt = AssignmentAttempt::query()->with(['allocation.assignmentVersion.items'])->findOrFail((int) $context['assignment_attempt_id']);
            abort_unless($attempt->child_id === $child->id, 403);

            if (! empty($context['assignment_allocation_id']) && (int) $attempt->assignment_allocation_id !== (int) $context['assignment_allocation_id']) {
                throw ValidationException::withMessages(['assignment_attempt_id' => 'This game attempt does not match the assignment allocation.']);
            }

            if (! empty($context['assignment_item_id'])) {
                $item = $attempt->allocation->assignmentVersion->items->firstWhere('id', (int) $context['assignment_item_id']);

                if (! $item) {
                    throw ValidationException::withMessages(['assignment_item_id' => 'This game item does not belong to the assignment attempt.']);
                }

                if ($item->game_version_id && ! empty($context['game_version_id']) && (int) $item->game_version_id !== (int) $context['game_version_id']) {
                    throw ValidationException::withMessages(['assignment_item_id' => 'This assignment item is linked to a different game version.']);
                }
            }
        }
    }

    private function isCorrect(GameSession $session, GameSessionRound $round, array $response): bool
    {
        $expected = $round->round_data['expected'] ?? null;
        $type = $session->gameVersion->definition->game_type;

        return match ($type) {
            GameType::ComputerPartMatching,
            GameType::DragAndDrop => ($response['match'] ?? $response['selected_match'] ?? null) === $expected,
            GameType::DoubleClickPractice => (int) ($response['interval_ms'] ?? 999999) <= (int) $expected,
            GameType::KeyboardKeyExplorer,
            GameType::FallingLetters,
            GameType::ArrowKeyPath => $this->normaliseKey((string) ($response['key'] ?? $response['move'] ?? '')) === $this->normaliseKey((string) $expected),
            default => ($response['selected_part'] ?? $response['selected_target'] ?? $response['value'] ?? null) === $expected,
        };
    }

    private function recordAssignmentResponse(GameSession $session, GameResult $result, User $child): void
    {
        $item = AssignmentItem::query()->findOrFail($session->assignment_item_id);
        AssignmentResponse::query()->updateOrCreate(
            [
                'assignment_attempt_id' => $session->assignment_attempt_id,
                'assignment_item_id' => $session->assignment_item_id,
            ],
            [
                'response_data' => [
                    'game_session_uuid' => $session->uuid,
                    'accuracy' => (float) $result->accuracy,
                    'completion_status' => $result->completion_status->value,
                ],
                'text_response' => 'Game completed',
                'is_correct' => $result->completion_status === GameCompletionStatus::Completed,
                'auto_score' => min((float) $item->points, (float) $item->points * ((float) $result->accuracy / 100)),
                'manual_score' => 0,
            ],
        );

        $attempt = AssignmentAttempt::query()->with(['responses.item', 'allocation.assignmentVersion'])->findOrFail($session->assignment_attempt_id);
        $this->assignmentAttemptService->recalculateAttempt($attempt, $child);
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function safeResponse(array $response): array
    {
        return Arr::except($response, ['expected', 'answer', 'answers', 'is_correct', 'score']);
    }

    private function normaliseKey(string $key): string
    {
        return match (strtolower(trim($key))) {
            ' ', 'spacebar' => 'space',
            'esc' => 'escape',
            'arrowup' => 'arrow_up',
            'arrowdown' => 'arrow_down',
            'arrowleft' => 'arrow_left',
            'arrowright' => 'arrow_right',
            default => strtolower(trim($key)),
        };
    }
}
