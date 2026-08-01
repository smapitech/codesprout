<?php

namespace App\Services\Curriculum;

use App\Enums\ContentStatus;
use App\Models\Curriculum;
use App\Models\CurriculumWorld;
use App\Models\User;
use Illuminate\Support\Collection;

class CurriculumAvailabilityService
{
    public function firstPublishedWorld(Curriculum $curriculum): ?CurriculumWorld
    {
        return $curriculum->worlds()
            ->published()
            ->orderBy('display_order')
            ->orderBy('world_number')
            ->first();
    }

    public function activeWorldForChild(User $child, Curriculum $curriculum): ?CurriculumWorld
    {
        $primaryClass = $child->enrolledClasses()
            ->with(['curriculumWorld'])
            ->orderByPivot('is_primary_class', 'desc')
            ->orderBy('sort_order')
            ->first();

        return $primaryClass?->curriculumWorld ?? $this->firstPublishedWorld($curriculum);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function childWorldCards(Curriculum $curriculum, ?CurriculumWorld $activeWorld = null): Collection
    {
        $firstPublishedWorld = $this->firstPublishedWorld($curriculum);

        return $curriculum->worlds
            ->filter(fn (CurriculumWorld $world): bool => $world->status === ContentStatus::Published)
            ->values()
            ->map(function (CurriculumWorld $world) use ($activeWorld, $firstPublishedWorld): array {
                $state = $this->worldState($world, $activeWorld, $firstPublishedWorld);

                return [
                    'id' => $world->id,
                    'slug' => $world->slug,
                    'number' => $world->world_number,
                    'name' => $world->name,
                    'short_description' => $world->short_description,
                    'story_description' => $world->story_description,
                    'theme_colour' => $world->theme_colour,
                    'accent_colour' => $world->accent_colour,
                    'display_order' => $world->display_order,
                    'status' => $state,
                    'openable' => $state === 'available',
                    'previewable' => in_array($state, ['available', 'preview'], true),
                    'units' => $world->units
                        ->filter(fn ($unit): bool => $unit->status === ContentStatus::Published)
                        ->map(fn ($unit): array => [
                            'id' => $unit->id,
                            'slug' => $unit->slug,
                            'title' => $unit->title,
                            'week_number' => $unit->week_number,
                            'description' => $unit->description,
                            'status' => $unit->status instanceof ContentStatus ? $unit->status->value : $unit->status,
                            'lessons' => $unit->lessons
                                ->filter(fn ($lesson): bool => $lesson->status === ContentStatus::Published)
                                ->map(fn ($lesson): array => [
                                    'id' => $lesson->id,
                                    'slug' => $lesson->slug,
                                    'title' => $lesson->title,
                                    'lesson_number' => $lesson->lesson_number,
                                    'estimated_minutes' => $lesson->estimated_minutes,
                                    'difficulty_level' => $lesson->difficulty_level instanceof \BackedEnum ? $lesson->difficulty_level->value : $lesson->difficulty_level,
                                    'stages' => $lesson->stages
                                        ->filter(fn ($stage): bool => $stage->status === ContentStatus::Published)
                                        ->map(fn ($stage): array => [
                                            'id' => $stage->id,
                                            'slug' => $stage->slug,
                                            'title' => $stage->title,
                                            'stage_type' => $stage->stage_type instanceof \BackedEnum ? $stage->stage_type->value : $stage->stage_type,
                                            'interaction_type' => $stage->interaction_type instanceof \BackedEnum ? $stage->interaction_type->value : $stage->interaction_type,
                                            'estimated_minutes' => $stage->estimated_minutes,
                                            'star_value' => $stage->star_value,
                                            'is_required' => $stage->is_required,
                                            'instruction_text' => $stage->instruction_text,
                                            'encouragement_text' => $stage->encouragement_text,
                                            'teacher_guidance' => $stage->teacher_guidance,
                                        ]),
                                ]),
                        ]),
                ];
            });
    }

    public function worldState(CurriculumWorld $world, ?CurriculumWorld $activeWorld, ?CurriculumWorld $firstPublishedWorld): string
    {
        if ($world->status !== ContentStatus::Published) {
            return 'hidden';
        }

        if ($activeWorld && $activeWorld->is($world)) {
            return 'available';
        }

        if ($firstPublishedWorld && $firstPublishedWorld->is($world)) {
            return 'available';
        }

        return 'preview';
    }
}
