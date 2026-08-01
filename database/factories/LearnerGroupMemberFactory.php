<?php

namespace Database\Factories;

use App\Models\LearnerGroup;
use App\Models\LearnerGroupMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearnerGroupMember>
 */
class LearnerGroupMemberFactory extends Factory
{
    protected $model = LearnerGroupMember::class;

    public function definition(): array
    {
        return [
            'learner_group_id' => LearnerGroup::factory(),
            'child_id' => User::factory(),
        ];
    }
}
