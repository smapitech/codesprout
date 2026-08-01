<?php

namespace App\Services\Curriculum;

use App\Enums\ContentStatus;
use App\Enums\DifficultyLevel;
use App\Enums\InteractionType;
use App\Enums\StageType;
use App\Models\Curriculum;
use App\Models\CurriculumLesson;
use App\Models\CurriculumUnit;
use App\Models\CurriculumWorld;
use App\Models\LessonStage;
use App\Models\Skill;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CurriculumImportService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function import(array $payload, bool $dryRun = false): Curriculum
    {
        $this->validatePayload($payload);

        if ($dryRun) {
            return new Curriculum;
        }

        return DB::transaction(function () use ($payload): Curriculum {
            $curriculumData = $payload['curriculum'];
            $skills = collect($payload['skills'] ?? []);

            $curriculum = Curriculum::query()->updateOrCreate(
                ['slug' => $curriculumData['slug']],
                [
                    'title' => $curriculumData['title'],
                    'description' => $curriculumData['description'] ?? null,
                    'target_min_age' => $curriculumData['target_min_age'] ?? null,
                    'target_max_age' => $curriculumData['target_max_age'] ?? null,
                    'duration_weeks' => $curriculumData['duration_weeks'] ?? 48,
                    'lessons_per_week' => $curriculumData['lessons_per_week'] ?? 3,
                    'version' => $curriculumData['version'] ?? '1.0.0',
                    'status' => $this->normalizeStatus($curriculumData['status'] ?? ContentStatus::Draft->value),
                    'published_at' => isset($curriculumData['published_at']) ? Carbon::parse($curriculumData['published_at']) : null,
                ],
            );

            $skillMap = [];
            foreach ($skills as $skillData) {
                $skill = Skill::query()->updateOrCreate(
                    ['slug' => $skillData['slug']],
                    [
                        'name' => $skillData['name'],
                        'category' => $skillData['category'],
                        'description' => $skillData['description'] ?? null,
                        'mastery_description' => $skillData['mastery_description'] ?? null,
                        'status' => $this->normalizeStatus($skillData['status'] ?? ContentStatus::Draft->value),
                        'published_at' => isset($skillData['published_at']) ? Carbon::parse($skillData['published_at']) : null,
                    ],
                );

                $skillMap[$skill->slug] = $skill;
            }

            $worldMap = [];
            foreach ($curriculumData['worlds'] ?? [] as $worldData) {
                $world = CurriculumWorld::query()->updateOrCreate(
                    [
                        'curriculum_id' => $curriculum->getKey(),
                        'slug' => $worldData['slug'],
                    ],
                    [
                        'name' => $worldData['name'],
                        'world_number' => $worldData['world_number'],
                        'short_description' => $worldData['short_description'] ?? null,
                        'story_description' => $worldData['story_description'] ?? null,
                        'learning_outcomes' => $worldData['learning_outcomes'] ?? [],
                        'theme_colour' => $worldData['theme_colour'] ?? null,
                        'accent_colour' => $worldData['accent_colour'] ?? null,
                        'icon_path' => $worldData['icon_path'] ?? null,
                        'cover_image_path' => $worldData['cover_image_path'] ?? null,
                        'estimated_weeks' => $worldData['estimated_weeks'] ?? 4,
                        'display_order' => $worldData['display_order'] ?? $worldData['world_number'],
                        'status' => $this->normalizeStatus($worldData['status'] ?? ContentStatus::Draft->value),
                        'published_at' => isset($worldData['published_at']) ? Carbon::parse($worldData['published_at']) : null,
                    ],
                );

                $worldMap[$world->slug] = $world;
            }

            foreach ($curriculumData['worlds'] ?? [] as $worldData) {
                $world = $worldMap[$worldData['slug']];
                $prerequisiteIds = collect($worldData['prerequisite_slugs'] ?? [])
                    ->map(static fn (string $slug): int => (int) $worldMap[$slug]->getKey())
                    ->values()
                    ->all();

                $world->prerequisites()->sync($prerequisiteIds);

                foreach ($worldData['units'] ?? [] as $unitData) {
                    $unit = CurriculumUnit::query()->updateOrCreate(
                        [
                            'world_id' => $world->getKey(),
                            'slug' => $unitData['slug'],
                        ],
                        [
                            'title' => $unitData['title'],
                            'week_number' => $unitData['week_number'],
                            'description' => $unitData['description'] ?? null,
                            'learning_outcomes' => $unitData['learning_outcomes'] ?? [],
                            'display_order' => $unitData['display_order'] ?? $unitData['week_number'],
                            'status' => $this->normalizeStatus($unitData['status'] ?? ContentStatus::Draft->value),
                            'published_at' => isset($unitData['published_at']) ? Carbon::parse($unitData['published_at']) : null,
                        ],
                    );

                    foreach ($unitData['lessons'] ?? [] as $lessonData) {
                        $lesson = CurriculumLesson::query()->updateOrCreate(
                            [
                                'unit_id' => $unit->getKey(),
                                'slug' => $lessonData['slug'],
                            ],
                            [
                                'title' => $lessonData['title'],
                                'lesson_number' => $lessonData['lesson_number'],
                                'description' => $lessonData['description'] ?? null,
                                'teacher_notes' => $lessonData['teacher_notes'] ?? null,
                                'learner_objective' => $lessonData['learner_objective'] ?? null,
                                'estimated_minutes' => $lessonData['estimated_minutes'] ?? 10,
                                'difficulty_level' => $this->normalizeDifficulty($lessonData['difficulty_level'] ?? DifficultyLevel::Introductory->value),
                                'display_order' => $lessonData['display_order'] ?? $lessonData['lesson_number'],
                                'status' => $this->normalizeStatus($lessonData['status'] ?? ContentStatus::Draft->value),
                                'published_at' => isset($lessonData['published_at']) ? Carbon::parse($lessonData['published_at']) : null,
                            ],
                        );

                        $lesson->skills()->sync($this->skillIds($lessonData['skill_slugs'] ?? [], $skillMap));

                        foreach ($lessonData['stages'] ?? [] as $stageData) {
                            $stage = LessonStage::query()->updateOrCreate(
                                [
                                    'lesson_id' => $lesson->getKey(),
                                    'slug' => $stageData['slug'],
                                ],
                                [
                                    'title' => $stageData['title'],
                                    'stage_type' => $this->normalizeStageType($stageData['stage_type'] ?? StageType::Introduction->value),
                                    'interaction_type' => $this->normalizeInteractionType($stageData['interaction_type'] ?? InteractionType::Watch->value),
                                    'instruction_text' => $stageData['instruction_text'] ?? '',
                                    'encouragement_text' => $stageData['encouragement_text'] ?? null,
                                    'teacher_guidance' => $stageData['teacher_guidance'] ?? null,
                                    'audio_instruction_path' => $stageData['audio_instruction_path'] ?? null,
                                    'estimated_minutes' => $stageData['estimated_minutes'] ?? 5,
                                    'difficulty_level' => $this->normalizeDifficulty($stageData['difficulty_level'] ?? DifficultyLevel::Introductory->value),
                                    'star_value' => $stageData['star_value'] ?? 5,
                                    'is_required' => (bool) ($stageData['is_required'] ?? true),
                                    'display_order' => $stageData['display_order'] ?? 1,
                                    'status' => $this->normalizeStatus($stageData['status'] ?? ContentStatus::Draft->value),
                                    'published_at' => isset($stageData['published_at']) ? Carbon::parse($stageData['published_at']) : null,
                                    'configuration' => $stageData['configuration'] ?? [],
                                ],
                            );

                            $stage->skills()->sync($this->skillIds($stageData['skill_slugs'] ?? [], $skillMap));
                        }
                    }
                }
            }

            return $curriculum->fresh(['worlds.units.lessons.stages']);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function validatePayload(array $payload): void
    {
        $validator = Validator::make($payload, [
            'schema_version' => ['required', 'integer', Rule::in([1])],
            'curriculum.title' => ['required', 'string', 'max:255'],
            'curriculum.slug' => ['required', 'string', 'max:255'],
            'curriculum.description' => ['nullable', 'string'],
            'curriculum.target_min_age' => ['nullable', 'integer', 'min:3', 'max:18'],
            'curriculum.target_max_age' => ['nullable', 'integer', 'min:3', 'max:18'],
            'curriculum.duration_weeks' => ['required', 'integer', 'min:1'],
            'curriculum.lessons_per_week' => ['required', 'integer', 'min:1'],
            'curriculum.version' => ['nullable', 'string', 'max:50'],
            'curriculum.status' => ['required', 'string', Rule::in(ContentStatus::values())],
            'curriculum.worlds' => ['required', 'array', 'min:1'],
            'curriculum.worlds.*.name' => ['required', 'string', 'max:255'],
            'curriculum.worlds.*.slug' => ['required', 'string', 'max:255'],
            'curriculum.worlds.*.world_number' => ['required', 'integer', 'min:1'],
            'curriculum.worlds.*.display_order' => ['required', 'integer', 'min:1'],
            'curriculum.worlds.*.status' => ['required', 'string', Rule::in(ContentStatus::values())],
            'curriculum.worlds.*.units' => ['required', 'array', 'min:1'],
            'curriculum.worlds.*.units.*.title' => ['required', 'string', 'max:255'],
            'curriculum.worlds.*.units.*.slug' => ['required', 'string', 'max:255'],
            'curriculum.worlds.*.units.*.week_number' => ['required', 'integer', 'min:1'],
            'curriculum.worlds.*.units.*.display_order' => ['required', 'integer', 'min:1'],
            'curriculum.worlds.*.units.*.status' => ['required', 'string', Rule::in(ContentStatus::values())],
            'curriculum.worlds.*.units.*.lessons' => ['required', 'array', 'min:1'],
            'curriculum.worlds.*.units.*.lessons.*.title' => ['required', 'string', 'max:255'],
            'curriculum.worlds.*.units.*.lessons.*.slug' => ['required', 'string', 'max:255'],
            'curriculum.worlds.*.units.*.lessons.*.lesson_number' => ['required', 'integer', 'min:1'],
            'curriculum.worlds.*.units.*.lessons.*.estimated_minutes' => ['required', 'integer', 'min:1'],
            'curriculum.worlds.*.units.*.lessons.*.difficulty_level' => ['required', 'string', Rule::in(DifficultyLevel::values())],
            'curriculum.worlds.*.units.*.lessons.*.status' => ['required', 'string', Rule::in(ContentStatus::values())],
            'curriculum.worlds.*.units.*.lessons.*.stages' => ['required', 'array', 'min:1'],
            'curriculum.worlds.*.units.*.lessons.*.stages.*.title' => ['required', 'string', 'max:255'],
            'curriculum.worlds.*.units.*.lessons.*.stages.*.slug' => ['required', 'string', 'max:255'],
            'curriculum.worlds.*.units.*.lessons.*.stages.*.stage_type' => ['required', 'string', Rule::in(StageType::values())],
            'curriculum.worlds.*.units.*.lessons.*.stages.*.interaction_type' => ['required', 'string', Rule::in(InteractionType::values())],
            'curriculum.worlds.*.units.*.lessons.*.stages.*.instruction_text' => ['required', 'string'],
            'curriculum.worlds.*.units.*.lessons.*.stages.*.estimated_minutes' => ['required', 'integer', 'min:1'],
            'curriculum.worlds.*.units.*.lessons.*.stages.*.difficulty_level' => ['required', 'string', Rule::in(DifficultyLevel::values())],
            'curriculum.worlds.*.units.*.lessons.*.stages.*.status' => ['required', 'string', Rule::in(ContentStatus::values())],
            'skills' => ['required', 'array'],
            'skills.*.slug' => ['required', 'string', 'max:255'],
            'skills.*.name' => ['required', 'string', 'max:255'],
            'skills.*.category' => ['required', 'string', 'max:255'],
            'skills.*.status' => ['required', 'string', Rule::in(ContentStatus::values())],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * @param  array<int, string>  $skillSlugs
     * @param  array<string, Skill>  $skillMap
     * @return array<int, int>
     */
    private function skillIds(array $skillSlugs, array $skillMap): array
    {
        return collect($skillSlugs)
            ->filter()
            ->map(fn (string $slug): int => (int) ($skillMap[$slug]->getKey() ?? 0))
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeStatus(string $status): string
    {
        return in_array($status, ContentStatus::values(), true) ? $status : ContentStatus::Draft->value;
    }

    private function normalizeDifficulty(string $value): string
    {
        return in_array($value, DifficultyLevel::values(), true) ? $value : DifficultyLevel::Introductory->value;
    }

    private function normalizeStageType(string $value): string
    {
        return in_array($value, StageType::values(), true) ? $value : StageType::Introduction->value;
    }

    private function normalizeInteractionType(string $value): string
    {
        return in_array($value, InteractionType::values(), true) ? $value : InteractionType::Watch->value;
    }
}
