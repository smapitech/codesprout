<?php

namespace Database\Factories;

use App\Models\AssignmentItem;
use App\Models\AssignmentItemOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentItemOption>
 */
class AssignmentItemOptionFactory extends Factory
{
    protected $model = AssignmentItemOption::class;

    public function definition(): array
    {
        return [
            'assignment_item_id' => AssignmentItem::factory(),
            'option_text' => fake()->words(2, true),
            'image_path' => null,
            'option_value' => fake()->word(),
            'is_correct' => false,
            'matching_key' => null,
            'display_order' => 1,
        ];
    }
}
