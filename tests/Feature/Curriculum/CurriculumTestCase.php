<?php

namespace Tests\Feature\Curriculum;

use App\Enums\ContentStatus;
use App\Enums\DifficultyLevel;
use App\Enums\InteractionType;
use App\Enums\StageType;
use App\Models\Curriculum;
use App\Models\CurriculumLesson;
use App\Models\CurriculumUnit;
use App\Models\CurriculumWorld;
use App\Models\LessonStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

abstract class CurriculumTestCase extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::query()->where('email', 'admin@childsbridge.test')->firstOrFail();
    }

    protected function teacher(): User
    {
        return User::query()->where('email', 'teacher@childsbridge.test')->firstOrFail();
    }

    protected function parentUser(): User
    {
        return User::query()->where('email', 'parent@childsbridge.test')->firstOrFail();
    }

    protected function childUser(): User
    {
        return User::query()
            ->whereHas('childProfile', static fn ($query) => $query->where('learner_id', 'CB-LEARN-1001'))
            ->firstOrFail();
    }

    protected function seededCurriculum(): Curriculum
    {
        return Curriculum::query()->where('slug', 'codesprout-one-year-programme')->firstOrFail();
    }

    protected function seededWorld(string $slug): CurriculumWorld
    {
        return CurriculumWorld::query()->where('slug', $slug)->firstOrFail();
    }

    protected function seededUnit(string $slug): CurriculumUnit
    {
        return CurriculumUnit::query()->where('slug', $slug)->firstOrFail();
    }

    protected function seededLesson(string $slug): CurriculumLesson
    {
        return CurriculumLesson::query()->where('slug', $slug)->firstOrFail();
    }

    protected function seededStage(string $slug): LessonStage
    {
        return LessonStage::query()->where('slug', $slug)->firstOrFail();
    }

    protected function createDraftCurriculumTree(string $title = 'Future Learning Path'): Curriculum
    {
        $curriculum = Curriculum::query()->create([
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => 'A draft curriculum used for curriculum service tests.',
            'target_min_age' => 6,
            'target_max_age' => 7,
            'duration_weeks' => 4,
            'lessons_per_week' => 3,
            'version' => '1.0.0',
            'status' => ContentStatus::Draft->value,
            'published_at' => null,
        ]);

        $world = $curriculum->worlds()->create([
            'name' => 'Starter World',
            'slug' => 'starter-world',
            'world_number' => 1,
            'short_description' => 'A starter world for tests.',
            'story_description' => 'This world exists only for automated curriculum tests.',
            'learning_outcomes' => ['Describe the purpose of the starter world.'],
            'theme_colour' => '#2fb37b',
            'accent_colour' => '#f7b53b',
            'estimated_weeks' => 1,
            'display_order' => 1,
            'status' => ContentStatus::Draft->value,
            'published_at' => null,
        ]);

        $unit = $world->units()->create([
            'title' => 'Opening Week',
            'slug' => 'opening-week',
            'week_number' => 1,
            'description' => 'The first weekly unit in the draft tree.',
            'learning_outcomes' => ['Understand the opening week.'],
            'display_order' => 1,
            'status' => ContentStatus::Draft->value,
            'published_at' => null,
        ]);

        $lesson = $unit->lessons()->create([
            'title' => 'Watch the Guide',
            'slug' => 'watch-the-guide',
            'lesson_number' => 1,
            'description' => 'A short draft lesson for service tests.',
            'teacher_notes' => 'Guide children through the opening demo.',
            'learner_objective' => 'Watch the guide and follow one simple instruction.',
            'estimated_minutes' => 10,
            'difficulty_level' => DifficultyLevel::Introductory->value,
            'display_order' => 1,
            'status' => ContentStatus::Draft->value,
            'published_at' => null,
        ]);

        $lesson->stages()->create([
            'title' => 'Watch It',
            'slug' => 'watch-it',
            'stage_type' => StageType::Introduction->value,
            'interaction_type' => InteractionType::Watch->value,
            'instruction_text' => 'Watch the short demonstration.',
            'encouragement_text' => 'You can do this.',
            'teacher_guidance' => 'Ask the child to watch and listen first.',
            'estimated_minutes' => 5,
            'difficulty_level' => DifficultyLevel::Introductory->value,
            'star_value' => 5,
            'is_required' => true,
            'display_order' => 1,
            'status' => ContentStatus::Draft->value,
            'published_at' => null,
            'configuration' => [],
        ]);

        return $curriculum->load(['worlds.units.lessons.stages']);
    }
}
