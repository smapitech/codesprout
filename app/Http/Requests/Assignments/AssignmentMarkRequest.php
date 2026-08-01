<?php

namespace App\Http\Requests\Assignments;

use App\Enums\AssignmentFeedbackType;
use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignmentMarkRequest extends FormRequest
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
            'manual_scores' => ['nullable', 'array'],
            'manual_scores.*' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'teacher_comment' => ['nullable', 'string'],
            'feedback_text' => ['nullable', 'string'],
            'feedback_type' => ['nullable', 'string', Rule::in(AssignmentFeedbackType::values())],
            'audio_feedback_path' => ['nullable', 'string', 'max:255'],
            'returned_for_retry' => ['nullable', 'boolean'],
            'visible_to_child' => ['nullable', 'boolean'],
            'visible_to_parent' => ['nullable', 'boolean'],
            'rubric_scores' => ['nullable', 'array'],
            'rubric_scores.*' => ['nullable', 'numeric', 'min:0', 'max:9999'],
        ];
    }
}
