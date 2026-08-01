<?php

namespace App\Services\Html;

use App\Enums\ContentStatus;
use App\Enums\HtmlAttemptStatus;
use App\Enums\HtmlValidationStatus;
use App\Events\Html\HtmlExerciseCompleted;
use App\Models\HtmlAttempt;
use App\Models\HtmlExerciseVersion;
use App\Models\AssignmentItem;
use App\Models\AssignmentResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HtmlAttemptService
{
    public function __construct(private readonly HtmlValidationService $validator) {}

    public function start(HtmlExerciseVersion $version, User $actor, array $context = []): HtmlAttempt
    {
        abort_unless($version->status === ContentStatus::Published, 404);

        return HtmlAttempt::query()->create([
            'child_id' => $context['preview'] ?? false ? null : $actor->id,
            'preview_actor_id' => $context['preview'] ?? false ? $actor->id : null,
            'html_exercise_version_id' => $version->id,
            'lesson_stage_id' => $context['lesson_stage_id'] ?? null,
            'assignment_allocation_id' => $context['assignment_allocation_id'] ?? null,
            'assignment_attempt_id' => $context['assignment_attempt_id'] ?? null,
            'assignment_item_id' => $context['assignment_item_id'] ?? null,
            'attempt_type' => $context['attempt_type'] ?? (($context['preview'] ?? false) ? 'preview' : 'practice'),
            'status' => ($context['preview'] ?? false) ? HtmlAttemptStatus::Preview : HtmlAttemptStatus::InProgress,
            'input_mode' => $context['input_mode'] ?? 'guided_code',
            'started_at' => now(),
            'metadata' => [
                'preview' => (bool) ($context['preview'] ?? false),
                'input_method' => $context['input_method'] ?? 'guided_code',
            ],
        ]);
    }

    public function complete(HtmlAttempt $attempt, User $actor, array $payload): HtmlAttempt
    {
        $this->assertOwner($attempt, $actor);

        return DB::transaction(function () use ($attempt, $payload): HtmlAttempt {
            $locked = HtmlAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, [HtmlAttemptStatus::Completed, HtmlAttemptStatus::Submitted], true)) {
                return $locked->fresh(['validationResult.requirementResults', 'exerciseVersion.exercise']);
            }

            if (! in_array($locked->status, [HtmlAttemptStatus::InProgress, HtmlAttemptStatus::Preview, HtmlAttemptStatus::Paused], true)) {
                throw ValidationException::withMessages(['attempt' => 'This HTML activity cannot be completed from its current state.']);
            }

            $source = (string) $payload['source_html'];
            $validation = $this->validator->validateSource($source, $locked->exerciseVersion);

            $locked->responses()->create([
                'response_type' => 'source_html',
                'bounded_response' => $source,
                'sanitised_response' => $validation['sanitised_html'],
                'structural_response' => $validation['structure'],
                'input_method' => $payload['input_method'] ?? $locked->input_mode,
                'display_order' => 1,
            ]);

            $locked->forceFill([
                'status' => ($locked->metadata['preview'] ?? false) ? HtmlAttemptStatus::Preview : (
                    $validation['status'] === HtmlValidationStatus::Valid ? HtmlAttemptStatus::Completed : HtmlAttemptStatus::AwaitingReview
                ),
                'completed_at' => now(),
                'active_duration_ms' => min(1800000, max(0, (int) ($payload['active_duration_ms'] ?? 0))),
                'assistance_count' => min(50, max(0, (int) ($payload['assistance_count'] ?? 0))),
                'idempotency_key' => $payload['idempotency_key'] ?? 'html.attempt:'.$locked->id,
                'state_version' => $locked->state_version + 1,
            ])->save();

            $this->validator->persistAttemptResult($locked, $validation);

            if ($locked->child_id && ! ($locked->metadata['preview'] ?? false) && $validation['status'] === HtmlValidationStatus::Valid) {
                $this->recordAssignmentCompletion($locked);
                event(new HtmlExerciseCompleted($locked->fresh(['validationResult', 'exerciseVersion.exercise'])));
            }

            return $locked->fresh(['validationResult.requirementResults', 'exerciseVersion.exercise']);
        });
    }

    public function payload(HtmlAttempt $attempt, User $actor): array
    {
        $this->assertOwner($attempt, $actor);
        $attempt->loadMissing(['exerciseVersion.exercise', 'exerciseVersion.requirements', 'validationResult']);

        return [
            'attempt' => [
                'uuid' => $attempt->uuid,
                'status' => $attempt->status->value,
                'inputMode' => $attempt->input_mode,
                'stateVersion' => $attempt->state_version,
            ],
            'exercise' => [
                'title' => $attempt->exerciseVersion->exercise->title,
                'slug' => $attempt->exerciseVersion->exercise->slug,
                'type' => $attempt->exerciseVersion->exercise_type->value,
                'instructions' => $attempt->exerciseVersion->exercise->child_instructions,
                'configuration' => $attempt->exerciseVersion->content_configuration,
                'requirements' => $attempt->exerciseVersion->requirements->map(fn ($requirement): array => [
                    'type' => $requirement->requirement_type,
                    'tag' => $requirement->tag_name,
                    'attribute' => $requirement->attribute_name,
                    'required' => $requirement->required,
                ]),
            ],
            'result' => $attempt->validationResult ? [
                'status' => $attempt->validationResult->validity_status->value,
                'summary' => $attempt->validationResult->result_summary,
            ] : null,
        ];
    }

    private function assertOwner(HtmlAttempt $attempt, User $actor): void
    {
        if ($attempt->child_id && $attempt->child_id !== $actor->id) {
            abort(403);
        }

        if ($attempt->preview_actor_id && $attempt->preview_actor_id !== $actor->id && ! $actor->hasRole('administrator')) {
            abort(403);
        }
    }

    private function recordAssignmentCompletion(HtmlAttempt $attempt): void
    {
        if (! $attempt->assignment_attempt_id || ! $attempt->assignment_item_id) {
            return;
        }

        $item = AssignmentItem::query()->find($attempt->assignment_item_id);
        if (! $item || (int) $item->html_exercise_version_id !== (int) $attempt->html_exercise_version_id) {
            return;
        }

        AssignmentResponse::query()->updateOrCreate(
            ['assignment_attempt_id' => $attempt->assignment_attempt_id, 'assignment_item_id' => $item->id],
            [
                'response_data' => ['linked_html_attempt' => $attempt->uuid, 'status' => 'completed'],
                'text_response' => 'Validated HTML exercise completed',
                'is_correct' => true,
                'auto_score' => $item->points,
                'manual_score' => 0,
            ],
        );
    }
}
