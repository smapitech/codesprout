<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Curriculum\CurriculumRequest;
use App\Models\Curriculum;
use App\Models\CurriculumLesson;
use App\Models\CurriculumUnit;
use App\Models\CurriculumWorld;
use App\Models\LessonStage;
use App\Services\Curriculum\CurriculumDuplicationService;
use App\Services\Curriculum\CurriculumExportService;
use App\Services\Curriculum\CurriculumImportService;
use App\Services\Curriculum\CurriculumOrderingService;
use App\Services\Curriculum\CurriculumPublicationService;
use App\Services\Curriculum\CurriculumSlugService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use JsonException;

class CurriculumController extends Controller
{
    public function index(): Response
    {
        $curricula = Curriculum::query()
            ->with(['worlds.units.lessons.stages'])
            ->orderBy('title')
            ->get()
            ->map(fn (Curriculum $curriculum): array => $this->summarizeCurriculum($curriculum));

        return Inertia::render('admin/curriculum/index', [
            'curricula' => $curricula,
            'statusOptions' => ContentStatus::options(),
            'totals' => [
                'curricula' => $curricula->count(),
                'worlds' => $curricula->sum('worlds_count'),
                'units' => $curricula->sum('units_count'),
                'lessons' => $curricula->sum('lessons_count'),
                'stages' => $curricula->sum('stages_count'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/curriculum/form', [
            'mode' => 'create',
            'curriculum' => null,
            'action' => route('admin.curriculum.store', absolute: false),
            'method' => 'post',
            'submitLabel' => 'Create curriculum',
            'modeLabel' => 'Create curriculum',
            'statusOptions' => ContentStatus::options(),
        ]);
    }

    public function store(CurriculumRequest $request, CurriculumSlugService $slugService): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $curriculum = DB::transaction(function () use ($validated, $slugService, $user): Curriculum {
            $curriculum = new Curriculum;
            $curriculum->fill([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'target_min_age' => $validated['target_min_age'] ?? null,
                'target_max_age' => $validated['target_max_age'] ?? null,
                'duration_weeks' => $validated['duration_weeks'],
                'lessons_per_week' => $validated['lessons_per_week'],
                'version' => $validated['version'],
            ]);
            $curriculum->slug = $slugService->generate(
                $curriculum->title,
                static fn (string $slug): bool => Curriculum::query()->where('slug', $slug)->exists(),
            );
            $this->applyStatus($curriculum, ContentStatus::from($validated['status']));
            $curriculum->created_by = $user?->id;
            $curriculum->updated_by = $user?->id;
            $curriculum->save();

            return $curriculum;
        });

        return to_route('admin.curriculum.show', $curriculum)->with('status', 'Curriculum created successfully.');
    }

    public function show(Curriculum $curriculum, CurriculumExportService $exportService, CurriculumPublicationService $publicationService): Response
    {
        $this->authorize('view', $curriculum);
        $curriculum->loadMissing(['worlds.units.lessons.stages.skills']);
        $export = $exportService->export($curriculum);

        return Inertia::render('admin/curriculum/builder', [
            'mode' => 'builder',
            'curriculum' => $this->summarizeExport($export),
            'worlds' => $export['curriculum']['worlds'],
            'skills' => $export['skills'],
            'statusOptions' => ContentStatus::options(),
            'validation' => $this->publicationValidation($curriculum, $publicationService),
        ]);
    }

    public function preview(Curriculum $curriculum, CurriculumExportService $exportService, CurriculumPublicationService $publicationService): Response
    {
        $this->authorize('view', $curriculum);
        $curriculum->loadMissing(['worlds.units.lessons.stages.skills']);
        $export = $exportService->export($curriculum);

        return Inertia::render('admin/curriculum/preview', [
            'mode' => 'preview',
            'curriculum' => $this->summarizeExport($export),
            'worlds' => $export['curriculum']['worlds'],
            'skills' => $export['skills'],
            'statusOptions' => ContentStatus::options(),
            'validation' => $this->publicationValidation($curriculum, $publicationService),
        ]);
    }

    public function edit(Curriculum $curriculum): Response
    {
        $this->authorize('update', $curriculum);

        return Inertia::render('admin/curriculum/form', [
            'mode' => 'edit',
            'curriculum' => [
                'title' => $curriculum->title,
                'description' => $curriculum->description ?? '',
                'target_min_age' => (string) ($curriculum->target_min_age ?? '6'),
                'target_max_age' => (string) ($curriculum->target_max_age ?? '7'),
                'duration_weeks' => (string) $curriculum->duration_weeks,
                'lessons_per_week' => (string) $curriculum->lessons_per_week,
                'version' => $curriculum->version,
                'status' => $this->statusValue($curriculum->status),
            ],
            'action' => route('admin.curriculum.update', $curriculum, absolute: false),
            'method' => 'put',
            'submitLabel' => 'Save curriculum',
            'modeLabel' => 'Edit curriculum',
            'statusOptions' => ContentStatus::options(),
        ]);
    }

    public function update(CurriculumRequest $request, Curriculum $curriculum): RedirectResponse
    {
        $this->authorize('update', $curriculum);
        $validated = $request->validated();
        $user = $request->user();

        DB::transaction(function () use ($curriculum, $validated, $user): void {
            $curriculum->fill([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'target_min_age' => $validated['target_min_age'] ?? null,
                'target_max_age' => $validated['target_max_age'] ?? null,
                'duration_weeks' => $validated['duration_weeks'],
                'lessons_per_week' => $validated['lessons_per_week'],
                'version' => $validated['version'],
            ]);
            $this->applyStatus($curriculum, ContentStatus::from($validated['status']));
            $curriculum->updated_by = $user?->id;
            $curriculum->save();
        });

        return to_route('admin.curriculum.show', $curriculum)->with('status', 'Curriculum updated successfully.');
    }

    public function publish(Curriculum $curriculum, CurriculumPublicationService $publicationService): RedirectResponse
    {
        $this->authorize('publish', $curriculum);
        $publicationService->publishCurriculum($curriculum);

        return to_route('admin.curriculum.show', $curriculum)->with('status', 'Curriculum published successfully.');
    }

    public function archive(Curriculum $curriculum, CurriculumPublicationService $publicationService): RedirectResponse
    {
        $this->authorize('archive', $curriculum);
        $publicationService->archiveCurriculum($curriculum);

        return to_route('admin.curriculum.show', $curriculum)->with('status', 'Curriculum archived successfully.');
    }

    public function restore(Curriculum $curriculum, CurriculumPublicationService $publicationService): RedirectResponse
    {
        $this->authorize('restore', $curriculum);
        $publicationService->restoreCurriculum($curriculum);

        return to_route('admin.curriculum.show', $curriculum)->with('status', 'Curriculum restored successfully.');
    }

    public function moveWorld(Curriculum $curriculum, CurriculumWorld $world, string $direction, CurriculumOrderingService $orderingService): RedirectResponse
    {
        $this->authorize('reorder', $curriculum);
        $this->ensureNestedBelongsToCurriculum($curriculum, $world);
        $this->ensureDirectionIsValid($direction);

        $orderingService->moveWorld($world, $direction);

        return to_route('admin.curriculum.show', $curriculum)->with('status', 'World order updated.');
    }

    public function moveUnit(Curriculum $curriculum, CurriculumWorld $world, CurriculumUnit $unit, string $direction, CurriculumOrderingService $orderingService): RedirectResponse
    {
        $this->authorize('reorder', $curriculum);
        $this->ensureNestedBelongsToCurriculum($curriculum, $world);
        abort_unless($unit->world_id === $world->id, 404);
        $this->ensureDirectionIsValid($direction);

        $orderingService->moveUnit($unit, $direction);

        return to_route('admin.curriculum.show', $curriculum)->with('status', 'Weekly unit order updated.');
    }

    public function moveLesson(Curriculum $curriculum, CurriculumWorld $world, CurriculumUnit $unit, CurriculumLesson $lesson, string $direction, CurriculumOrderingService $orderingService): RedirectResponse
    {
        $this->authorize('reorder', $curriculum);
        $this->ensureNestedBelongsToCurriculum($curriculum, $world);
        abort_unless($unit->world_id === $world->id, 404);
        abort_unless($lesson->unit_id === $unit->id, 404);
        $this->ensureDirectionIsValid($direction);

        $orderingService->moveLesson($lesson, $direction);

        return to_route('admin.curriculum.show', $curriculum)->with('status', 'Lesson order updated.');
    }

    public function moveStage(Curriculum $curriculum, CurriculumWorld $world, CurriculumUnit $unit, CurriculumLesson $lesson, LessonStage $stage, string $direction, CurriculumOrderingService $orderingService): RedirectResponse
    {
        $this->authorize('reorder', $curriculum);
        $this->ensureNestedBelongsToCurriculum($curriculum, $world);
        abort_unless($unit->world_id === $world->id, 404);
        abort_unless($lesson->unit_id === $unit->id, 404);
        abort_unless($stage->lesson_id === $lesson->id, 404);
        $this->ensureDirectionIsValid($direction);

        $orderingService->moveStage($stage, $direction);

        return to_route('admin.curriculum.show', $curriculum)->with('status', 'Stage order updated.');
    }

    public function duplicateWorld(Curriculum $curriculum, CurriculumWorld $world, CurriculumDuplicationService $duplicationService): RedirectResponse
    {
        $this->authorize('duplicate', $curriculum);
        $this->ensureNestedBelongsToCurriculum($curriculum, $world);
        $duplicationService->duplicateWorld($world);

        return to_route('admin.curriculum.show', $curriculum)->with('status', 'World duplicated successfully.');
    }

    public function duplicateUnit(Curriculum $curriculum, CurriculumWorld $world, CurriculumUnit $unit, CurriculumDuplicationService $duplicationService): RedirectResponse
    {
        $this->authorize('duplicate', $curriculum);
        $this->ensureNestedBelongsToCurriculum($curriculum, $world);
        abort_unless($unit->world_id === $world->id, 404);
        $duplicationService->duplicateUnit($unit);

        return to_route('admin.curriculum.show', $curriculum)->with('status', 'Weekly unit duplicated successfully.');
    }

    public function duplicateLesson(Curriculum $curriculum, CurriculumWorld $world, CurriculumUnit $unit, CurriculumLesson $lesson, CurriculumDuplicationService $duplicationService): RedirectResponse
    {
        $this->authorize('duplicate', $curriculum);
        $this->ensureNestedBelongsToCurriculum($curriculum, $world);
        abort_unless($unit->world_id === $world->id, 404);
        abort_unless($lesson->unit_id === $unit->id, 404);
        $duplicationService->duplicateLesson($lesson);

        return to_route('admin.curriculum.show', $curriculum)->with('status', 'Lesson duplicated successfully.');
    }

    public function duplicateStage(Curriculum $curriculum, CurriculumWorld $world, CurriculumUnit $unit, CurriculumLesson $lesson, LessonStage $stage, CurriculumDuplicationService $duplicationService): RedirectResponse
    {
        $this->authorize('duplicate', $curriculum);
        $this->ensureNestedBelongsToCurriculum($curriculum, $world);
        abort_unless($unit->world_id === $world->id, 404);
        abort_unless($lesson->unit_id === $unit->id, 404);
        abort_unless($stage->lesson_id === $lesson->id, 404);
        $duplicationService->duplicateStage($stage);

        return to_route('admin.curriculum.show', $curriculum)->with('status', 'Stage duplicated successfully.');
    }

    public function validatePublication(Curriculum $curriculum, CurriculumPublicationService $publicationService): RedirectResponse
    {
        $this->authorize('publish', $curriculum);
        $publicationService->validateCurriculum($curriculum);

        return to_route('admin.curriculum.show', $curriculum)->with('status', 'Curriculum validation passed.');
    }

    public function export(Curriculum $curriculum, CurriculumExportService $exportService)
    {
        $this->authorize('export', $curriculum);

        return response()->json($exportService->export($curriculum));
    }

    public function import(Request $request, CurriculumImportService $importService): RedirectResponse
    {
        $this->authorize('import', Curriculum::class);
        $payload = $request->validate([
            'payload' => ['required', 'string'],
        ]);

        $curriculum = DB::transaction(function () use ($importService, $payload): Curriculum {
            try {
                $decoded = json_decode($payload['payload'], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw ValidationException::withMessages([
                    'payload' => 'The curriculum import payload must be valid JSON.',
                ]);
            }

            if (! is_array($decoded)) {
                throw ValidationException::withMessages([
                    'payload' => 'The curriculum import payload must be valid JSON.',
                ]);
            }

            return $importService->import($decoded, false);
        });

        return to_route('admin.curriculum.show', $curriculum)->with('status', 'Curriculum imported successfully.');
    }

    private function publicationValidation(Curriculum $curriculum, CurriculumPublicationService $publicationService): array
    {
        try {
            $publicationService->validateCurriculum($curriculum);

            return [
                'is_publishable' => true,
                'messages' => [],
            ];
        } catch (ValidationException $exception) {
            return [
                'is_publishable' => false,
                'messages' => $exception->errors(),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $export
     */
    private function summarizeExport(array $export): array
    {
        $curriculum = $export['curriculum'];
        $worlds = collect($curriculum['worlds']);

        return [
            'title' => $curriculum['title'],
            'slug' => $curriculum['slug'],
            'description' => $curriculum['description'],
            'target_min_age' => $curriculum['target_min_age'],
            'target_max_age' => $curriculum['target_max_age'],
            'duration_weeks' => $curriculum['duration_weeks'],
            'lessons_per_week' => $curriculum['lessons_per_week'],
            'version' => $curriculum['version'],
            'status' => $curriculum['status'],
            'published_at' => $curriculum['published_at'],
            'worlds_count' => $worlds->count(),
            'units_count' => $worlds->sum(fn (array $world): int => count($world['units'] ?? [])),
            'lessons_count' => $worlds->sum(fn (array $world): int => collect($world['units'] ?? [])->sum(fn (array $unit): int => count($unit['lessons'] ?? []))),
            'stages_count' => $worlds->sum(fn (array $world): int => collect($world['units'] ?? [])->sum(fn (array $unit): int => collect($unit['lessons'] ?? [])->sum(fn (array $lesson): int => count($lesson['stages'] ?? [])))),
        ];
    }

    private function summarizeCurriculum(Curriculum $curriculum): array
    {
        $worlds = $curriculum->worlds;

        return [
            'id' => $curriculum->id,
            'title' => $curriculum->title,
            'slug' => $curriculum->slug,
            'description' => $curriculum->description,
            'status' => $this->statusValue($curriculum->status),
            'version' => $curriculum->version,
            'published_at' => $curriculum->published_at?->toIso8601String(),
            'target_min_age' => $curriculum->target_min_age,
            'target_max_age' => $curriculum->target_max_age,
            'duration_weeks' => $curriculum->duration_weeks,
            'lessons_per_week' => $curriculum->lessons_per_week,
            'worlds_count' => $worlds->count(),
            'units_count' => $worlds->sum(static fn ($world): int => $world->units->count()),
            'lessons_count' => $worlds->sum(static fn ($world): int => $world->units->sum(static fn ($unit): int => $unit->lessons->count())),
            'stages_count' => $worlds->sum(static fn ($world): int => $world->units->sum(static fn ($unit): int => $unit->lessons->sum(static fn ($lesson): int => $lesson->stages->count()))),
            'updated_at' => $curriculum->updated_at?->toIso8601String(),
            'edit_href' => route('admin.curriculum.edit', $curriculum, absolute: false),
            'builder_href' => route('admin.curriculum.show', $curriculum, absolute: false),
            'preview_href' => route('admin.curriculum.preview', $curriculum, absolute: false),
        ];
    }

    private function applyStatus(Curriculum $curriculum, ContentStatus $status): void
    {
        match ($status) {
            ContentStatus::Published => $curriculum->markPublished(),
            ContentStatus::Archived => $curriculum->markArchived(),
            default => $curriculum->markDraft(),
        };
    }

    private function ensureNestedBelongsToCurriculum(Curriculum $curriculum, CurriculumWorld $world): void
    {
        abort_unless($world->curriculum_id === $curriculum->getKey(), 404);
    }

    private function ensureDirectionIsValid(string $direction): void
    {
        abort_unless(in_array($direction, ['up', 'down'], true), 404);
    }

    private function statusValue(mixed $status): string
    {
        return $status instanceof \BackedEnum ? (string) $status->value : (string) $status;
    }
}
