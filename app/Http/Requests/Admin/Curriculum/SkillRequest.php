<?php

namespace App\Http\Requests\Admin\Curriculum;

use App\Enums\ContentStatus;
use App\Enums\RoleName;
use App\Http\Requests\Admin\Curriculum\Concerns\NormalizesCurriculumData;
use App\Models\Skill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SkillRequest extends FormRequest
{
    use NormalizesCurriculumData;

    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleName::Administrator->value) ?? false;
    }

    public function rules(): array
    {
        $skill = $this->route('skill');

        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'mastery_description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(ContentStatus::values())],
            'published_at' => ['nullable', 'date'],
            'slug' => [
                'nullable',
                'string',
                Rule::unique('skills', 'slug')->ignore($skill instanceof Skill ? $skill->getKey() : null),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->normalizeTitle((string) $this->input('name')),
            'category' => $this->normalizeTitle((string) $this->input('category')),
            'description' => blank($this->input('description')) ? null : trim((string) $this->input('description')),
            'mastery_description' => blank($this->input('mastery_description')) ? null : trim((string) $this->input('mastery_description')),
        ]);
    }
}
