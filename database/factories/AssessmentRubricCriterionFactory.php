<?php

namespace Database\Factories;

use App\Models\AssessmentRubric;
use App\Models\AssessmentRubricCriterion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentRubricCriterion>
 */
class AssessmentRubricCriterionFactory extends Factory
{
    protected $model = AssessmentRubricCriterion::class;

    public function definition(): array
    {
        return [
            'assessment_rubric_id' => AssessmentRubric::factory(),
            'title' => fake()->sentence(2),
            'description' => fake()->sentence(),
            'maximum_points' => 5,
            'display_order' => 1,
        ];
    }
}
