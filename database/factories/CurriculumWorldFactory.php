<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\CurriculumWorld;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CurriculumWorld>
 */
class CurriculumWorldFactory extends Factory
{
    protected $model = CurriculumWorld::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'curriculum_id' => null,
            'name' => Str::headline($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999),
            'world_number' => fake()->numberBetween(1, 12),
            'short_description' => fake()->sentence(),
            'story_description' => fake()->paragraph(),
            'learning_outcomes' => [fake()->sentence()],
            'theme_colour' => fake()->hexColor(),
            'accent_colour' => fake()->hexColor(),
            'icon_path' => null,
            'cover_image_path' => null,
            'estimated_weeks' => 4,
            'display_order' => 1,
            'status' => ContentStatus::Draft->value,
            'published_at' => null,
        ];
    }
}
