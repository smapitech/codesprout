<?php

namespace Database\Factories;

use App\Enums\AssignmentType;
use App\Enums\ContentStatus;
use App\Models\Assignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'created_by' => null,
            'assignment_type' => AssignmentType::Mission->value,
            'status' => ContentStatus::Draft->value,
            'current_version_id' => null,
            'archived_at' => null,
        ];
    }
}
