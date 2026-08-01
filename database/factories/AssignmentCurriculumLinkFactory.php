<?php

namespace Database\Factories;

use App\Models\AssignmentCurriculumLink;
use App\Models\AssignmentVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentCurriculumLink>
 */
class AssignmentCurriculumLinkFactory extends Factory
{
    protected $model = AssignmentCurriculumLink::class;

    public function definition(): array
    {
        return [
            'assignment_version_id' => AssignmentVersion::factory(),
            'curriculum_id' => null,
            'curriculum_world_id' => null,
            'curriculum_unit_id' => null,
            'curriculum_lesson_id' => null,
            'lesson_stage_id' => null,
        ];
    }
}
