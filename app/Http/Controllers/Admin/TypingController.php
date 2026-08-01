<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Enums\TypingExerciseType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Typing\TypingExerciseRequest;
use App\Models\Skill;
use App\Models\TypingDifficultyProfile;
use App\Models\TypingExercise;
use App\Models\TypingExerciseVersion;
use App\Services\Typing\TypingExercisePublicationService;
use App\Services\Typing\TypingReportService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TypingController extends Controller
{
    public function index(TypingReportService $reports): Response
    {
        $exercises = TypingExercise::query()
            ->with(['currentVersion.difficultyProfile'])
            ->withCount('versions')
            ->orderBy('title')
            ->get()
            ->map(fn (TypingExercise $exercise): array => $this->exerciseRow($exercise));

        return Inertia::render('typing/admin/index', [
            'exercises' => $exercises,
            'summary' => $reports->adminSummary(),
            'createHref' => route('admin.typing.create', absolute: false),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('typing/admin/form', [
            'mode' => 'create',
            'exercise' => null,
            'action' => route('admin.typing.store', absolute: false),
            'typeOptions' => TypingExerciseType::options(),
            'difficultyProfiles' => $this->difficultyProfiles(),
            'skills' => Skill::query()->orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    public function store(TypingExerciseRequest $request, TypingExercisePublicationService $publication): RedirectResponse
    {
        $exercise = $publication->createDraft($request->validated(), $request->user());

        return to_route('admin.typing.show', $exercise)->with('status', 'Typing exercise draft created.');
    }

    public function show(TypingExercise $typing): Response
    {
        $typing->loadMissing(['currentVersion.contentItems', 'currentVersion.difficultyProfile', 'versions']);

        return Inertia::render('typing/admin/show', [
            'exercise' => $this->exerciseDetails($typing),
            'actions' => [
                'edit' => route('admin.typing.edit', $typing, absolute: false),
                'publish' => $typing->currentVersion ? route('admin.typing.publish', [$typing, $typing->currentVersion], absolute: false) : null,
                'archive' => route('admin.typing.archive', $typing, absolute: false),
            ],
        ]);
    }

    public function edit(TypingExercise $typing): Response
    {
        $typing->loadMissing(['currentVersion.contentItems', 'currentVersion.skills']);

        return Inertia::render('typing/admin/form', [
            'mode' => 'edit',
            'exercise' => $this->exerciseDetails($typing),
            'action' => route('admin.typing.update', $typing, absolute: false),
            'typeOptions' => TypingExerciseType::options(),
            'difficultyProfiles' => $this->difficultyProfiles(),
            'skills' => Skill::query()->orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    public function update(TypingExerciseRequest $request, TypingExercise $typing, TypingExercisePublicationService $publication): RedirectResponse
    {
        $publication->createDraftFrom($typing, $request->validated(), $request->user());

        return to_route('admin.typing.show', $typing)->with('status', 'A new draft typing version was created.');
    }

    public function publish(TypingExercise $typing, TypingExerciseVersion $version, TypingExercisePublicationService $publication): RedirectResponse
    {
        abort_unless($version->typing_exercise_id === $typing->id, 404);
        $publication->publish($version, request()->user());

        return to_route('admin.typing.show', $typing)->with('status', 'Typing exercise published.');
    }

    public function archive(TypingExercise $typing, TypingExercisePublicationService $publication): RedirectResponse
    {
        $publication->archive($typing, request()->user());

        return to_route('admin.typing.show', $typing)->with('status', 'Typing exercise archived.');
    }

    private function difficultyProfiles()
    {
        return TypingDifficultyProfile::query()
            ->where('status', ContentStatus::Published)
            ->orderBy('difficulty_order')
            ->get(['id', 'name', 'slug']);
    }

    private function exerciseRow(TypingExercise $exercise): array
    {
        return [
            'id' => $exercise->id,
            'slug' => $exercise->slug,
            'title' => $exercise->title,
            'type' => $exercise->exercise_type->label(),
            'status' => $exercise->status->value,
            'versions' => $exercise->versions_count ?? $exercise->versions()->count(),
            'currentVersion' => $exercise->currentVersion?->version_number,
            'difficulty' => $exercise->currentVersion?->difficultyProfile?->name,
            'href' => route('admin.typing.show', $exercise, absolute: false),
        ];
    }

    private function exerciseDetails(TypingExercise $exercise): array
    {
        return array_merge($this->exerciseRow($exercise), [
            'description' => $exercise->description,
            'childInstructions' => $exercise->child_instructions,
            'teacherInstructions' => $exercise->teacher_instructions,
            'currentVersionData' => $exercise->currentVersion ? [
                'id' => $exercise->currentVersion->id,
                'status' => $exercise->currentVersion->status->value,
                'configuration' => $exercise->currentVersion->content_configuration,
                'caseSensitive' => $exercise->currentVersion->case_sensitive,
                'backspacePolicy' => $exercise->currentVersion->backspace_policy->value,
                'correctionPolicy' => $exercise->currentVersion->correction_policy->value,
                'accuracyRequirement' => (float) $exercise->currentVersion->accuracy_requirement,
                'items' => $exercise->currentVersion->contentItems->map(fn ($item): array => [
                    'id' => $item->id,
                    'prompt_text' => $item->prompt_text,
                    'expected_text' => $item->expected_text,
                    'target_keys' => $item->target_keys ?? [],
                ])->all(),
            ] : null,
        ]);
    }
}
