<?php

namespace App\Http\Requests\Html;

use Illuminate\Foundation\Http\FormRequest;

class ProjectTemplateRequest extends FormRequest
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
            'description' => ['nullable', 'string', 'max:4000'],
            'html_tag_policy_id' => ['required', 'integer', 'exists:html_tag_policies,id'],
            'starter_source' => ['required', 'string', 'max:8000'],
            'project_configuration' => ['nullable', 'array'],
            'checklist_configuration' => ['nullable', 'array'],
            'validation_configuration' => ['nullable', 'array'],
            'preview_configuration' => ['nullable', 'array'],
            'rubric_configuration' => ['nullable', 'array'],
        ];
    }
}
