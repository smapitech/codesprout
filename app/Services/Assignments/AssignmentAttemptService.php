<?php

namespace App\Services\Assignments;

use App\Enums\AllocationStatus;
use App\Enums\AssignmentFeedbackType;
use App\Enums\AttemptStatus;
use App\Events\Assignments\AssignmentCompleted;
use App\Events\Assignments\AssignmentMarked;
use App\Events\Assignments\AssignmentReturnedForRetry;
use App\Events\Assignments\AssignmentStarted;
use App\Events\Assignments\AssignmentSubmitted;
use App\Models\AssessmentRubricCriterion;
use App\Models\AssignmentAllocation;
use App\Models\AssignmentAttempt;
use App\Models\AssignmentFeedback;
use App\Models\AssignmentItem;
use App\Models\AssignmentResponse;
use App\Models\AssignmentRubricScore;
use App\Models\SubmissionAttachment;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignmentAttemptService
{
    public function __construct(
        private readonly AssignmentQuestionHandlerRegistry $registry,
        private readonly AssignmentAllocationService $allocationService,
        private readonly AssignmentAuditService $auditService,
    ) {}

    public function startAttempt(AssignmentAllocation $allocation, User $child): AssignmentAttempt
    {
        $allocation->loadMissing(['assignmentVersion.items.options', 'attempts']);
        $this->assertCanStart($allocation, $child);

        return DB::transaction(function () use ($allocation, $child): AssignmentAttempt {
            $attempt = $allocation->attempts()
                ->where('child_id', $child->id)
                ->orderByDesc('attempt_number')
                ->lockForUpdate()
                ->first();

            if ($attempt && in_array($attempt->status, [AttemptStatus::InProgress, AttemptStatus::Returned], true)) {
                return $attempt->load(['allocation.assignmentVersion.items.options', 'responses', 'feedback', 'attachments']);
            }

            $attemptCount = $allocation->attempts()->where('child_id', $child->id)->count();
            $attemptLimit = max(1, (int) ($allocation->attempt_limit ?? $allocation->assignmentVersion->default_attempt_limit ?? 1));
            abort_unless($attemptCount < $attemptLimit, 429);

            $attempt = new AssignmentAttempt([
                'assignment_allocation_id' => $allocation->getKey(),
                'assignment_version_id' => $allocation->assignment_version_id,
                'child_id' => $child->getKey(),
                'attempt_number' => $attemptCount + 1,
                'status' => AttemptStatus::InProgress,
                'started_at' => now(),
                'last_activity_at' => now(),
                'auto_score' => 0,
                'manual_score' => 0,
                'final_score' => 0,
                'maximum_score' => (float) $allocation->assignmentVersion->total_points,
                'time_spent_seconds' => 0,
                'hints_used' => 0,
                'is_late' => $this->isLate($allocation),
            ]);
            $attempt->save();

            $this->auditService->record('assignment.started', $attempt, $child, [
                'assignment_allocation_id' => $allocation->getKey(),
                'attempt_number' => $attempt->attempt_number,
            ]);

            event(new AssignmentStarted($attempt));

            return $attempt->fresh(['allocation.assignmentVersion.items.options', 'responses', 'feedback', 'attachments']);
        });
    }

    /**
     * @param  array<string, mixed>|string|int|float|bool|null  $response
     */
    public function saveResponse(AssignmentAttempt $attempt, AssignmentItem $item, mixed $response, User $child): AssignmentResponse
    {
        $attempt->loadMissing(['allocation.assignmentVersion.items.options', 'responses', 'child']);
        $this->assertCanEditAttempt($attempt, $child);
        $this->assertItemBelongsToAttempt($attempt, $item);

        $handler = $this->registry->handlerFor($item);
        $handler->validateResponse($item, $response);

        $sanitisedResponse = $this->sanitiseResponse($response);
        $grading = $handler->gradeResponse($item, $sanitisedResponse);
        $autoScore = $handler->requiresManualReview($item) ? 0 : (float) ($grading['score'] ?? 0);

        return DB::transaction(function () use ($attempt, $item, $sanitisedResponse, $grading, $autoScore, $child): AssignmentResponse {
            $responseModel = AssignmentResponse::query()->updateOrCreate(
                [
                    'assignment_attempt_id' => $attempt->getKey(),
                    'assignment_item_id' => $item->getKey(),
                ],
                [
                    'response_data' => $sanitisedResponse,
                    'text_response' => $this->responseText($sanitisedResponse),
                    'is_correct' => $grading['is_correct'],
                    'auto_score' => $autoScore,
                    'manual_score' => 0,
                    'marked_by' => null,
                    'marked_at' => null,
                    'teacher_comment' => null,
                ],
            );

            $this->recalculateAttempt($attempt->fresh(['responses', 'allocation.assignmentVersion']), $child);

            return $responseModel->fresh(['item']);
        });
    }

    public function submitAttempt(AssignmentAttempt $attempt, User $child): AssignmentAttempt
    {
        $attempt->loadMissing(['allocation.assignmentVersion.items.options', 'responses']);
        abort_unless($attempt->child_id === $child->id, 403);

        if (in_array($attempt->status, [AttemptStatus::Submitted, AttemptStatus::AwaitingReview, AttemptStatus::Marked, AttemptStatus::Completed], true)) {
            return $attempt;
        }

        $this->assertCanEditAttempt($attempt, $child);

        $requiredItemIds = $attempt->allocation->assignmentVersion->items->where('is_required', true)->pluck('id');
        $answeredItemIds = $attempt->responses->pluck('assignment_item_id');
        if ($requiredItemIds->diff($answeredItemIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'attempt' => 'Complete every required mission step before submitting.',
            ]);
        }

        return DB::transaction(function () use ($attempt, $child): AssignmentAttempt {
            $this->recalculateAttempt($attempt, $child, true);

            $manualReviewNeeded = $attempt->responses->contains(fn ($response) => $response->item && $this->registry->handlerFor($response->item)->requiresManualReview($response->item));

            $attempt->forceFill([
                'status' => $manualReviewNeeded ? AttemptStatus::AwaitingReview : AttemptStatus::Marked,
                'submitted_at' => now(),
                'last_activity_at' => now(),
                'time_spent_seconds' => $attempt->started_at ? now()->diffInSeconds($attempt->started_at) : 0,
            ])->save();

            $this->auditService->record('assignment.submitted', $attempt, $child, [
                'status' => $attempt->status->value,
                'attempt_number' => $attempt->attempt_number,
            ]);

            event(new AssignmentSubmitted($attempt));

            if ($manualReviewNeeded) {
                return $attempt->fresh(['responses.item', 'feedback', 'allocation.assignmentVersion']);
            }

            event(new AssignmentMarked($attempt));

            return $attempt->fresh(['responses.item', 'feedback', 'allocation.assignmentVersion']);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function markAttempt(AssignmentAttempt $attempt, User $teacher, array $payload): AssignmentAttempt
    {
        $attempt->loadMissing(['allocation.assignmentVersion.items.options', 'responses.item', 'feedback']);
        $this->assertTeacherCanReview($attempt, $teacher);

        return DB::transaction(function () use ($attempt, $teacher, $payload): AssignmentAttempt {
            $manualScores = collect($payload['manual_scores'] ?? [])
                ->mapWithKeys(static fn ($value, $key): array => [(int) $key => (float) $value]);

            $attempt->responses->each(function (AssignmentResponse $response) use ($manualScores, $teacher): void {
                $itemPoints = (float) ($response->item?->points ?? 0);
                $autoScore = (float) ($response->auto_score ?? 0);
                $manualScore = (float) ($manualScores->get($response->assignment_item_id, $response->manual_score ?? 0));
                $manualScore = max(0, min($manualScore, max(0, $itemPoints - $autoScore)));

                $response->forceFill([
                    'manual_score' => $manualScore,
                    'marked_by' => $teacher->getKey(),
                    'marked_at' => now(),
                    'teacher_comment' => blank($payload['teacher_comment'] ?? null) ? $response->teacher_comment : trim((string) $payload['teacher_comment']),
                ])->save();
            });

            $manualTotal = (float) $attempt->responses->sum('manual_score');
            $autoTotal = (float) $attempt->responses->sum('auto_score');
            $maximum = (float) $attempt->maximum_score;
            $final = min($maximum, $manualTotal + $autoTotal);

            $attempt->forceFill([
                'manual_score' => $manualTotal,
                'auto_score' => $autoTotal,
                'final_score' => $final,
                'status' => ! empty($payload['returned_for_retry']) ? AttemptStatus::Returned : AttemptStatus::Marked,
                'last_activity_at' => now(),
            ])->save();

            if (! empty($payload['feedback_text'])) {
                AssignmentFeedback::query()->create([
                    'assignment_attempt_id' => $attempt->getKey(),
                    'teacher_id' => $teacher->getKey(),
                    'feedback_text' => trim((string) $payload['feedback_text']),
                    'audio_feedback_path' => blank($payload['audio_feedback_path'] ?? null) ? null : (string) $payload['audio_feedback_path'],
                    'feedback_type' => $payload['feedback_type'] ?? AssignmentFeedbackType::General->value,
                    'returned_for_retry' => (bool) ($payload['returned_for_retry'] ?? false),
                    'visible_to_child' => (bool) ($payload['visible_to_child'] ?? true),
                    'visible_to_parent' => (bool) ($payload['visible_to_parent'] ?? true),
                ]);
            }

            if (! empty($payload['rubric_scores']) && is_array($payload['rubric_scores'])) {
                foreach ($payload['rubric_scores'] as $criterionId => $value) {
                    $criterion = AssessmentRubricCriterion::query()->findOrFail((int) $criterionId);
                    $criterionScore = (float) $value;
                    AssignmentRubricScore::query()->updateOrCreate(
                        [
                            'assignment_attempt_id' => $attempt->getKey(),
                            'rubric_criterion_id' => (int) $criterionId,
                        ],
                        [
                            'awarded_points' => max(0, min($criterionScore, (float) $criterion->maximum_points)),
                            'teacher_comment' => null,
                            'marked_by' => $teacher->getKey(),
                        ],
                    );
                }
            }

            $this->auditService->record('assignment.marked', $attempt, $teacher, [
                'final_score' => $attempt->final_score,
                'manual_score' => $attempt->manual_score,
            ]);

            event(new AssignmentMarked($attempt));

            if ($attempt->status === AttemptStatus::Returned) {
                event(new AssignmentReturnedForRetry($attempt));
            }

            return $attempt->fresh(['responses.item', 'feedback', 'rubricScores', 'allocation.assignmentVersion']);
        });
    }

    public function returnForRetry(AssignmentAttempt $attempt, User $teacher, string $feedback = ''): AssignmentAttempt
    {
        return $this->markAttempt($attempt, $teacher, [
            'returned_for_retry' => true,
            'feedback_text' => $feedback,
            'feedback_type' => AssignmentFeedbackType::RetryGuidance->value,
            'visible_to_child' => true,
            'visible_to_parent' => true,
        ]);
    }

    public function completeAttempt(AssignmentAttempt $attempt, ?User $actor = null): AssignmentAttempt
    {
        $attempt->forceFill(['status' => AttemptStatus::Completed, 'last_activity_at' => now()])->save();

        if ($actor) {
            $this->auditService->record('assignment.completed', $attempt, $actor, [
                'final_score' => $attempt->final_score,
            ]);
        }

        event(new AssignmentCompleted($attempt));

        return $attempt->fresh(['responses.item', 'feedback', 'rubricScores', 'allocation.assignmentVersion']);
    }

    public function uploadAttachment(AssignmentAttempt $attempt, AssignmentItem $item, array $fileData, User $child): SubmissionAttachment
    {
        $attempt->loadMissing(['allocation.assignmentVersion.items.options', 'child']);
        $this->assertCanEditAttempt($attempt, $child);
        $this->assertItemBelongsToAttempt($attempt, $item);

        if (empty($fileData['path']) || empty($fileData['disk'])) {
            throw ValidationException::withMessages([
                'attachment' => 'A valid attachment path is required.',
            ]);
        }

        return SubmissionAttachment::query()->create([
            'assignment_attempt_id' => $attempt->getKey(),
            'assignment_item_id' => $item->getKey(),
            'uploaded_by' => $child->getKey(),
            'disk' => (string) $fileData['disk'],
            'path' => (string) $fileData['path'],
            'original_name' => (string) ($fileData['original_name'] ?? basename((string) $fileData['path'])),
            'mime_type' => (string) ($fileData['mime_type'] ?? 'application/octet-stream'),
            'size_bytes' => (int) ($fileData['size_bytes'] ?? 0),
        ]);
    }

    public function recalculateAttempt(AssignmentAttempt $attempt, ?User $actor = null, bool $markSubmitted = false): void
    {
        $attempt->loadMissing(['responses.item']);

        $autoScore = (float) $attempt->responses->sum('auto_score');
        $manualScore = (float) $attempt->responses->sum('manual_score');
        $maximum = (float) $attempt->maximum_score;
        $finalScore = min($maximum, $autoScore + $manualScore);

        $attempt->forceFill([
            'auto_score' => $autoScore,
            'manual_score' => $manualScore,
            'final_score' => $finalScore,
            'last_activity_at' => now(),
        ]);

        if ($markSubmitted && $attempt->status === AttemptStatus::InProgress) {
            $attempt->status = AttemptStatus::Submitted;
            $attempt->submitted_at = now();
        }

        $attempt->save();
    }

    private function assertCanStart(AssignmentAllocation $allocation, User $child): void
    {
        $this->allocationService->assertChildCanAccessAllocation($child, $allocation);

        if ($allocation->status === AllocationStatus::Cancelled) {
            throw ValidationException::withMessages([
                'allocation' => 'This mission has been cancelled.',
            ]);
        }

        if ($allocation->available_from && $allocation->available_from->isFuture()) {
            throw ValidationException::withMessages([
                'allocation' => 'This mission is not open yet.',
            ]);
        }

        if ($allocation->closes_at && $allocation->closes_at->isPast() && ! $allocation->allow_late_submission) {
            throw ValidationException::withMessages([
                'allocation' => 'This mission has closed.',
            ]);
        }
    }

    private function assertCanEditAttempt(AssignmentAttempt $attempt, User $child): void
    {
        abort_unless($attempt->child_id === $child->id, 403);

        if (in_array($attempt->status, [AttemptStatus::Submitted, AttemptStatus::AwaitingReview, AttemptStatus::Marked, AttemptStatus::Completed], true)) {
            throw ValidationException::withMessages([
                'attempt' => 'This mission has already been submitted.',
            ]);
        }
    }

    private function assertTeacherCanReview(AssignmentAttempt $attempt, User $teacher): void
    {
        if ($teacher->hasRole('administrator')) {
            return;
        }

        $classIds = $teacher->teachingClasses()->pluck('classes.id')->all();
        $allocation = $attempt->allocation;

        $matchesClass = $allocation->class_id && in_array((int) $allocation->class_id, $classIds, true);
        $matchesGroup = $allocation->group_id && $allocation->group?->class_id && in_array((int) $allocation->group->class_id, $classIds, true);
        $matchesChild = $allocation->child_id && $allocation->child?->enrolledClasses()->whereIn('classes.id', $classIds)->exists();

        abort_unless($matchesClass || $matchesGroup || $matchesChild, 403);
    }

    private function assertItemBelongsToAttempt(AssignmentAttempt $attempt, AssignmentItem $item): void
    {
        abort_unless((int) $item->assignment_version_id === (int) $attempt->assignment_version_id, 404);
    }

    /**
     * @param  array<string, mixed>|string|int|float|bool|null  $response
     * @return array<string, mixed>
     */
    private function sanitiseResponse(mixed $response): array
    {
        if (! is_array($response)) {
            return ['value' => $this->responseText($response)];
        }

        return Arr::except($response, [
            'correct',
            'correct_answer',
            'expected',
            'expected_answer',
            'is_correct',
            'answer',
            'answers',
        ]);
    }

    /**
     * @param  array<string, mixed>|string|int|float|bool|null  $response
     */
    private function responseText(mixed $response): string
    {
        if (is_array($response)) {
            return (string) ($response['text'] ?? $response['value'] ?? '');
        }

        return (string) $response;
    }

    private function isLate(AssignmentAllocation $allocation): bool
    {
        return $allocation->due_at instanceof \DateTimeInterface
            && $allocation->due_at->isPast();
    }
}
