<?php

namespace App\Http\Requests\Admin\Curriculum;

use App\Enums\ContentStatus;
use App\Enums\RoleName;
use App\Http\Requests\Admin\Curriculum\Concerns\NormalizesCurriculumData;
use App\Models\CurriculumUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CurriculumUnitRequest extends FormRequest
{
    use NormalizesCurriculumData;

    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleName::Administrator->value) ?? false;
    }

    public function rules(): array
    {
        $world = $this->route('world');
        $unit = $this->route('unit');

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'learning_outcomes' => ['required', 'array', 'min:1'],
            'learning_outcomes.*' => ['string'],
            'week_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('curriculum_units', 'week_number')
                    ->where(fn ($query) => $query->where('world_id', $world?->id))
                    ->ignore($unit instanceof CurriculumUnit ? $unit->getKey() : null),
            ],
            'display_order' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('curriculum_units', 'display_order')
                    ->where(fn ($query) => $query->where('world_id', $world?->id))
                    ->ignore($unit instanceof CurriculumUnit ? $unit->getKey() : null),
            ],
            'status' => ['required', 'string', Rule::in(ContentStatus::values())],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => $this->normalizeTitle((string) $this->input('title')),
            'description' => trim((string) $this->input('description')),
            'learning_outcomes' => $this->linesToArray((string) $this->input('learning_outcomes_text')),
        ]);
    }
}
