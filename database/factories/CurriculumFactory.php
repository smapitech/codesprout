<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Curriculum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Curriculum>
 */
class CurriculumFactory extends Factory
{
    protected $model = Curriculum::class;

    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'title' => Str::headline($title),
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 999),
            'description' => fake()->paragraph(),
            'target_min_age' => 6,
            'target_max_age' => 7,
            'duration_weeks' => 48,
            'lessons_per_week' => 3,
            'version' => '1.0.0',
            'status' => ContentStatus::Draft->value,
            'published_at' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
