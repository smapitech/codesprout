<?php

namespace Database\Factories;

use App\Models\AssessmentRubric;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentRubric>
 */
class AssessmentRubricFactory extends Factory
{
    protected $model = AssessmentRubric::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'created_by' => User::factory(),
        ];
    }
}
