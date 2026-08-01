<?php

namespace Database\Factories;

use App\Enums\AllocationStatus;
use App\Enums\AssignmentScoringMethod;
use App\Enums\LateSubmissionPolicy;
use App\Models\AssignmentAllocation;
use App\Models\AssignmentVersion;
use App\Models\LearningClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentAllocation>
 */
class AssignmentAllocationFactory extends Factory
{
    protected $model = AssignmentAllocation::class;

    public function definition(): array
    {
        return [
            'assignment_version_id' => AssignmentVersion::factory(),
            'assigned_by' => User::factory(),
            'class_id' => LearningClass::factory(),
            'group_id' => null,
            'child_id' => null,
            'available_from' => now(),
            'due_at' => now()->addWeek(),
            'closes_at' => now()->addWeeks(2),
            'attempt_limit' => 1,
            'scoring_method' => AssignmentScoringMethod::LatestAttempt->value,
            'show_score_to_child' => true,
            'show_correct_answers' => false,
            'allow_late_submission' => false,
            'late_submission_policy' => LateSubmissionPolicy::Block->value,
            'status' => AllocationStatus::Scheduled->value,
        ];
    }
}
