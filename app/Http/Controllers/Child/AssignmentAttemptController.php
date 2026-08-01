<?php

namespace App\Http\Controllers\Child;

use App\Http\Controllers\Concerns\InteractsWithAssignments;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assignments\AssignmentResponseRequest;
use App\Http\Requests\Assignments\SubmissionAttachmentRequest;
use App\Models\AssignmentAttempt;
use App\Models\AssignmentItem;
use App\Models\HtmlAttempt;
use App\Models\LearnerWebpageProject;
use App\Models\User;
use App\Services\Assignments\AssignmentAttemptService;
use App\Services\Assignments\AssignmentQuestionHandlerRegistry;
use App\Services\Html\HtmlAttemptService;
use App\Services\Html\WebpageProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentAttemptController extends Controller
{
    use InteractsWithAssignments;

    public function show(AssignmentAttempt $assignmentAttempt, AssignmentQuestionHandlerRegistry $registry): Response
    {
        $child = request()->user();
        abort_unless($child instanceof User, 403);
        $this->authorize('view', $assignmentAttempt);

        $assignmentAttempt->loadMissing(['allocation.assignmentVersion.items.options', 'responses.item', 'feedback.teacher', 'child']);
        $version = $assignmentAttempt->allocation->assignmentVersion;

        return Inertia::render('child/missions/player', [
            'attempt' => $this->attemptResource($assignmentAttempt, true),
            'allocation' => $this->allocationResource($assignmentAttempt->allocation),
            'mission' => $this->versionResource($version, $registry, true),
            'progress' => [
                'current' => $assignmentAttempt->responses->count(),
                'total' => $version->items->count(),
            ],
            'actions' => [
                'save_response_base' => '/child/missions/attempts/'.$assignmentAttempt->id.'/responses',
                'submit' => route('child.missions.attempts.submit', $assignmentAttempt, absolute: false),
                'attachment_base' => '/child/missions/attempts/'.$assignmentAttempt->id.'/attachments',
                'html_launch_base' => '/child/missions/attempts/'.$assignmentAttempt->id.'/html',
                'project_launch_base' => '/child/missions/attempts/'.$assignmentAttempt->id.'/projects',
            ],
        ]);
    }

    public function storeResponse(
        AssignmentResponseRequest $request,
        AssignmentAttempt $assignmentAttempt,
        AssignmentItem $assignmentItem,
        AssignmentAttemptService $attemptService,
    ): RedirectResponse {
        $child = request()->user();
        abort_unless($child instanceof User, 403);
        $this->authorize('update', $assignmentAttempt);

        $attemptService->saveResponse($assignmentAttempt, $assignmentItem, $request->input('response'), $child);

        return back()->with('status', 'Saved.');
    }

    public function submit(AssignmentAttempt $assignmentAttempt, AssignmentAttemptService $attemptService): RedirectResponse
    {
        $child = request()->user();
        abort_unless($child instanceof User, 403);
        $this->authorize('update', $assignmentAttempt);

        $attemptService->submitAttempt($assignmentAttempt, $child);

        return to_route('child.missions.attempts.show', $assignmentAttempt)->with('status', 'Mission submitted.');
    }

    public function launchHtml(AssignmentAttempt $assignmentAttempt, AssignmentItem $assignmentItem, HtmlAttemptService $service): RedirectResponse
    {
        $child = request()->user();
        abort_unless($child instanceof User, 403);
        $this->authorize('update', $assignmentAttempt);
        abort_unless((int) $assignmentItem->assignment_version_id === (int) $assignmentAttempt->assignment_version_id, 404);
        $assignmentItem->loadMissing('htmlExerciseVersion');
        abort_unless($assignmentItem->htmlExerciseVersion, 404);

        $attempt = HtmlAttempt::query()
            ->where('assignment_attempt_id', $assignmentAttempt->id)
            ->where('assignment_item_id', $assignmentItem->id)
            ->where('child_id', $child->id)
            ->latest()
            ->first();

        if (! $attempt) {
            try {
                $attempt = $service->start($assignmentItem->htmlExerciseVersion, $child, [
                    'assignment_allocation_id' => $assignmentAttempt->assignment_allocation_id,
                    'assignment_attempt_id' => $assignmentAttempt->id,
                    'assignment_item_id' => $assignmentItem->id,
                    'attempt_type' => 'assignment',
                ]);
            } catch (QueryException $exception) {
                $attempt = HtmlAttempt::query()
                    ->where('assignment_attempt_id', $assignmentAttempt->id)
                    ->where('assignment_item_id', $assignmentItem->id)
                    ->where('child_id', $child->id)
                    ->first();
                if (! $attempt) {
                    throw $exception;
                }
            }
        }

        return redirect()->route('child.html.attempts.show', $attempt);
    }

    public function launchProject(AssignmentAttempt $assignmentAttempt, AssignmentItem $assignmentItem, WebpageProjectService $service): RedirectResponse
    {
        $child = request()->user();
        abort_unless($child instanceof User, 403);
        $this->authorize('update', $assignmentAttempt);
        abort_unless((int) $assignmentItem->assignment_version_id === (int) $assignmentAttempt->assignment_version_id, 404);
        $assignmentItem->loadMissing('projectTemplateVersion.template');
        abort_unless($assignmentItem->projectTemplateVersion, 404);

        $project = LearnerWebpageProject::query()
            ->where('assignment_attempt_id', $assignmentAttempt->id)
            ->where('assignment_item_id', $assignmentItem->id)
            ->where('child_id', $child->id)
            ->latest()
            ->first();

        if (! $project) {
            try {
                $project = $service->create($assignmentItem->projectTemplateVersion, $child, [
                    'assignment_allocation_id' => $assignmentAttempt->assignment_allocation_id,
                    'assignment_attempt_id' => $assignmentAttempt->id,
                    'assignment_item_id' => $assignmentItem->id,
                    'idempotency_key' => 'assignment-project:'.$assignmentAttempt->id.':'.$assignmentItem->id,
                ]);
            } catch (QueryException $exception) {
                $project = LearnerWebpageProject::query()
                    ->where('assignment_attempt_id', $assignmentAttempt->id)
                    ->where('assignment_item_id', $assignmentItem->id)
                    ->where('child_id', $child->id)
                    ->first();
                if (! $project) {
                    throw $exception;
                }
            }
        }

        return redirect()->route('child.html.projects.show', $project);
    }

    public function attachment(
        SubmissionAttachmentRequest $request,
        AssignmentAttempt $assignmentAttempt,
        AssignmentItem $assignmentItem,
        AssignmentAttemptService $attemptService,
    ): RedirectResponse {
        $child = request()->user();
        abort_unless($child instanceof User, 403);
        $this->authorize('update', $assignmentAttempt);

        $file = $request->file('attachment');
        abort_unless($file, 422);

        $path = $file->storeAs(
            'assignments/'.$assignmentAttempt->id,
            Str::uuid()->toString().'.'.$file->getClientOriginalExtension(),
            'private',
        );

        $attemptService->uploadAttachment($assignmentAttempt, $assignmentItem, [
            'disk' => 'private',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
        ], $child);

        return back()->with('status', 'Attachment saved.');
    }
}
