<?php

namespace App\Http\Controllers\Child;

use App\Http\Controllers\Controller;
use App\Models\Curriculum;
use App\Models\CurriculumWorld;
use App\Models\User;
use App\Services\ChildDashboardService;
use App\Services\Curriculum\CurriculumAvailabilityService;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class JourneyController extends Controller
{
    public function show(ChildDashboardService $dashboardService, CurriculumAvailabilityService $availabilityService, ?CurriculumWorld $world = null): Response
    {
        $child = request()->user();
        abort_unless($child instanceof User, 403);
        abort_unless($world === null || $world->isPublished(), 404);

        $child->loadMissing([
            'profile',
            'childProfile',
            'enrolledClasses.curriculumWorld.curriculum.worlds.units.lessons.stages.skills',
            'enrolledClasses.cohort',
            'enrolledClasses.teachers.profile',
        ]);

        $base = $dashboardService->build($child);
        $curriculum = $this->resolveCurriculum($child, $world);

        abort_unless($curriculum instanceof Curriculum, 404);

        $curriculum->loadMissing(['worlds.units.lessons.stages.skills']);
        $activeWorld = $world ?? $availabilityService->firstPublishedWorld($curriculum);
        $worldCards = $availabilityService->childWorldCards($curriculum, $activeWorld);
        $selectedWorld = $this->selectedWorld($worldCards, $activeWorld?->slug);

        return Inertia::render('child/journey', array_merge($base, [
            'journey' => [
                'curriculum' => [
                    'title' => $curriculum->title,
                    'slug' => $curriculum->slug,
                    'total_worlds' => $curriculum->worlds->count(),
                    'published_worlds' => $curriculum->worlds->where('status', 'published')->count(),
                ],
                'worlds' => $worldCards,
                'selected_world' => $selectedWorld,
                'selected_world_slug' => $selectedWorld['slug'] ?? null,
            ],
        ]));
    }

    private function resolveCurriculum(User $child, ?CurriculumWorld $selectedWorld): ?Curriculum
    {
        if ($selectedWorld?->curriculum instanceof Curriculum) {
            return $selectedWorld->curriculum;
        }

        $primaryClass = $child->enrolledClasses()
            ->with(['curriculumWorld.curriculum'])
            ->orderByPivot('is_primary_class', 'desc')
            ->orderBy('sort_order')
            ->first();

        if ($primaryClass?->curriculumWorld?->curriculum instanceof Curriculum) {
            return $primaryClass->curriculumWorld->curriculum;
        }

        return Curriculum::query()->with(['worlds.units.lessons.stages.skills'])->oldest('id')->first();
    }

    /**
     * @param  array<int, array<string, mixed>>  $worldCards
     * @return array<string, mixed>|null
     */
    private function selectedWorld(Collection|array $worldCards, ?string $slug): ?array
    {
        $collection = collect($worldCards);

        if ($slug === null) {
            return $collection->first();
        }

        return $collection->firstWhere('slug', $slug) ?? $collection->first();
    }
}
