<?php

namespace Database\Factories;

use App\Models\AssignmentAttempt;
use App\Models\AssignmentItem;
use App\Models\AssignmentResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentResponse>
 */
class AssignmentResponseFactory extends Factory
{
    protected $model = AssignmentResponse::class;

    public function definition(): array
    {
        return [
            'assignment_attempt_id' => AssignmentAttempt::factory(),
            'assignment_item_id' => AssignmentItem::factory(),
            'response_data' => ['value' => fake()->word()],
            'text_response' => fake()->sentence(),
            'is_correct' => null,
            'auto_score' => 0,
            'manual_score' => 0,
            'marked_by' => User::factory(),
            'marked_at' => null,
            'teacher_comment' => null,
        ];
    }
}
