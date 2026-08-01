<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;

class LearningClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleName::Administrator->value) === true;
    }

    public function rules(): array
    {
        return [
            'academic_cohort_id' => ['required', 'integer', 'exists:academic_cohorts,id'],
            'class_code' => ['required', 'string', 'max:40', 'alpha_dash', 'unique:classes,class_code'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
