<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Enums\HtmlExerciseType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Html\HtmlExerciseRequest;
use App\Http\Requests\Html\ProjectTemplateRequest;
use App\Models\HtmlExercise;
use App\Models\HtmlExerciseVersion;
use App\Models\HtmlTagPolicy;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateVersion;
use App\Services\Html\HtmlExercisePublicationService;
use App\Services\Html\HtmlReportService;
use App\Services\Html\ProjectTemplatePublicationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HtmlController extends Controller
{
    public function index(HtmlReportService $reports): Response
    {
        return Inertia::render('html/admin/index', [
            'summary' => $reports->adminSummary(),
            'usageByType' => $reports->usageByType(),
            'exercises' => HtmlExercise::query()
                ->with('currentVersion.tagPolicy')
                ->withCount('versions')
                ->orderBy('title')
                ->get()
                ->map(fn (HtmlExercise $exercise): array => $this->exerciseRow($exercise)),
            'templates' => ProjectTemplate::query()
                ->with('currentVersion.tagPolicy')
                ->withCount('versions')
                ->orderBy('title')
                ->get()
                ->map(fn (ProjectTemplate $template): array => $this->templateRow($template)),
            'createHref' => route('admin.html.create', absolute: false),
            'templateCreateHref' => route('admin.html.templates.create', absolute: false),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('html/admin/form', [
            'mode' => 'create',
            'kind' => 'exercise',
            'action' => route('admin.html.store', absolute: false),
            'exercise' => null,
            'typeOptions' => HtmlExerciseType::options(),
            'tagPolicies' => $this->tagPolicies(),
        ]);
    }

    public function store(HtmlExerciseRequest $request, HtmlExercisePublicationService $publication): RedirectResponse
    {
        $exercise = $publication->createDraft($request->validated(), $request->user());

        return to_route('admin.html.show', $exercise)->with('status', 'HTML exercise draft created.');
    }

    public function show(HtmlExercise $html): Response
    {
        $html->loadMissing(['currentVersion.requirements', 'currentVersion.tagPolicy', 'versions']);

        return Inertia::render('html/admin/show', [
            'exercise' => $this->exerciseDetails($html),
            'actions' => [
                'edit' => route('admin.html.edit', $html, absolute: false),
                'publish' => $html->currentVersion ? route('admin.html.publish', [$html, $html->currentVersion], absolute: false) : null,
                'archive' => route('admin.html.archive', $html, absolute: false),
            ],
        ]);
    }

    public function edit(HtmlExercise $html): Response
    {
        $html->loadMissing(['currentVersion.requirements', 'currentVersion.tagPolicy']);

        return Inertia::render('html/admin/form', [
            'mode' => 'edit',
            'kind' => 'exercise',
            'action' => route('admin.html.update', $html, absolute: false),
            'exercise' => $this->exerciseDetails($html),
            'typeOptions' => HtmlExerciseType::options(),
            'tagPolicies' => $this->tagPolicies(),
        ]);
    }

    public function update(HtmlExerciseRequest $request, HtmlExercise $html, HtmlExercisePublicationService $publication): RedirectResponse
    {
        $publication->createDraftFrom($html, $request->validated(), $request->user());

        return to_route('admin.html.show', $html)->with('status', 'A new draft HTML exercise version was created.');
    }

    public function publish(HtmlExercise $html, HtmlExerciseVersion $version, HtmlExercisePublicationService $publication): RedirectResponse
    {
        abort_unless($version->html_exercise_id === $html->id, 404);
        $publication->publish($version, request()->user());

        return to_route('admin.html.show', $html)->with('status', 'HTML exercise published.');
    }

    public function archive(HtmlExercise $html, HtmlExercisePublicationService $publication): RedirectResponse
    {
        $publication->archive($html, request()->user());

        return to_route('admin.html.show', $html)->with('status', 'HTML exercise archived.');
    }

    public function createTemplate(): Response
    {
        return Inertia::render('html/admin/form', [
            'mode' => 'create',
            'kind' => 'template',
            'action' => route('admin.html.templates.store', absolute: false),
            'exercise' => null,
            'typeOptions' => HtmlExerciseType::options(),
            'tagPolicies' => $this->tagPolicies(),
        ]);
    }

    public function storeTemplate(ProjectTemplateRequest $request, ProjectTemplatePublicationService $publication): RedirectResponse
    {
        $template = $publication->createDraft($request->validated(), $request->user());

        return to_route('admin.html.index')->with('status', 'Project template draft created: '.$template->title);
    }

    public function publishTemplate(ProjectTemplate $template, ProjectTemplateVersion $version, ProjectTemplatePublicationService $publication): RedirectResponse
    {
        abort_unless($version->project_template_id === $template->id, 404);
        $publication->publish($version, request()->user());

        return to_route('admin.html.index')->with('status', 'Project template published.');
    }

    private function tagPolicies()
    {
        return HtmlTagPolicy::query()
            ->where('status', ContentStatus::Published)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'version']);
    }

    private function exerciseRow(HtmlExercise $exercise): array
    {
        return [
            'id' => $exercise->id,
            'slug' => $exercise->slug,
            'title' => $exercise->title,
            'type' => $exercise->exercise_type->label(),
            'status' => $exercise->status->value,
            'versions' => $exercise->versions_count ?? $exercise->versions()->count(),
            'currentVersion' => $exercise->currentVersion?->version_number,
            'tagPolicy' => $exercise->currentVersion?->tagPolicy?->name,
            'href' => route('admin.html.show', $exercise, absolute: false),
        ];
    }

    private function exerciseDetails(HtmlExercise $exercise): array
    {
        return array_merge($this->exerciseRow($exercise), [
            'description' => $exercise->description,
            'childInstructions' => $exercise->child_instructions,
            'teacherInstructions' => $exercise->teacher_instructions,
            'currentVersionData' => $exercise->currentVersion ? [
                'id' => $exercise->currentVersion->id,
                'status' => $exercise->currentVersion->status->value,
                'configuration' => $exercise->currentVersion->content_configuration,
                'requirements' => $exercise->currentVersion->requirements->map(fn ($requirement): array => [
                    'type' => $requirement->requirement_type,
                    'tag' => $requirement->tag_name,
                    'attribute' => $requirement->attribute_name,
                    'required' => $requirement->required,
                ]),
            ] : null,
        ]);
    }

    private function templateRow(ProjectTemplate $template): array
    {
        return [
            'id' => $template->id,
            'slug' => $template->slug,
            'title' => $template->title,
            'status' => $template->status->value,
            'versions' => $template->versions_count ?? $template->versions()->count(),
            'currentVersion' => $template->currentVersion?->version_number,
            'publishHref' => $template->currentVersion ? route('admin.html.templates.publish', [$template, $template->currentVersion], absolute: false) : null,
        ];
    }
}
