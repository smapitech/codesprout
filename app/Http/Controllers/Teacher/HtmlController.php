<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Html\ProjectReviewRequest;
use App\Models\HtmlExercise;
use App\Models\LearnerWebpageProject;
use App\Services\Html\HtmlAttemptService;
use App\Services\Html\HtmlReportService;
use App\Services\Html\WebpageProjectService;
use Inertia\Inertia;
use Inertia\Response;

class HtmlController extends Controller
{
    public function index(HtmlReportService $reports): Response
    {
        return Inertia::render('html/teacher/index', [
            'exercises' => HtmlExercise::query()
                ->where('status', ContentStatus::Published)
                ->orderBy('title')
                ->get()
                ->map(fn (HtmlExercise $exercise): array => [
                    'slug' => $exercise->slug,
                    'title' => $exercise->title,
                    'type' => $exercise->exercise_type->label(),
                    'previewHref' => route('teacher.html.preview', $exercise, absolute: false),
                ]),
            'projects' => $reports->teacherRows(request()->user()),
        ]);
    }

    public function preview(HtmlExercise $html, HtmlAttemptService $service): Response
    {
        abort_unless($html->status === ContentStatus::Published && $html->currentVersion, 404);
        $attempt = $service->start($html->currentVersion, request()->user(), ['preview' => true]);

        return Inertia::render('child/html/attempt', [
            'payload' => $service->payload($attempt, request()->user()),
            'actions' => ['complete' => route('teacher.html.index', absolute: false), 'leave' => route('teacher.html.index', absolute: false)],
            'banner' => 'Teacher Preview - no learner progress or rewards will be created.',
        ]);
    }

    public function review(LearnerWebpageProject $project): Response
    {
        $this->authorize('review', $project);
        $project->loadMissing(['child:id,name', 'latestRevision', 'templateVersion.template', 'reviews']);

        return Inertia::render('html/teacher/review', [
            'project' => [
                'uuid' => $project->uuid,
                'child' => $project->child?->name,
                'title' => $project->title,
                'status' => $project->status->value,
                'template' => $project->templateVersion->template->title,
                'sanitisedHtml' => $project->latestRevision?->sanitised_html,
                'sourceChecksum' => $project->latestRevision?->source_checksum,
            ],
            'action' => route('teacher.html.projects.review.store', $project, absolute: false),
        ]);
    }

    public function storeReview(ProjectReviewRequest $request, LearnerWebpageProject $project, WebpageProjectService $service)
    {
        $this->authorize('review', $project);
        $service->review($project, $request->user(), $request->validated());

        return to_route('teacher.html.index')->with('status', 'Project review saved.');
    }
}
