<?php

namespace App\Http\Controllers\Child;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Html\HtmlAttemptCompleteRequest;
use App\Http\Requests\Html\HtmlPreviewRequest;
use App\Http\Requests\Html\ProjectAutosaveRequest;
use App\Models\HtmlAttempt;
use App\Models\HtmlExercise;
use App\Models\LearnerWebpageProject;
use App\Models\ProjectTemplate;
use App\Services\Html\HtmlAdaptationService;
use App\Services\Html\HtmlAttemptService;
use App\Services\Html\HtmlReportService;
use App\Services\Html\HtmlSanitizer;
use App\Services\Html\WebpageProjectService;
use Inertia\Inertia;
use Inertia\Response;

class HtmlController extends Controller
{
    public function index(HtmlReportService $reports, HtmlAdaptationService $adaptation): Response
    {
        $user = request()->user();
        $editorEnabled = (bool) config('codesprout.features.html_code_editor');
        $projectsEnabled = (bool) config('codesprout.features.html_project_assignments');
        $adaptationEnabled = (bool) config('codesprout.features.html_adaptive_practice');

        return Inertia::render('child/html/index', [
            'summary' => $reports->childSummary($user),
            'readiness' => $reports->readiness($user),
            'recommendation' => $adaptationEnabled
                ? $adaptation->recommendationFor($user)
                : ['label' => 'Choose a published activity', 'reason' => 'Your teacher is guiding the next HTML step.'],
            'exercises' => $editorEnabled ? HtmlExercise::query()
                ->with('currentVersion')
                ->where('status', ContentStatus::Published)
                ->orderBy('title')
                ->limit(12)
                ->get()
                ->map(fn (HtmlExercise $exercise): array => [
                    'slug' => $exercise->slug,
                    'title' => $exercise->title,
                    'type' => $exercise->exercise_type->label(),
                    'instructions' => $exercise->child_instructions,
                    'startHref' => route('child.html.start', $exercise, absolute: false),
                ]) : collect(),
            'templates' => $projectsEnabled ? ProjectTemplate::query()
                ->with('currentVersion')
                ->where('status', ContentStatus::Published)
                ->orderBy('title')
                ->limit(6)
                ->get()
                ->map(fn (ProjectTemplate $template): array => [
                    'slug' => $template->slug,
                    'title' => $template->title,
                    'description' => $template->description,
                    'startHref' => route('child.html.projects.start', $template, absolute: false),
                ]) : collect(),
            'projects' => $projectsEnabled ? LearnerWebpageProject::query()
                ->where('child_id', $user->id)
                ->with('latestRevision')
                ->latest('updated_at')
                ->limit(8)
                ->get()
                ->map(fn (LearnerWebpageProject $project): array => $this->projectRow($project)) : collect(),
        ]);
    }

    public function start(HtmlExercise $html, HtmlAttemptService $service)
    {
        abort_unless($html->status === ContentStatus::Published && $html->currentVersion, 404);
        $attempt = $service->start($html->currentVersion, request()->user());

        return redirect()->route('child.html.attempts.show', $attempt);
    }

    public function showAttempt(HtmlAttempt $attempt, HtmlAttemptService $service): Response
    {
        return Inertia::render('child/html/attempt', [
            'payload' => $service->payload($attempt, request()->user()),
            'actions' => [
                'complete' => route('child.html.attempts.complete', $attempt, absolute: false),
                'preview' => route('child.html.attempts.preview', $attempt, absolute: false),
                'leave' => $attempt->assignment_attempt_id
                    ? route('child.missions.attempts.show', $attempt->assignment_attempt_id, absolute: false)
                    : route('child.html.index', absolute: false),
            ],
        ]);
    }

    public function completeAttempt(HtmlAttemptCompleteRequest $request, HtmlAttempt $attempt, HtmlAttemptService $service)
    {
        $service->complete($attempt, $request->user(), $request->validated());

        return back()->with('status', 'Your HTML activity was checked safely.');
    }

