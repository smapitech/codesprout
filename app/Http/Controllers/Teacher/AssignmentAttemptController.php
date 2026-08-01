<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Concerns\InteractsWithAssignments;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assignments\AssignmentMarkRequest;
use App\Models\AssignmentAttempt;
use App\Models\User;
use App\Services\Assignments\AssignmentAttemptService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentAttemptController extends Controller
{
    use InteractsWithAssignments;

    public function show(AssignmentAttempt $assignmentAttempt): Response
    {
        $teacher = request()->user();
        abort_unless($teacher instanceof User, 403);
        $this->authorize('view', $assignmentAttempt);

        $assignmentAttempt->loadMissing(['allocation.assignmentVersion.items.options', 'child.childProfile', 'responses.item', 'feedback.teacher', 'rubricScores.rubricCriterion']);

        return Inertia::render('teacher/assignments/attempt', [
            'attempt' => $this->attemptResource($assignmentAttempt),
            'allocation' => $this->allocationResource($assignmentAttempt->allocation),
            'actions' => [
                'mark' => route('teacher.assignments.attempts.update', $assignmentAttempt, absolute: false),
                'return' => route('teacher.assignments.attempts.return', $assignmentAttempt, absolute: false),
                'complete' => route('teacher.assignments.attempts.complete', $assignmentAttempt, absolute: false),
            ],
        ]);
    }

    public function update(
        AssignmentMarkRequest $request,
        AssignmentAttempt $assignmentAttempt,
        AssignmentAttemptService $attemptService,
    ): Response|RedirectResponse {
        $teacher = request()->user();
        abort_unless($teacher instanceof User, 403);
        $this->authorize('mark', $assignmentAttempt);

        $attemptService->markAttempt($assignmentAttempt, $teacher, $request->validated());

        return to_route('teacher.assignments.attempts.show', $assignmentAttempt)->with('status', 'Attempt marked.');
    }

    public function returnForRetry(AssignmentAttempt $assignmentAttempt, AssignmentAttemptService $attemptService): RedirectResponse
    {
        $teacher = request()->user();
        abort_unless($teacher instanceof User, 403);
        $this->authorize('mark', $assignmentAttempt);

        $attemptService->returnForRetry($assignmentAttempt, $teacher, 'Please try again with a little more support.');

        return to_route('teacher.assignments.attempts.show', $assignmentAttempt)->with('status', 'Returned for another attempt.');
    }

    public function complete(AssignmentAttempt $assignmentAttempt, AssignmentAttemptService $attemptService): RedirectResponse
    {
        $teacher = request()->user();
        abort_unless($teacher instanceof User, 403);
        $this->authorize('complete', $assignmentAttempt);

        $attemptService->completeAttempt($assignmentAttempt, $teacher);

        return to_route('teacher.assignments.attempts.show', $assignmentAttempt)->with('status', 'Attempt completed.');
    }
}
