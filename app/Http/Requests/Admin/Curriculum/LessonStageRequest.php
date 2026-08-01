<?php

namespace App\Http\Requests\Admin\Curriculum;

use App\Enums\ContentStatus;
use App\Enums\DifficultyLevel;
use App\Enums\InteractionType;
use App\Enums\RoleName;
use App\Enums\StageType;
use App\Http\Requests\Admin\Curriculum\Concerns\NormalizesCurriculumData;
use App\Models\LessonStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LessonStageRequest extends FormRequest
{
    use NormalizesCurriculumData;

    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleName::Administrator->value) ?? false;
    }

    public function rules(): array
    {
        $lesson = $this->route('lesson');
        $stage = $this->route('stage');

        return [
            'title' => ['required', 'string', 'max:255'],
            'stage_type' => ['required', 'string', Rule::in(StageType::values())],
            'interaction_type' => ['required', 'string', Rule::in(InteractionType::values())],
            'instruction_text' => ['required', 'string'],
            'encouragement_text' => ['nullable', 'string'],
            'teacher_guidance' => ['nullable', 'string'],
            'audio_instruction_path' => ['nullable', 'string', 'max:255'],
            'estimated_minutes' => ['required', 'integer', 'min:1'],
            'difficulty_level' => ['required', 'string', Rule::in(DifficultyLevel::values())],
            'star_value' => ['required', 'integer', 'min:0', 'max:100'],
            'is_required' => ['required', 'boolean'],
            'display_order' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('lesson_stages', 'display_order')
                    ->where(fn ($query) => $query->where('lesson_id', $lesson?->id))
                    ->ignore($stage instanceof LessonStage ? $stage->getKey() : null),
            ],
            'status' => ['required', 'string', Rule::in(ContentStatus::values())],
            'skill_ids' => ['nullable', 'array'],
            'skill_ids.*' => ['integer', 'exists:skills,id'],
            'prerequisite_stage_ids' => ['nullable', 'array'],
            'prerequisite_stage_ids.*' => ['integer', 'exists:lesson_stages,id'],
            'configuration' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => $this->normalizeTitle((string) $this->input('title')),
            'encouragement_text' => blank($this->input('encouragement_text')) ? null : trim((string) $this->input('encouragement_text')),
            'teacher_guidance' => blank($this->input('teacher_guidance')) ? null : trim((string) $this->input('teacher_guidance')),
            'skill_ids' => collect($this->input('skill_ids', []))
                ->map(static fn ($value): int => (int) $value)
                ->filter()
                ->values()
                ->all(),
            'prerequisite_stage_ids' => collect($this->input('prerequisite_stage_ids', []))
                ->map(static fn ($value): int => (int) $value)
                ->filter()
                ->values()
                ->all(),
            'configuration' => $this->jsonToArray((string) $this->input('configuration_text')),
        ]);
    }
}
