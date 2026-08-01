<?php

namespace App\Http\Controllers\Child;

use App\Http\Controllers\Concerns\InteractsWithAssignments;
use App\Http\Controllers\Controller;
use App\Models\AssignmentAllocation;
use App\Models\User;
use App\Services\Assignments\AssignmentAttemptService;
use App\Services\Assignments\AssignmentQuestionHandlerRegistry;
use App\Services\Assignments\AssignmentReportService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MissionController extends Controller
{
    use InteractsWithAssignments;

    public function index(AssignmentReportService $reportService): Response
    {
        $child = request()->user();
        abort_unless($child instanceof User, 403);

        return Inertia::render('child/missions/index', [
            'child' => [
                'id' => $child->id,
                'name' => $child->name,
                'learner_id' => $child->childProfile?->learner_id,
                'avatar_url' => $child->avatar_url,
            ],
            'missions' => $reportService->childMissions($child),
        ]);
    }

    public function show(AssignmentAllocation $assignmentAllocation, AssignmentQuestionHandlerRegistry $registry): Response
    {
        $child = request()->user();
        abort_unless($child instanceof User, 403);
        $this->authorize('view', $assignmentAllocation);

        $assignmentAllocation->loadMissing(['assignmentVersion.items.options', 'attempts.responses.item', 'attempts.feedback', 'child']);
        $latestAttempt = $assignmentAllocation->attempts()->where('child_id', $child->id)->orderByDesc('attempt_number')->first();

        return Inertia::render('child/missions/show', [
            'allocation' => $this->allocationResource($assignmentAllocation),
            'mission' => $this->versionResource($assignmentAllocation->assignmentVersion, $registry, true),
            'latestAttempt' => $latestAttempt ? $this->attemptResource($latestAttempt, true) : null,
            'canStart' => $assignmentAllocation->status->value !== 'cancelled',
            'startAction' => route('child.missions.start', $assignmentAllocation, absolute: false),
        ]);
    }

    public function start(AssignmentAllocation $assignmentAllocation, AssignmentAttemptService $attemptService): RedirectResponse
    {
        $child = request()->user();
        abort_unless($child instanceof User, 403);
        $this->authorize('view', $assignmentAllocation);

        $attempt = $attemptService->startAttempt($assignmentAllocation, $child);

        return to_route('child.missions.attempts.show', $attempt);
    }
}
