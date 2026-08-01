<?php

namespace Database\Factories;

use App\Models\AssessmentRubricCriterion;
use App\Models\AssignmentAttempt;
use App\Models\AssignmentRubricScore;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentRubricScore>
 */
class AssignmentRubricScoreFactory extends Factory
{
    protected $model = AssignmentRubricScore::class;

    public function definition(): array
    {
        return [
            'assignment_attempt_id' => AssignmentAttempt::factory(),
            'rubric_criterion_id' => AssessmentRubricCriterion::factory(),
            'awarded_points' => 0,
            'teacher_comment' => null,
            'marked_by' => User::factory(),
        ];
    }
}
