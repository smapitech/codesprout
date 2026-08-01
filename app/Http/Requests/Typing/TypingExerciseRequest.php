<?php

namespace App\Http\Requests\Typing;

use App\Enums\ContentStatus;
use App\Enums\TypingBackspacePolicy;
use App\Enums\TypingCorrectionPolicy;
use App\Enums\TypingExerciseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TypingExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('administrator') === true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'exercise_type' => ['required', 'string', Rule::in(TypingExerciseType::values())],
            'description' => ['nullable', 'string'],
            'child_instructions' => ['required', 'string', 'max:2000'],
            'teacher_instructions' => ['nullable', 'string', 'max:4000'],
            'typing_difficulty_profile_id' => ['nullable', 'integer', 'exists:typing_difficulty_profiles,id'],
            'content_configuration' => ['nullable', 'array'],
            'case_sensitive' => ['nullable', 'string', Rule::in(['case_sensitive', 'case_insensitive'])],
            'backspace_policy' => ['nullable', 'string', Rule::in(TypingBackspacePolicy::values())],
            'correction_policy' => ['nullable', 'string', Rule::in(TypingCorrectionPolicy::values())],
            'input_method_policy' => ['nullable', 'string', Rule::in(['any', 'physical_keyboard', 'touch_allowed', 'assistive_allowed'])],
            'timer_configuration' => ['nullable', 'array'],
            'completion_criteria' => ['nullable', 'array'],
            'accuracy_requirement' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'speed_requirement' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'assistance_configuration' => ['nullable', 'array'],
            'adaptive_configuration' => ['nullable', 'array'],
            'skill_ids' => ['nullable', 'array'],
            'skill_ids.*' => ['integer', 'exists:skills,id'],
            'items' => ['required', 'array', 'min:1', 'max:80'],
            'items.*.item_type' => ['nullable', 'string', 'max:60'],
            'items.*.prompt_text' => ['required', 'string', 'max:500'],
            'items.*.expected_text' => ['required', 'string', 'max:180'],
            'items.*.display_text' => ['nullable', 'string', 'max:180'],
            'items.*.target_keys' => ['nullable', 'array', 'max:20'],
            'items.*.target_keys.*' => ['string', 'max:40'],
            'items.*.display_order' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', Rule::in(ContentStatus::values())],
        ];
    }
}
