<?php

namespace Database\Factories;

use App\Enums\AttemptStatus;
use App\Models\AssignmentAllocation;
use App\Models\AssignmentAttempt;
use App\Models\AssignmentVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentAttempt>
 */
class AssignmentAttemptFactory extends Factory
{
    protected $model = AssignmentAttempt::class;

    public function definition(): array
    {
        return [
            'assignment_allocation_id' => AssignmentAllocation::factory(),
            'assignment_version_id' => AssignmentVersion::factory(),
            'child_id' => User::factory(),
            'attempt_number' => 1,
            'status' => AttemptStatus::NotStarted->value,
            'started_at' => null,
            'last_activity_at' => null,
            'submitted_at' => null,
            'auto_score' => 0,
            'manual_score' => 0,
            'final_score' => 0,
            'maximum_score' => 0,
            'time_spent_seconds' => 0,
            'hints_used' => 0,
            'is_late' => false,
        ];
    }
}
