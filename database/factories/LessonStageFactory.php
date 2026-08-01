<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\DifficultyLevel;
use App\Enums\InteractionType;
use App\Enums\StageType;
use App\Models\LessonStage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LessonStage>
 */
class LessonStageFactory extends Factory
{
    protected $model = LessonStage::class;

    public function definition(): array
    {
        $title = fake()->words(2, true);

        return [
            'lesson_id' => null,
            'title' => Str::headline($title),
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 999),
            'stage_type' => StageType::Introduction->value,
            'interaction_type' => InteractionType::Watch->value,
            'instruction_text' => fake()->sentence(),
            'encouragement_text' => fake()->sentence(),
            'teacher_guidance' => fake()->paragraph(),
            'audio_instruction_path' => null,
            'estimated_minutes' => fake()->numberBetween(2, 5),
            'difficulty_level' => DifficultyLevel::Introductory->value,
            'star_value' => 5,
            'is_required' => true,
            'display_order' => 1,
            'status' => ContentStatus::Draft->value,
            'published_at' => null,
            'configuration' => [],
        ];
    }
}
