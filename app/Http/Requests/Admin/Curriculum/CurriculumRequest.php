<?php

namespace App\Http\Requests\Admin\Curriculum;

use App\Enums\ContentStatus;
use App\Enums\RoleName;
use App\Http\Requests\Admin\Curriculum\Concerns\NormalizesCurriculumData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CurriculumRequest extends FormRequest
{
    use NormalizesCurriculumData;

    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleName::Administrator->value) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_min_age' => ['nullable', 'integer', 'min:3', 'max:18'],
            'target_max_age' => ['nullable', 'integer', 'min:3', 'max:18'],
            'duration_weeks' => ['required', 'integer', 'min:1'],
            'lessons_per_week' => ['required', 'integer', 'min:1'],
            'version' => ['required', 'string', 'max:50'],
            'status' => ['required', 'string', Rule::in(ContentStatus::values())],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => $this->normalizeTitle((string) $this->input('title')),
            'description' => blank($this->input('description')) ? null : trim((string) $this->input('description')),
            'version' => $this->normalizeTitle((string) $this->input('version')),
        ]);
    }
}
