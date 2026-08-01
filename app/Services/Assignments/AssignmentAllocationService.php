<?php

namespace App\Services\Assignments;

use App\Enums\AllocationStatus;
use App\Enums\ContentStatus;
use App\Enums\LateSubmissionPolicy;
use App\Enums\RoleName;
use App\Models\AssignmentAllocation;
use App\Models\AssignmentVersion;
use App\Models\LearnerGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignmentAllocationService
{
    public function __construct(
        private readonly AssignmentAuditService $auditService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createAllocation(AssignmentVersion $version, array $payload, User $actor): AssignmentAllocation
    {
        $this->assertVersionCanBeAllocated($version);
        $this->validateTargetPayload($payload);
        $this->assertTeacherCanAllocate($actor, $payload);

        return DB::transaction(function () use ($version, $payload, $actor): AssignmentAllocation {
            $allocation = new AssignmentAllocation;
            $allocation->fill([
                'assignment_version_id' => $version->getKey(),
                'assigned_by' => $actor->getKey(),
                'class_id' => $payload['class_id'] ?? null,
                'group_id' => $payload['group_id'] ?? null,
                'child_id' => $payload['child_id'] ?? null,
                'available_from' => $this->parseDate($payload['available_from'] ?? now()),
                'due_at' => $this->parseDate($payload['due_at'] ?? null),
                'closes_at' => $this->parseDate($payload['closes_at'] ?? null),
                'attempt_limit' => (int) ($payload['attempt_limit'] ?? $version->default_attempt_limit ?? 1),
                'scoring_method' => $payload['scoring_method'] ?? $version->scoring_method,
                'show_score_to_child' => (bool) ($payload['show_score_to_child'] ?? true),
                'show_correct_answers' => (bool) ($payload['show_correct_answers'] ?? false),
                'allow_late_submission' => (bool) ($payload['allow_late_submission'] ?? false),
                'late_submission_policy' => $payload['late_submission_policy'] ?? LateSubmissionPolicy::Block->value,
                'status' => $payload['status'] ?? $this->resolveInitialStatus($payload),
            ]);
            $allocation->save();

            $this->auditService->record('assignment.allocation.created', $allocation, $actor, [
                'assignment_version_id' => $version->getKey(),
                'target' => $this->targetSummary($allocation),
            ]);

            return $allocation->fresh(['assignmentVersion.assignment', 'classroom', 'group', 'child']);
        });
    }

    public function cancel(AssignmentAllocation $allocation, ?User $actor = null): AssignmentAllocation
    {
        $allocation->forceFill(['status' => AllocationStatus::Cancelled])->save();

        $this->auditService->record('assignment.allocation.cancelled', $allocation, $actor, [
            'target' => $this->targetSummary($allocation),
        ]);

        return $allocation->fresh(['assignmentVersion.assignment', 'classroom', 'group', 'child']);
    }

    public function reopen(AssignmentAllocation $allocation, ?User $actor = null): AssignmentAllocation
    {
        $allocation->forceFill([
            'status' => $this->resolveInitialStatus([
                'available_from' => $allocation->available_from,
                'closes_at' => $allocation->closes_at,
            ]),
        ])->save();

        $this->auditService->record('assignment.allocation.reopened', $allocation, $actor, [
            'target' => $this->targetSummary($allocation),
        ]);

        return $allocation->fresh(['assignmentVersion.assignment', 'classroom', 'group', 'child']);
    }

    public function close(AssignmentAllocation $allocation, ?User $actor = null): AssignmentAllocation
    {
        $allocation->forceFill(['status' => AllocationStatus::Closed])->save();

        $this->auditService->record('assignment.allocation.closed', $allocation, $actor, [
            'target' => $this->targetSummary($allocation),
        ]);

        return $allocation->fresh(['assignmentVersion.assignment', 'classroom', 'group', 'child']);
    }

    public function targetSummary(AssignmentAllocation $allocation): array
    {
        return [
            'type' => $allocation->class_id ? 'class' : ($allocation->group_id ? 'group' : 'child'),
            'label' => $allocation->targetLabel(),
        ];
    }

    public function isOpen(AssignmentAllocation $allocation): bool
    {
        if ($allocation->status === AllocationStatus::Cancelled) {
            return false;
        }

        $now = now();

        if ($allocation->available_from && $allocation->available_from->isFuture()) {
            return false;
        }

        if ($allocation->closes_at && $allocation->closes_at->isPast()) {
            return false;
        }

        return $allocation->status === AllocationStatus::Open || $allocation->status === AllocationStatus::Scheduled;
    }

    /**
     * @return Collection<int, AssignmentAllocation>
     */
    public function allocationsForTeacher(User $teacher): Collection
    {
        $classIds = $teacher->teachingClasses()->pluck('classes.id');

        return AssignmentAllocation::query()
            ->with(['assignmentVersion.assignment', 'classroom', 'group', 'child', 'attempts'])
            ->where(function (Builder $query) use ($classIds): void {
                $query->whereIn('class_id', $classIds->all())
                    ->orWhereHas('group', fn (Builder $groupQuery) => $groupQuery->whereIn('class_id', $classIds->all()))
                    ->orWhereHas('child.enrolledClasses', fn (Builder $classQuery) => $classQuery->whereIn('classes.id', $classIds->all()));
            })
            ->orderByDesc('available_from')
            ->get();
    }

    /**
     * @return Collection<int, AssignmentAllocation>
     */
    public function allocationsForChild(User $child): Collection
    {
        $classIds = $child->enrolledClasses()->pluck('classes.id');
        $groupIds = LearnerGroup::query()
            ->whereIn('class_id', $classIds->all())
            ->whereHas('members', fn (Builder $query) => $query->where('child_id', $child->id))
            ->pluck('id');

        return AssignmentAllocation::query()
            ->with(['assignmentVersion.assignment', 'classroom', 'group', 'child', 'attempts'])
            ->where('status', '!=', AllocationStatus::Cancelled->value)
            ->where(function (Builder $query) use ($classIds, $groupIds, $child): void {
                $query->whereIn('class_id', $classIds->all())
                    ->orWhereIn('group_id', $groupIds->all())
                    ->orWhere('child_id', $child->id);
            })
            ->orderByDesc('available_from')
            ->get();
    }

    /**
     * @return Collection<int, AssignmentAllocation>
     */
    public function allocationsForParent(User $parent): Collection
    {
        $childIds = $parent->children()->pluck('users.id');

        return AssignmentAllocation::query()
            ->with(['assignmentVersion.assignment', 'classroom', 'group', 'child', 'attempts'])
            ->where(function (Builder $query) use ($childIds): void {
                $query->whereIn('child_id', $childIds->all())
                    ->orWhereHas('classroom.learners', fn (Builder $learnerQuery) => $learnerQuery->whereIn('users.id', $childIds->all()))
                    ->orWhereHas('group.members', fn (Builder $memberQuery) => $memberQuery->whereIn('child_id', $childIds->all()));
            })
            ->orderByDesc('available_from')
            ->get();
    }

    public function assertChildCanAccessAllocation(User $child, AssignmentAllocation $allocation): void
    {
        if ($allocation->child_id === $child->id) {
            return;
        }

        $childClassIds = $child->enrolledClasses()->pluck('classes.id')->all();

        if ($allocation->class_id && in_array((int) $allocation->class_id, $childClassIds, true)) {
            return;
        }

        if ($allocation->group_id) {
            $group = LearnerGroup::query()->with('members')->find($allocation->group_id);

            if ($group && (int) $group->class_id && in_array((int) $group->class_id, $childClassIds, true) && $group->members->contains('child_id', $child->id)) {
                return;
            }
        }

        abort(403);
    }

    public function assertTeacherCanAllocate(User $teacher, array $payload): void
    {
        if ($teacher->hasRole(RoleName::Administrator->value)) {
            return;
        }

        $classIds = $teacher->teachingClasses()->pluck('classes.id')->all();

        if (filled($payload['class_id'] ?? null)) {
            abort_unless(in_array((int) $payload['class_id'], $classIds, true), 403);

            return;
        }

        if (filled($payload['group_id'] ?? null)) {
            $group = LearnerGroup::query()->findOrFail((int) $payload['group_id']);
            abort_unless(in_array((int) $group->class_id, $classIds, true), 403);

            return;
        }

        if (filled($payload['child_id'] ?? null)) {
            $child = User::query()->findOrFail((int) $payload['child_id']);
            abort_unless($child->enrolledClasses()->whereIn('classes.id', $classIds)->exists(), 403);
        }
    }

    public function assertActorMayTarget(User $actor, array $payload): void
    {
        $this->assertTeacherCanAllocate($actor, $payload);
    }

    public function assertVersionCanBeAllocated(AssignmentVersion $version): void
    {
        if (! $version->isPublished() && $version->status !== ContentStatus::Published) {
            throw ValidationException::withMessages([
                'assignment_version_id' => 'Only published assignment versions can be allocated.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function validateTargetPayload(array $payload): void
    {
        $filledTargets = collect([
            $payload['class_id'] ?? null,
            $payload['group_id'] ?? null,
            $payload['child_id'] ?? null,
        ])->filter(fn ($value): bool => filled($value))->count();

        if ($filledTargets !== 1) {
            throw ValidationException::withMessages([
                'target' => 'Choose exactly one target: a class, a group, or a child.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function resolveInitialStatus(array $payload): AllocationStatus
    {
        $availableFrom = $this->parseDate($payload['available_from'] ?? null);
        $closesAt = $this->parseDate($payload['closes_at'] ?? null);

        if ($availableFrom instanceof \DateTimeInterface && $availableFrom > now()) {
            return AllocationStatus::Scheduled;
        }

        if ($closesAt instanceof \DateTimeInterface && $closesAt <= now()) {
            return AllocationStatus::Closed;
        }

        return AllocationStatus::Open;
    }

    private function parseDate(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface || $value === null || $value === '') {
            return $value;
        }

        return now()->parse((string) $value);
    }
}
