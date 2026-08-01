<?php

namespace Database\Factories;

use App\Enums\AssignmentFeedbackType;
use App\Models\AssignmentAttempt;
use App\Models\AssignmentFeedback;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentFeedback>
 */
class AssignmentFeedbackFactory extends Factory
{
    protected $model = AssignmentFeedback::class;

    public function definition(): array
    {
        return [
            'assignment_attempt_id' => AssignmentAttempt::factory(),
            'teacher_id' => User::factory(),
            'feedback_text' => fake()->sentence(),
            'audio_feedback_path' => null,
            'feedback_type' => AssignmentFeedbackType::General->value,
            'returned_for_retry' => false,
            'visible_to_child' => true,
            'visible_to_parent' => true,
        ];
    }
}
