<?php

namespace Database\Factories;

use App\Enums\AssignmentFeedbackMode;
use App\Enums\AssignmentScoringMethod;
use App\Enums\ContentStatus;
use App\Enums\DifficultyLevel;
use App\Models\Assignment;
use App\Models\AssignmentVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentVersion>
 */
class AssignmentVersionFactory extends Factory
{
    protected $model = AssignmentVersion::class;

    public function definition(): array
    {
        return [
            'assignment_id' => Assignment::factory(),
            'version_number' => 1,
            'title' => fake()->sentence(3),
            'short_description' => fake()->sentence(),
            'child_instructions' => fake()->sentence(),
            'teacher_instructions' => fake()->paragraph(),
            'audio_instruction_path' => null,
            'estimated_minutes' => 10,
            'difficulty_level' => DifficultyLevel::Introductory->value,
            'total_points' => 10,
            'default_attempt_limit' => 1,
            'feedback_mode' => AssignmentFeedbackMode::AfterSubmission->value,
            'scoring_method' => AssignmentScoringMethod::LatestAttempt->value,
            'status' => ContentStatus::Draft->value,
            'published_at' => null,
            'published_by' => null,
            'settings' => [],
        ];
    }
}
