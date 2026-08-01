<?php

namespace App\Http\Requests\Games;

use App\Enums\ContentStatus;
use App\Enums\GameCategory;
use App\Enums\GameType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GameDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('administrator') === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(GameCategory::values())],
            'game_type' => ['required', 'string', Rule::in(GameType::values())],
            'description' => ['nullable', 'string'],
            'instructions' => ['required', 'string'],
            'status' => ['nullable', 'string', Rule::in(ContentStatus::values())],
            'configuration' => ['required', 'array'],
            'instruction_content' => ['nullable', 'array'],
            'difficulty_configuration' => ['nullable', 'array'],
            'supported_input_methods' => ['nullable', 'array'],
            'supported_input_methods.*' => ['string', Rule::in(['mouse', 'touch', 'keyboard'])],
        ];
    }
}
