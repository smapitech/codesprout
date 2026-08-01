<?php

namespace App\Http\Requests\Html;

use Illuminate\Foundation\Http\FormRequest;

class ProjectAutosaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('child') === true;
    }

    public function rules(): array
    {
        return [
            'autosave_uuid' => ['required', 'uuid'],
            'state_version' => ['required', 'integer', 'min:1'],
            'source_html' => ['required', 'string', 'max:8000'],
            'client_instance_id' => ['nullable', 'string', 'max:120'],
        ];
    }
}
