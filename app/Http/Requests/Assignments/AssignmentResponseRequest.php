<?php

namespace App\Http\Requests\Assignments;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;

class AssignmentResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleName::Child->value) ?? false;
    }

    public function rules(): array
    {
        return [
            'response' => ['required'],
            'hint_used' => ['nullable', 'boolean'],
        ];
    }
}
