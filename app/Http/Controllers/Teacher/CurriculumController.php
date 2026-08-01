<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\CurriculumWorld;
use App\Models\User;
use App\Services\Curriculum\CurriculumAvailabilityService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CurriculumController extends Controller
{
    public function index(): Response
    {
        $teacher = request()->user();
        abort_unless($teacher instanceof User, 403);

        $teacher->loadMissing(['teachingClasses.curriculumWorld.units.lessons.stages', 'teachingClasses.cohort']);

        $worlds = $teacher->teachingClasses
            ->filter(fn ($class): bool => $class->curriculumWorld instanceof CurriculumWorld)
            ->groupBy('curriculum_world_id')
            ->map(function (Collection $classes) {
                $class = $classes->first();
                $world = $class->curriculumWorld;
                $lessonsCount = $world?->units->sum(static fn ($unit): int => $unit->lessons->count()) ?? 0;

                return [
                    'id' => $world?->id,
                    'slug' => $world?->slug,
                    'number' => $world?->world_number,
                    'name' => $world?->name,
                    'short_description' => $world?->short_description,
                    'status' => $world?->status instanceof \BackedEnum ? $world->status->value : $world?->status,
                    'theme_colour' => $world?->theme_colour,
                    'accent_colour' => $world?->accent_colour,
                    'classes' => $classes->map(static fn ($learningClass): array => [
                        'id' => $learningClass->id,
                        'name' => $learningClass->name,
                        'code' => $learningClass->class_code,
                    ])->values()->all(),
                    'units_count' => $world?->units->count() ?? 0,
                    'lessons_count' => $lessonsCount,
                    'preview_href' => route('teacher.curriculum.show', $world, absolute: false),
                ];
            })
            ->values();

        return Inertia::render('teacher/curriculum/index', [
            'worlds' => $worlds,
        ]);
    }

    public function show(CurriculumWorld $curriculumWorld, CurriculumAvailabilityService $availabilityService): Response
    {
        $teacher = request()->user();
        abort_unless($teacher instanceof User, 403);

        Gate::authorize('view', $curriculumWorld);

        $isAssigned = $teacher->teachingClasses()
            ->where('curriculum_world_id', $curriculumWorld->id)
            ->exists();

        abort_unless($isAssigned, 403);

        $curriculumWorld->loadMissing(['curriculum', 'units.lessons.stages.skills']);
        $worldCards = $availabilityService->childWorldCards($curriculumWorld->curriculum, $curriculumWorld);

        return Inertia::render('teacher/curriculum/show', [
            'curriculum' => [
                'title' => $curriculumWorld->curriculum->title,
                'slug' => $curriculumWorld->curriculum->slug,
            ],
            'world' => [
                'id' => $curriculumWorld->id,
                'slug' => $curriculumWorld->slug,
                'number' => $curriculumWorld->world_number,
                'name' => $curriculumWorld->name,
                'short_description' => $curriculumWorld->short_description,
                'story_description' => $curriculumWorld->story_description,
                'theme_colour' => $curriculumWorld->theme_colour,
                'accent_colour' => $curriculumWorld->accent_colour,
                'status' => $curriculumWorld->status instanceof \BackedEnum ? $curriculumWorld->status->value : $curriculumWorld->status,
                'units_count' => $curriculumWorld->units->count(),
                'lessons_count' => $curriculumWorld->units->sum(static fn ($unit): int => $unit->lessons->count()),
            ],
            'worlds' => $worldCards,
            'tree' => $worldCards
                ->where('slug', $curriculumWorld->slug)
                ->values()
                ->all(),
        ]);
    }
}
