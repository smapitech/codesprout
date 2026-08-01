<?php

namespace App\Http\Requests\Admin\Curriculum;

use App\Enums\ContentStatus;
use App\Enums\RoleName;
use App\Http\Requests\Admin\Curriculum\Concerns\NormalizesCurriculumData;
use App\Models\CurriculumWorld;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CurriculumWorldRequest extends FormRequest
{
    use NormalizesCurriculumData;

    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleName::Administrator->value) ?? false;
    }

    public function rules(): array
    {
        $curriculum = $this->route('curriculum');
        $world = $this->route('world');

        return [
            'name' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string'],
            'story_description' => ['nullable', 'string'],
            'learning_outcomes' => ['required', 'array', 'min:1'],
            'learning_outcomes.*' => ['string'],
            'theme_colour' => ['nullable', 'string', 'max:32'],
            'accent_colour' => ['nullable', 'string', 'max:32'],
            'icon_path' => ['nullable', 'string', 'max:255'],
            'cover_image_path' => ['nullable', 'string', 'max:255'],
            'estimated_weeks' => ['required', 'integer', 'min:1'],
            'display_order' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('curriculum_worlds', 'display_order')
                    ->where(fn ($query) => $query->where('curriculum_id', $curriculum?->id))
                    ->ignore($world instanceof CurriculumWorld ? $world->getKey() : null),
            ],
            'status' => ['required', 'string', Rule::in(ContentStatus::values())],
            'prerequisite_world_ids' => ['nullable', 'array'],
            'prerequisite_world_ids.*' => ['integer', 'exists:curriculum_worlds,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->normalizeTitle((string) $this->input('name')),
            'short_description' => blank($this->input('short_description')) ? null : trim((string) $this->input('short_description')),
            'story_description' => blank($this->input('story_description')) ? null : trim((string) $this->input('story_description')),
            'learning_outcomes' => $this->linesToArray((string) $this->input('learning_outcomes_text')),
            'prerequisite_world_ids' => collect($this->input('prerequisite_world_ids', []))
                ->map(static fn ($value): int => (int) $value)
                ->filter()
                ->values()
                ->all(),
        ]);
    }
}
