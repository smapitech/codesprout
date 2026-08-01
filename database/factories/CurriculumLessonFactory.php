<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\DifficultyLevel;
use App\Models\CurriculumLesson;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CurriculumLesson>
 */
class CurriculumLessonFactory extends Factory
{
    protected $model = CurriculumLesson::class;

    public function definition(): array
    {
        $title = fake()->words(3, true);

        return [
            'unit_id' => null,
            'title' => Str::headline($title),
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 999),
            'lesson_number' => fake()->numberBetween(1, 3),
            'description' => fake()->sentence(),
            'teacher_notes' => fake()->paragraph(),
            'learner_objective' => fake()->sentence(),
            'estimated_minutes' => fake()->numberBetween(5, 15),
            'difficulty_level' => DifficultyLevel::Introductory->value,
            'display_order' => 1,
            'status' => ContentStatus::Draft->value,
            'published_at' => null,
        ];
    }
}
