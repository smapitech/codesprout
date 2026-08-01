<?php

namespace App\Services\Assignments;

use App\Enums\AttemptStatus;
use App\Models\AssignmentAllocation;
use App\Models\AssignmentAttempt;
use App\Models\LearningClass;
use App\Models\User;
use Illuminate\Support\Collection;

class AssignmentReportService
{
    public function __construct(
        private readonly AssignmentAllocationService $allocationService,
    ) {}

    public function adminSummary(): array
    {
        $allocations = AssignmentAllocation::query()->with(['assignmentVersion.assignment', 'attempts'])->get();
        $attempts = AssignmentAttempt::query()->with(['allocation.assignmentVersion.assignment', 'child'])->get();

        return $this->summaryPayload($allocations, $attempts);
    }

    public function teacherSummary(User $teacher): array
    {
        $allocations = $this->allocationService->allocationsForTeacher($teacher);
        $attempts = AssignmentAttempt::query()
            ->with(['allocation.assignmentVersion.assignment', 'child'])
            ->whereHas('allocation', function ($query) use ($teacher): void {
                $classIds = $teacher->teachingClasses()->pluck('classes.id')->all();
                $query->whereIn('class_id', $classIds)
                    ->orWhereHas('group', fn ($groupQuery) => $groupQuery->whereIn('class_id', $classIds))
                    ->orWhereHas('child.enrolledClasses', fn ($childClassQuery) => $childClassQuery->whereIn('classes.id', $classIds));
            })
            ->get();

        return $this->summaryPayload($allocations, $attempts);
    }

    public function teacherMarkingQueue(User $teacher): Collection
    {
        $classIds = $teacher->teachingClasses()->pluck('classes.id')->all();

        return AssignmentAttempt::query()
            ->with([
                'allocation.assignmentVersion.assignment',
                'allocation.classroom',
                'allocation.group',
                'child.childProfile',
                'responses.item',
                'feedback.teacher',
            ])
            ->whereIn('status', [AttemptStatus::Submitted->value, AttemptStatus::AwaitingReview->value])
            ->whereHas('allocation', function ($query) use ($classIds): void {
                $query->whereIn('class_id', $classIds)
                    ->orWhereHas('group', fn ($groupQuery) => $groupQuery->whereIn('class_id', $classIds))
                    ->orWhereHas('child.enrolledClasses', fn ($childClassQuery) => $childClassQuery->whereIn('classes.id', $classIds));
            })
            ->orderByDesc('submitted_at')
            ->get()
            ->map(fn (AssignmentAttempt $attempt): array => [
                'id' => $attempt->id,
                'child' => $attempt->child?->name,
                'class' => $attempt->allocation?->targetLabel(),
                'assignment' => $attempt->allocation?->assignmentVersion?->title,
                'submission_date' => $attempt->submitted_at?->toIso8601String(),
                'attempt_number' => $attempt->attempt_number,
                'auto_score' => (float) $attempt->auto_score,
                'manual_score' => (float) $attempt->manual_score,
                'final_score' => (float) $attempt->final_score,
                'maximum_score' => (float) $attempt->maximum_score,
                'late' => (bool) $attempt->is_late,
                'status' => $attempt->status->value,
                'review_href' => route('teacher.assignments.attempts.show', $attempt, absolute: false),
            ]);
    }

    public function childMissions(User $child): array
    {
        $allocations = $this->allocationService->allocationsForChild($child);

        $today = $allocations->filter(fn (AssignmentAllocation $allocation): bool => $this->isToday($allocation));
        $continue = $allocations->filter(fn (AssignmentAllocation $allocation): bool => $this->hasInProgressAttempt($allocation, $child));
        $comingSoon = $allocations->filter(fn (AssignmentAllocation $allocation): bool => $allocation->available_from?->isFuture() ?? false);
        $completed = $allocations->filter(fn (AssignmentAllocation $allocation): bool => $this->hasCompletedAttempt($allocation, $child));

        return [
            'today' => $today->values()->map(fn (AssignmentAllocation $allocation): array => $this->missionCard($allocation, $child, 'Ready to Play', 'Start mission'))->all(),
            'continue' => $continue->values()->map(fn (AssignmentAllocation $allocation): array => $this->missionCard($allocation, $child, 'Continue Mission', 'Continue mission'))->all(),
            'coming_soon' => $comingSoon->values()->map(fn (AssignmentAllocation $allocation): array => $this->missionCard($allocation, $child, 'Coming Soon', 'Not open yet'))->all(),
            'completed' => $completed->values()->map(fn (AssignmentAllocation $allocation): array => $this->missionCard($allocation, $child, 'Great Work!', 'Completed'))->all(),
        ];
    }

