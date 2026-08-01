<?php

namespace App\Http\Requests\Html;

use App\Enums\HtmlExerciseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HtmlExerciseRequest extends FormRequest
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
            'exercise_type' => ['required', 'string', Rule::in(HtmlExerciseType::values())],
            'description' => ['nullable', 'string', 'max:4000'],
            'child_instructions' => ['required', 'string', 'max:2000'],
            'teacher_instructions' => ['nullable', 'string', 'max:4000'],
            'html_tag_policy_id' => ['required', 'integer', 'exists:html_tag_policies,id'],
            'content_configuration' => ['nullable', 'array'],
            'completion_configuration' => ['nullable', 'array'],
            'assistance_configuration' => ['nullable', 'array'],
            'preview_configuration' => ['nullable', 'array'],
            'assessment_configuration' => ['nullable', 'array'],
            'accessibility_configuration' => ['nullable', 'array'],
            'requirements' => ['required', 'array', 'min:1', 'max:25'],
            'requirements.*.requirement_type' => ['required', 'string', 'max:80'],
            'requirements.*.tag_name' => ['nullable', 'string', 'max:30'],
            'requirements.*.attribute_name' => ['nullable', 'string', 'max:60'],
            'requirements.*.expected_value' => ['nullable', 'string', 'max:500'],
            'requirements.*.minimum_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'requirements.*.maximum_count' => ['nullable', 'integer', 'min:0', 'max:30'],
            'requirements.*.required' => ['nullable', 'boolean'],
            'requirements.*.scoring_weight' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'requirements.*.safe_configuration' => ['nullable', 'array'],
        ];
    }
}
