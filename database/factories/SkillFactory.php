<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Skill>
 */
class SkillFactory extends Factory
{
    protected $model = Skill::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'name' => Str::headline($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999),
            'category' => fake()->randomElement(['Keyboard', 'Typing', 'HTML', 'CSS', 'JavaScript', 'Logic']),
            'description' => fake()->sentence(),
            'mastery_description' => fake()->sentence(),
            'status' => ContentStatus::Draft->value,
            'published_at' => null,
        ];
    }
}
