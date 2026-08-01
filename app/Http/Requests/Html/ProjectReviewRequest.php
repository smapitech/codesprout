<?php

namespace App\Http\Requests\Html;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('teacher') === true;
    }

    public function rules(): array
    {
        return [
            'review_status' => ['required', 'string', Rule::in(['approved', 'changes_requested'])],
            'child_feedback' => ['required', 'string', 'max:2000'],
            'teacher_only_notes' => ['nullable', 'string', 'max:2000'],
            'requested_changes' => ['nullable', 'array', 'max:10'],
            'rubric_result' => ['nullable', 'array'],
            'release_to_parent' => ['nullable', 'boolean'],
        ];
    }
}
