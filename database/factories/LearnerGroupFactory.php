<?php

namespace Database\Factories;

use App\Models\LearnerGroup;
use App\Models\LearningClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearnerGroup>
 */
class LearnerGroupFactory extends Factory
{
    protected $model = LearnerGroup::class;

    public function definition(): array
    {
        return [
            'class_id' => LearningClass::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