    public function previewAttempt(HtmlPreviewRequest $request, HtmlAttempt $attempt, HtmlSanitizer $sanitizer)
    {
        abort_unless($attempt->child_id === $request->user()->id, 404);
        $attempt->loadMissing('exerciseVersion.tagPolicy');
        abort_unless($attempt->exerciseVersion?->tagPolicy, 422);

        $preview = $sanitizer->sanitise($request->validated('source_html'), $attempt->exerciseVersion->tagPolicy);

        return response()->json(['html' => $preview['sanitised_html'], 'issues' => $preview['issues'], 'checksum' => $preview['checksum']]);
    }

    public function startProject(ProjectTemplate $template, WebpageProjectService $service)
    {
        abort_unless($template->status === ContentStatus::Published && $template->currentVersion, 404);
        $project = $service->create($template->currentVersion, request()->user());

        return redirect()->route('child.html.projects.show', $project);
    }

    public function showProject(LearnerWebpageProject $project): Response
    {
        $this->authorize('update', $project);
        $project->loadMissing(['templateVersion.template', 'latestRevision', 'reviews']);

        return Inertia::render('child/html/editor', [
            'project' => $this->projectDetails($project),
            'actions' => [
                'autosave' => route('child.html.projects.autosave', $project, absolute: false),
                'preview' => route('child.html.projects.preview', $project, absolute: false),
                'pause' => route('child.html.projects.pause', $project, absolute: false),
                'resume' => route('child.html.projects.resume', $project, absolute: false),
                'submit' => route('child.html.projects.submit', $project, absolute: false),
                'leave' => $project->assignment_attempt_id
                    ? route('child.missions.attempts.show', $project->assignment_attempt_id, absolute: false)
                    : route('child.html.index', absolute: false),
            ],
        ]);
    }

    public function autosave(ProjectAutosaveRequest $request, LearnerWebpageProject $project, WebpageProjectService $service)
    {
        $saved = $service->autosave($project, $request->user(), $request->validated());

        if (! $request->expectsJson()) {
            return back()->with(['status' => 'Saved', 'stateVersion' => $saved->state_version]);
        }

        return response()->json(['status' => 'saved', 'stateVersion' => $saved->state_version]);
    }

    public function previewProject(HtmlPreviewRequest $request, LearnerWebpageProject $project, HtmlSanitizer $sanitizer)
    {
        $this->authorize('update', $project);
        $project->loadMissing('templateVersion.tagPolicy');
        abort_unless($project->templateVersion?->tagPolicy, 422);

        $preview = $sanitizer->sanitise($request->validated('source_html'), $project->templateVersion->tagPolicy);

        return response()->json(['html' => $preview['sanitised_html'], 'issues' => $preview['issues'], 'checksum' => $preview['checksum']]);
    }

    public function pause(LearnerWebpageProject $project, WebpageProjectService $service)
    {
        $service->pause($project, request()->user());

        return back()->with('status', 'Your webpage is safely paused.');
    }

    public function resume(LearnerWebpageProject $project, WebpageProjectService $service)
    {
        $service->resume($project, request()->user());

        return back()->with('status', 'Your webpage adventure is ready again.');
    }

    public function submit(LearnerWebpageProject $project, WebpageProjectService $service)
    {
        $service->submit($project, request()->user(), request()->header('Idempotency-Key'));

        return back()->with('status', 'Your webpage is waiting for teacher review.');
    }

    private function projectRow(LearnerWebpageProject $project): array
    {
        return [
            'uuid' => $project->uuid,
            'title' => $project->title,
            'status' => $project->status->value,
            'stateVersion' => $project->state_version,
            'updatedAt' => $project->updated_at?->toDateTimeString(),
            'href' => route('child.html.projects.show', $project, absolute: false),
        ];
    }

    private function projectDetails(LearnerWebpageProject $project): array
    {
        return array_merge($this->projectRow($project), [
            'template' => $project->templateVersion->template->title,
            'sourceHtml' => $project->latestRevision?->source_html,
            'sanitisedHtml' => $project->latestRevision?->sanitised_html,
            'checklist' => $project->templateVersion->checklist_configuration['items'] ?? [],
            'mode' => $project->project_mode->value,
            'feedback' => $project->reviews->whereNotNull('released_at')->map(fn ($review): array => [
                'status' => $review->review_status,
                'feedback' => $review->child_feedback,
            ])->values(),
        ]);
    }
}
