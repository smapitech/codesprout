<?php

namespace App\Http\Requests\Admin\Curriculum;

use App\Enums\ContentStatus;
use App\Enums\DifficultyLevel;
use App\Enums\RoleName;
use App\Http\Requests\Admin\Curriculum\Concerns\NormalizesCurriculumData;
use App\Models\CurriculumLesson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CurriculumLessonRequest extends FormRequest
{
    use NormalizesCurriculumData;

    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleName::Administrator->value) ?? false;
    }

    public function rules(): array
    {
        $unit = $this->route('unit');
        $lesson = $this->route('lesson');

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'teacher_notes' => ['nullable', 'string'],
            'learner_objective' => ['required', 'string'],
            'estimated_minutes' => ['required', 'integer', 'min:1'],
            'difficulty_level' => ['required', 'string', Rule::in(DifficultyLevel::values())],
            'lesson_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('lessons', 'lesson_number')
                    ->where(fn ($query) => $query->where('unit_id', $unit?->id))
                    ->ignore($lesson instanceof CurriculumLesson ? $lesson->getKey() : null),
            ],
            'display_order' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('lessons', 'display_order')
                    ->where(fn ($query) => $query->where('unit_id', $unit?->id))
                    ->ignore($lesson instanceof CurriculumLesson ? $lesson->getKey() : null),
            ],
            'status' => ['required', 'string', Rule::in(ContentStatus::values())],
            'skill_ids' => ['nullable', 'array'],
            'skill_ids.*' => ['integer', 'exists:skills,id'],
            'prerequisite_lesson_ids' => ['nullable', 'array'],
            'prerequisite_lesson_ids.*' => ['integer', 'exists:lessons,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => $this->normalizeTitle((string) $this->input('title')),
            'description' => blank($this->input('description')) ? null : trim((string) $this->input('description')),
            'teacher_notes' => blank($this->input('teacher_notes')) ? null : trim((string) $this->input('teacher_notes')),
            'skill_ids' => collect($this->input('skill_ids', []))
                ->map(static fn ($value): int => (int) $value)
                ->filter()
                ->values()
                ->all(),
            'prerequisite_lesson_ids' => collect($this->input('prerequisite_lesson_ids', []))
                ->map(static fn ($value): int => (int) $value)
                ->filter()
                ->values()
                ->all(),
        ]);
    }
}
