<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;

class UserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleName::Administrator->value) === true;
    }

    public function rules(): array
    {
        return ['active' => ['required', 'boolean']];
    }
}
