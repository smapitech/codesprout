<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\CurriculumUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CurriculumUnit>
 */
class CurriculumUnitFactory extends Factory
{
    protected $model = CurriculumUnit::class;

    public function definition(): array
    {
        $title = fake()->words(3, true);

        return [
            'world_id' => null,
            'title' => Str::headline($title),
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 999),
            'week_number' => fake()->numberBetween(1, 48),
            'description' => fake()->paragraph(),
            'learning_outcomes' => [fake()->sentence()],
            'display_order' => 1,
            'status' => ContentStatus::Draft->value,
            'published_at' => null,
        ];
    }
}
