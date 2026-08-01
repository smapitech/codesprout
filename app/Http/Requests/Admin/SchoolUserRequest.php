<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleName::Administrator->value) === true;
    }

    public function rules(): array
    {
        $role = $this->string('role')->toString();
        $isChild = $role === RoleName::Child->value;

        return [
            'role' => ['required', Rule::in(array_map(fn (RoleName $role): string => $role->value, RoleName::cases()))],
            'name' => ['required', 'string', 'max:120'],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'email' => [$isChild ? 'nullable' : 'required', 'nullable', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => [$isChild ? 'nullable' : 'required', 'nullable', 'string', 'min:8', 'max:128'],
            'learner_id' => [$isChild ? 'required' : 'nullable', 'nullable', 'string', 'max:40', 'alpha_dash', 'unique:child_profiles,learner_id'],
            'pin' => [$isChild ? 'required' : 'nullable', 'nullable', 'digits_between:4,6'],
            'staff_code' => [$role === RoleName::Teacher->value ? 'required' : 'nullable', 'nullable', 'string', 'max:40', 'alpha_dash', 'unique:teacher_profiles,staff_code'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'subject_focus' => ['nullable', 'string', 'max:100'],
        ];
    }
}
