<?php

namespace App\Http\Requests\Assignments;

use App\Enums\AssignmentFeedbackMode;
use App\Enums\AssignmentScoringMethod;
use App\Enums\AssignmentType;
use App\Enums\DifficultyLevel;
use App\Enums\InteractionType;
use App\Enums\QuestionType;
use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssignmentVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([
            RoleName::Administrator->value,
            RoleName::Teacher->value,
        ]) ?? false;
    }

    public function rules(): array
    {
        return [
            'assignment_type' => ['required', 'string', Rule::in(AssignmentType::values())],
            'title' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'child_instructions' => ['nullable', 'string'],
            'teacher_instructions' => ['nullable', 'string'],
            'audio_instruction_path' => ['nullable', 'string', 'max:255'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
            'difficulty_level' => ['nullable', 'string', Rule::in(DifficultyLevel::values())],
            'default_attempt_limit' => ['nullable', 'integer', 'min:1', 'max:10'],
            'feedback_mode' => ['nullable', 'string', Rule::in(AssignmentFeedbackMode::values())],
            'scoring_method' => ['nullable', 'string', Rule::in(AssignmentScoringMethod::values())],
            'settings' => ['nullable', 'array'],
            'items' => ['nullable', 'array'],
            'items.*.title' => ['nullable', 'string', 'max:255'],
            'items.*.prompt_text' => ['nullable', 'string'],
            'items.*.audio_prompt_path' => ['nullable', 'string', 'max:255'],
            'items.*.image_path' => ['nullable', 'string', 'max:255'],
            'items.*.html_exercise_version_id' => [
                'nullable', 'integer',
                Rule::exists('html_exercise_versions', 'id')->where(fn ($query) => $query->where('status', 'published')),
            ],
            'items.*.project_template_version_id' => [
                'nullable', 'integer',
                Rule::exists('project_template_versions', 'id')->where(fn ($query) => $query->where('status', 'published')),
            ],
            'items.*.question_type' => ['nullable', 'string', Rule::in(QuestionType::values())],
            'items.*.interaction_type' => ['nullable', 'string', Rule::in(InteractionType::values())],
            'items.*.points' => ['nullable', 'integer', 'min:0', 'max:100'],
            'items.*.is_required' => ['nullable', 'boolean'],
            'items.*.hint_text' => ['nullable', 'string'],
            'items.*.hint_audio_path' => ['nullable', 'string', 'max:255'],
            'items.*.explanation_text' => ['nullable', 'string'],
            'items.*.display_order' => ['nullable', 'integer', 'min:1'],
            'items.*.configuration' => ['nullable', 'array'],
            'items.*.grading_configuration' => ['nullable', 'array'],
            'items.*.options' => ['nullable', 'array'],
            'items.*.options.*.option_text' => ['nullable', 'string', 'max:255'],
            'items.*.options.*.image_path' => ['nullable', 'string', 'max:255'],
            'items.*.options.*.option_value' => ['nullable', 'string', 'max:255'],
            'items.*.options.*.is_correct' => ['nullable', 'boolean'],
            'items.*.options.*.matching_key' => ['nullable', 'string', 'max:255'],
            'items.*.options.*.display_order' => ['nullable', 'integer', 'min:1'],
            'curriculum_links' => ['nullable', 'array'],
            'curriculum_links.*.curriculum_id' => ['nullable', 'integer', 'exists:curricula,id'],
            'curriculum_links.*.curriculum_world_id' => ['nullable', 'integer', 'exists:curriculum_worlds,id'],
            'curriculum_links.*.curriculum_unit_id' => ['nullable', 'integer', 'exists:curriculum_units,id'],
            'curriculum_links.*.curriculum_lesson_id' => ['nullable', 'integer', 'exists:lessons,id'],
            'curriculum_links.*.lesson_stage_id' => ['nullable', 'integer', 'exists:lesson_stages,id'],
            'skill_ids' => ['nullable', 'array'],
            'skill_ids.*' => ['integer', 'exists:skills,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))->map(function (array $item): array {
            $item['html_exercise_version_id'] = filled($item['html_exercise_version_id'] ?? null) ? (int) $item['html_exercise_version_id'] : null;
            $item['project_template_version_id'] = filled($item['project_template_version_id'] ?? null) ? (int) $item['project_template_version_id'] : null;

            return $item;
        })->all();

        $this->merge(['items' => $items]);
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ($this->input('items', []) as $index => $item) {
                if (filled($item['html_exercise_version_id'] ?? null) && filled($item['project_template_version_id'] ?? null)) {
                    $validator->errors()->add("items.{$index}.html_exercise_version_id", 'Choose either an HTML exercise or a webpage project for this step, not both.');
                }
            }
        }];
    }
}
