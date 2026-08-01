<?php

namespace App\Http\Requests\Assignments;

use App\Enums\AllocationStatus;
use App\Enums\AssignmentScoringMethod;
use App\Enums\LateSubmissionPolicy;
use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignmentAllocationRequest extends FormRequest
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
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'group_id' => ['nullable', 'integer', 'exists:learner_groups,id'],
            'child_id' => ['nullable', 'integer', 'exists:users,id'],
            'available_from' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:available_from'],
            'closes_at' => ['nullable', 'date', 'after_or_equal:due_at'],
            'attempt_limit' => ['nullable', 'integer', 'min:1', 'max:10'],
            'scoring_method' => ['nullable', 'string', Rule::in(AssignmentScoringMethod::values())],
            'show_score_to_child' => ['nullable', 'boolean'],
            'show_correct_answers' => ['nullable', 'boolean'],
            'allow_late_submission' => ['nullable', 'boolean'],
            'late_submission_policy' => ['nullable', 'string', Rule::in(LateSubmissionPolicy::values())],
            'status' => ['nullable', 'string', Rule::in(AllocationStatus::values())],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'show_score_to_child' => $this->boolean('show_score_to_child'),
            'show_correct_answers' => $this->boolean('show_correct_answers'),
            'allow_late_submission' => $this->boolean('allow_late_submission'),
        ]);
    }
}
