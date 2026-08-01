<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleName::Administrator->value) === true;
    }

    public function rules(): array
    {
        return [
            'connection_type' => ['required', Rule::in(['teacher_class', 'child_class', 'parent_child'])],
            'teacher_id' => ['exclude_unless:connection_type,teacher_class', 'required', 'integer', 'exists:users,id'],
            'class_id' => ['exclude_if:connection_type,parent_child', 'required', 'integer', 'exists:classes,id'],
            'child_id' => ['exclude_if:connection_type,teacher_class', 'required', 'integer', 'exists:users,id'],
            'parent_id' => ['exclude_unless:connection_type,parent_child', 'required', 'integer', 'exists:users,id'],
            'is_primary' => ['sometimes', 'boolean'],
            'relationship_type' => ['exclude_unless:connection_type,parent_child', 'nullable', 'string', 'max:40'],
            'role_label' => ['exclude_unless:connection_type,teacher_class', 'nullable', 'string', 'max:80'],
        ];
    }
}
