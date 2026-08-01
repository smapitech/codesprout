<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AssignmentFeedbackMode;
use App\Enums\AssignmentScoringMethod;
use App\Enums\AssignmentType;
use App\Enums\ContentStatus;
use App\Enums\DifficultyLevel;
use App\Enums\LateSubmissionPolicy;
use App\Enums\QuestionType;
use App\Http\Controllers\Concerns\InteractsWithAssignments;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assignments\AssignmentAllocationRequest;
use App\Http\Requests\Assignments\AssignmentVersionRequest;
use App\Models\Assignment;
use App\Models\AssignmentVersion;
use App\Models\CurriculumWorld;
use App\Models\LearnerGroup;
use App\Models\LearningClass;
use App\Models\HtmlExercise;
use App\Models\ProjectTemplate;
use App\Models\Skill;
use App\Models\User;
use App\Services\Assignments\AssignmentAllocationService;
use App\Services\Assignments\AssignmentPublicationService;
use App\Services\Assignments\AssignmentQuestionHandlerRegistry;
use App\Services\Assignments\AssignmentReportService;
use App\Services\Assignments\AssignmentVersionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentController extends Controller
{
    use InteractsWithAssignments;

    public function index(AssignmentReportService $reportService): Response
    {
        $this->authorize('viewAny', Assignment::class);

        $assignments = Assignment::query()
            ->with(['owner', 'creator', 'currentVersion.items.options', 'versions'])
            ->withCount('versions')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Assignment $assignment): array => $this->assignmentResource($assignment));

        return Inertia::render('assignments/index', [
            'role' => 'administrator',
            'assignments' => $assignments,
            'summary' => $reportService->adminSummary(),
            'assignmentTypeOptions' => AssignmentType::options(),
            'statusOptions' => ContentStatus::options(),
            'difficultyLevelOptions' => DifficultyLevel::options(),
            'feedbackModeOptions' => AssignmentFeedbackMode::options(),
            'scoringMethodOptions' => AssignmentScoringMethod::options(),
            'lateSubmissionPolicyOptions' => LateSubmissionPolicy::options(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Assignment::class);

        return Inertia::render('assignments/builder', [
            'mode' => 'create',
            'role' => 'administrator',
            'assignment' => null,
            'version' => null,
            'action' => route('admin.assignments.store', absolute: false),
            'method' => 'post',
            'publishAction' => null,
            'allocateAction' => null,
            'submitLabel' => 'Save draft',
            'modeLabel' => 'Create assignment',
            'assignmentTypeOptions' => AssignmentType::options(),
            'statusOptions' => ContentStatus::options(),
            'difficultyLevelOptions' => DifficultyLevel::options(),
            'feedbackModeOptions' => AssignmentFeedbackMode::options(),
            'scoringMethodOptions' => AssignmentScoringMethod::options(),
            'lateSubmissionPolicyOptions' => LateSubmissionPolicy::options(),
            'questionTypeOptions' => QuestionType::options(),
            'builderOptions' => $this->builderOptions(),
            'validation' => ['is_publishable' => false, 'messages' => []],
        ]);
    }

    public function store(AssignmentVersionRequest $request, AssignmentVersionService $versionService): RedirectResponse
    {
        $version = $versionService->createDraft($request->validated(), $request->user());

        return to_route('admin.assignments.show', $version->assignment)->with('status', 'Assignment draft created.');
    }

    public function show(Assignment $assignment, AssignmentPublicationService $publicationService, AssignmentQuestionHandlerRegistry $registry): Response
    {
        $this->authorize('view', $assignment);
        $assignment->loadMissing(['currentVersion.items.options', 'versions', 'owner', 'creator']);
        $version = $assignment->currentVersion;
        abort_unless($version instanceof AssignmentVersion, 404);

        return Inertia::render('assignments/builder', [
            'mode' => 'edit',
            'role' => 'administrator',
            'assignment' => $this->assignmentResource($assignment),
            'version' => $this->versionResource($version, $registry),
            'action' => route('admin.assignments.update', $assignment, absolute: false),
            'method' => 'put',
            'publishAction' => route('admin.assignments.publish', $assignment, absolute: false),
            'allocateAction' => route('admin.assignments.allocate', $assignment, absolute: false),
            'submitLabel' => 'Save draft',
            'modeLabel' => 'Assignment builder',
            'assignmentTypeOptions' => AssignmentType::options(),
            'statusOptions' => ContentStatus::options(),
            'difficultyLevelOptions' => DifficultyLevel::options(),
            'feedbackModeOptions' => AssignmentFeedbackMode::options(),
            'scoringMethodOptions' => AssignmentScoringMethod::options(),
            'lateSubmissionPolicyOptions' => LateSubmissionPolicy::options(),
            'questionTypeOptions' => QuestionType::options(),
            'builderOptions' => $this->builderOptions(),
            'validation' => $this->publicationValidation($version, $publicationService),
        ]);
    }

    public function edit(Assignment $assignment, AssignmentPublicationService $publicationService, AssignmentQuestionHandlerRegistry $registry): Response
    {
        return $this->show($assignment, $publicationService, $registry);
    }

    public function update(AssignmentVersionRequest $request, Assignment $assignment, AssignmentVersionService $versionService): RedirectResponse
    {
        $this->authorize('update', $assignment);
        $versionService->saveDraft($assignment, $request->validated(), $request->user());

        return to_route('admin.assignments.show', $assignment)->with('status', 'Assignment draft saved.');
    }

    public function publish(Assignment $assignment, AssignmentPublicationService $publicationService): RedirectResponse
    {
        $version = $assignment->currentVersion;
        abort_unless($version instanceof AssignmentVersion, 404);
        $this->authorize('publish', $version);

        $publicationService->publishVersion($version, request()->user());

        return to_route('admin.assignments.show', $assignment)->with('status', 'Assignment published.');
    }

    public function archive(Assignment $assignment, AssignmentPublicationService $publicationService): RedirectResponse
    {
        $this->authorize('archive', $assignment);
        $publicationService->archiveAssignment($assignment, request()->user());

        return to_route('admin.assignments.show', $assignment)->with('status', 'Assignment archived.');
    }

    public function restore(Assignment $assignment, AssignmentPublicationService $publicationService): RedirectResponse
    {
        $this->authorize('restore', $assignment);
        $publicationService->restoreAssignment($assignment, request()->user());

        return to_route('admin.assignments.show', $assignment)->with('status', 'Assignment restored.');
    }

    public function allocate(Assignment $assignment, AssignmentAllocationRequest $request, AssignmentAllocationService $allocationService): RedirectResponse
    {
        $this->authorize('allocate', $assignment);
        $version = $assignment->versions()->published()->orderByDesc('version_number')->first();
        abort_unless($version instanceof AssignmentVersion, 422);

        $allocationService->createAllocation($version, $request->validated(), $request->user());

        return to_route('admin.assignments.show', $assignment)->with('status', 'Assignment allocated.');
    }

    private function builderOptions(): array
    {
        return [
            'classes' => LearningClass::query()
                ->orderBy('name')
                ->get(['id', 'name', 'class_code'])
                ->map(fn (LearningClass $class): array => [
                    'id' => $class->id,
                    'name' => $class->name,
                    'code' => $class->class_code,
                ])
                ->values(),
            'groups' => LearnerGroup::query()
                ->with('classroom')
                ->orderBy('name')
                ->get()
                ->map(fn (LearnerGroup $group): array => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'class_id' => $group->class_id,
                    'class_name' => $group->classroom?->name,
                ])
                ->values(),
            'children' => User::role('child')
                ->with('childProfile')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $child): array => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'learner_id' => $child->childProfile?->learner_id,
                ])
                ->values(),
            'worlds' => CurriculumWorld::query()
                ->published()
                ->orderBy('display_order')
                ->orderBy('world_number')
                ->get(['id', 'name', 'slug', 'world_number'])
                ->map(fn (CurriculumWorld $world): array => [
                    'id' => $world->id,
                    'name' => $world->name,
                    'slug' => $world->slug,
                    'number' => $world->world_number,
                ])
                ->values(),
            'skills' => Skill::query()
                ->published()
                ->orderBy('category')
                ->orderBy('name')
                ->get(['id', 'name', 'category'])
                ->values(),
            'htmlExercises' => $this->publishedHtmlExerciseOptions(),
            'projectTemplates' => $this->publishedProjectTemplateOptions(),
        ];
    }

    private function publishedHtmlExerciseOptions(): Collection
    {
        if (! config('codesprout.features.html_learning_engine') || ! config('codesprout.features.html_code_editor')) {
            return collect();
        }

        return HtmlExercise::query()
            ->with('currentVersion:id,html_exercise_id,version_number,status')
            ->where('status', ContentStatus::Published)
            ->orderBy('title')
            ->get()
            ->filter(fn (HtmlExercise $exercise) => $exercise->currentVersion?->status === ContentStatus::Published)
            ->map(fn (HtmlExercise $exercise): array => ['id' => $exercise->currentVersion->id, 'title' => $exercise->title, 'version' => $exercise->currentVersion->version_number])
            ->values();
    }

    private function publishedProjectTemplateOptions(): Collection
    {
        if (! config('codesprout.features.html_learning_engine') || ! config('codesprout.features.html_project_assignments')) {
            return collect();
        }

        return ProjectTemplate::query()
            ->with('currentVersion:id,project_template_id,version_number,status')
            ->where('status', ContentStatus::Published)
            ->orderBy('title')
            ->get()
            ->filter(fn (ProjectTemplate $template) => $template->currentVersion?->status === ContentStatus::Published)
            ->map(fn (ProjectTemplate $template): array => ['id' => $template->currentVersion->id, 'title' => $template->title, 'version' => $template->currentVersion->version_number])
            ->values();
    }
}
