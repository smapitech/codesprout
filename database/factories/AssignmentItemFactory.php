<?php

namespace Database\Factories;

use App\Enums\InteractionType;
use App\Enums\QuestionType;
use App\Models\AssignmentItem;
use App\Models\AssignmentVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentItem>
 */
class AssignmentItemFactory extends Factory
{
    protected $model = AssignmentItem::class;

    public function definition(): array
    {
        return [
            'assignment_version_id' => AssignmentVersion::factory(),
            'title' => fake()->sentence(2),
            'prompt_text' => fake()->sentence(),
            'audio_prompt_path' => null,
            'image_path' => null,
            'question_type' => QuestionType::MultipleChoice->value,
            'interaction_type' => InteractionType::Select->value,
            'points' => 5,
            'is_required' => true,
            'hint_text' => fake()->sentence(),
            'hint_audio_path' => null,
            'explanation_text' => fake()->paragraph(),
            'display_order' => 1,
            'configuration' => [],
            'grading_configuration' => [],
        ];
    }
}