    public function parentAssignments(User $parent): Collection
    {
        $childIds = $parent->children()->pluck('users.id')->all();

        return AssignmentAttempt::query()
            ->with(['allocation.assignmentVersion.assignment', 'allocation.assignmentVersion.skills', 'allocation.classroom', 'allocation.group', 'child.childProfile', 'feedback.teacher'])
            ->whereIn('child_id', $childIds)
            ->orderByDesc('submitted_at')
            ->get()
            ->map(function (AssignmentAttempt $attempt): array {
                $visibleFeedback = $attempt->feedback
                    ->where('visible_to_parent', true)
                    ->sortByDesc('created_at')
                    ->first();

                $resultReleased = in_array($attempt->status, [AttemptStatus::Marked, AttemptStatus::Completed], true)
                    && (bool) $attempt->allocation?->show_score_to_child;

                return [
                    'id' => $attempt->id,
                    'child' => $attempt->child?->name,
                    'assignment' => $attempt->allocation?->assignmentVersion?->title,
                    'status' => $attempt->status->value,
                    'due_at' => $attempt->allocation?->due_at?->toIso8601String(),
                    'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                    'result_released' => $resultReleased,
                    'score' => $resultReleased ? (float) $attempt->final_score : null,
                    'maximum_score' => $resultReleased ? (float) $attempt->maximum_score : null,
                    'teacher_feedback' => $visibleFeedback?->feedback_text,
                    'retry_allowed' => $attempt->feedback->firstWhere('returned_for_retry', true) !== null,
                    'skills' => $attempt->version?->skills?->pluck('name')->values()->all() ?? [],
                ];
            });
    }

    public function reportForClass(LearningClass $class): array
    {
        $allocations = AssignmentAllocation::query()
            ->with(['assignmentVersion.assignment', 'attempts.child'])
            ->where('class_id', $class->id)
            ->get();

        $attempts = $allocations->flatMap->attempts;

        return $this->summaryPayload($allocations, $attempts);
    }

    private function summaryPayload(Collection $allocations, Collection $attempts): array
    {
        return [
            'assigned_learners' => (int) $attempts->pluck('child_id')->filter()->unique()->count(),
            'not_started' => (int) $attempts->filter(fn (AssignmentAttempt $attempt): bool => $attempt->status === AttemptStatus::NotStarted)->count(),
            'in_progress' => (int) $attempts->filter(fn (AssignmentAttempt $attempt): bool => $attempt->status === AttemptStatus::InProgress)->count(),
            'submitted' => (int) $attempts->filter(fn (AssignmentAttempt $attempt): bool => $attempt->status === AttemptStatus::Submitted)->count(),
            'awaiting_review' => (int) $attempts->filter(fn (AssignmentAttempt $attempt): bool => $attempt->status === AttemptStatus::AwaitingReview)->count(),
            'completed' => (int) $attempts->filter(fn (AssignmentAttempt $attempt): bool => $attempt->status === AttemptStatus::Completed)->count(),
            'returned' => (int) $attempts->filter(fn (AssignmentAttempt $attempt): bool => $attempt->status === AttemptStatus::Returned)->count(),
            'late' => (int) $attempts->where('is_late', true)->count(),
            'average_score' => round((float) $attempts->avg('final_score'), 2),
            'attempt_count' => (int) $attempts->count(),
            'allocation_count' => (int) $allocations->count(),
        ];
    }

    private function missionCard(AssignmentAllocation $allocation, User $child, string $stateLabel, string $actionLabel): array
    {
        $attempt = $allocation->attempts->firstWhere('child_id', $child->id);

        return [
            'id' => $allocation->id,
            'title' => $allocation->assignmentVersion?->title ?? 'Untitled mission',
            'category' => $allocation->assignmentVersion?->assignment?->assignment_type?->label() ?? 'Mission',
            'estimated_minutes' => $allocation->assignmentVersion?->estimated_minutes ?? 10,
            'stars' => max(1, (int) ceil(((float) ($allocation->assignmentVersion?->total_points ?? 1)) / 2)),
            'state_label' => $stateLabel,
            'action_label' => $actionLabel,
            'available_from' => $allocation->available_from?->toIso8601String(),
            'due_at' => $allocation->due_at?->toIso8601String(),
            'status' => $allocation->status->value,
            'attempt_status' => $attempt?->status->value,
            'continue_href' => $attempt ? route('child.missions.attempts.show', $attempt, absolute: false) : route('child.missions.show', $allocation, absolute: false),
        ];
    }

    private function isToday(AssignmentAllocation $allocation): bool
    {
        return $allocation->available_from?->isToday() ?? false;
    }

    private function hasInProgressAttempt(AssignmentAllocation $allocation, User $child): bool
    {
        return $allocation->attempts->contains(fn (AssignmentAttempt $attempt): bool => $attempt->child_id === $child->id && in_array($attempt->status, [AttemptStatus::InProgress, AttemptStatus::Returned], true));
    }

    private function hasCompletedAttempt(AssignmentAllocation $allocation, User $child): bool
    {
        return $allocation->attempts->contains(fn (AssignmentAttempt $attempt): bool => $attempt->child_id === $child->id && in_array($attempt->status, [AttemptStatus::Marked, AttemptStatus::Completed], true));
    }
}
