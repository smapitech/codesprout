<?php

namespace App\Services\Curriculum;

use App\Models\Curriculum;
use App\Models\Skill;

class CurriculumExportService
{
    public function export(Curriculum $curriculum): array
    {
        $curriculum->loadMissing([
            'worlds.units.lessons.stages.skills',
            'worlds.prerequisites',
            'worlds.units.lessons.skills',
            'worlds.units.lessons.stages.skills',
        ]);

        return [
            'schema_version' => 1,
            'exported_at' => now()->toIso8601String(),
            'curriculum' => [
                'title' => $curriculum->title,
                'slug' => $curriculum->slug,
                'description' => $curriculum->description,
                'target_min_age' => $curriculum->target_min_age,
                'target_max_age' => $curriculum->target_max_age,
                'duration_weeks' => $curriculum->duration_weeks,
                'lessons_per_week' => $curriculum->lessons_per_week,
                'version' => $curriculum->version,
                'status' => $this->stringStatus($curriculum->status),
                'published_at' => $curriculum->published_at?->toIso8601String(),
                'worlds' => $curriculum->worlds->map(fn ($world): array => [
                    'name' => $world->name,
                    'slug' => $world->slug,
                    'world_number' => $world->world_number,
                    'short_description' => $world->short_description,
                    'story_description' => $world->story_description,
                    'learning_outcomes' => $world->learning_outcomes ?? [],
                    'theme_colour' => $world->theme_colour,
                    'accent_colour' => $world->accent_colour,
                    'icon_path' => $world->icon_path,
                    'cover_image_path' => $world->cover_image_path,
                    'estimated_weeks' => $world->estimated_weeks,
                    'display_order' => $world->display_order,
                    'status' => $this->stringStatus($world->status),
                    'published_at' => $world->published_at?->toIso8601String(),
                    'prerequisite_slugs' => $world->prerequisites->pluck('slug')->all(),
                    'units' => $world->units->map(fn ($unit): array => [
                        'title' => $unit->title,
                        'slug' => $unit->slug,
                        'week_number' => $unit->week_number,
                        'description' => $unit->description,
                        'learning_outcomes' => $unit->learning_outcomes ?? [],
                        'display_order' => $unit->display_order,
                        'status' => $this->stringStatus($unit->status),
                        'published_at' => $unit->published_at?->toIso8601String(),
                        'lessons' => $unit->lessons->map(fn ($lesson): array => [
                            'title' => $lesson->title,
                            'slug' => $lesson->slug,
                            'lesson_number' => $lesson->lesson_number,
                            'description' => $lesson->description,
                            'teacher_notes' => $lesson->teacher_notes,
                            'learner_objective' => $lesson->learner_objective,
                            'estimated_minutes' => $lesson->estimated_minutes,
                            'difficulty_level' => $this->stringStatus($lesson->difficulty_level),
                            'display_order' => $lesson->display_order,
                            'status' => $this->stringStatus($lesson->status),
                            'published_at' => $lesson->published_at?->toIso8601String(),
                            'skill_slugs' => $lesson->skills->pluck('slug')->all(),
                            'stages' => $lesson->stages->map(fn ($stage): array => [
                                'title' => $stage->title,
                                'slug' => $stage->slug,
                                'stage_type' => $this->stringStatus($stage->stage_type),
                                'interaction_type' => $this->stringStatus($stage->interaction_type),
                                'instruction_text' => $stage->instruction_text,
                                'encouragement_text' => $stage->encouragement_text,
                                'teacher_guidance' => $stage->teacher_guidance,
                                'audio_instruction_path' => $stage->audio_instruction_path,
                                'estimated_minutes' => $stage->estimated_minutes,
                                'difficulty_level' => $this->stringStatus($stage->difficulty_level),
                                'star_value' => $stage->star_value,
                                'is_required' => $stage->is_required,
                                'display_order' => $stage->display_order,
                                'status' => $this->stringStatus($stage->status),
                                'published_at' => $stage->published_at?->toIso8601String(),
                                'configuration' => $stage->configuration ?? [],
                                'skill_slugs' => $stage->skills->pluck('slug')->all(),
                            ]),
                        ]),
                    ]),
                ]),
            ],
            'skills' => Skill::query()
                ->orderBy('name')
                ->get()
                ->map(fn ($skill): array => [
                    'name' => $skill->name,
                    'slug' => $skill->slug,
                    'category' => $skill->category,
                    'description' => $skill->description,
                    'mastery_description' => $skill->mastery_description,
                    'status' => $this->stringStatus($skill->status),
                    'published_at' => $skill->published_at?->toIso8601String(),
                ])
                ->all(),
        ];
    }

    private function stringStatus(mixed $value): ?string
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        return $value !== null ? (string) $value : null;
    }
}
