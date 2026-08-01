<?php

namespace App\Http\Requests\Games;

use Illuminate\Foundation\Http\FormRequest;

class GameVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('administrator') === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'configuration' => ['required', 'array'],
            'instruction_content' => ['nullable', 'array'],
            'difficulty_configuration' => ['nullable', 'array'],
            'supported_input_methods' => ['nullable', 'array'],
            'supported_input_methods.*' => ['string', 'in:mouse,touch,keyboard'],
        ];
    }
}
