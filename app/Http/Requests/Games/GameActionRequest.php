<?php

namespace App\Http\Requests\Games;

use Illuminate\Foundation\Http\FormRequest;

class GameActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('child') === true;
    }

    public function rules(): array
    {
        return [
            'round_number' => ['required', 'integer', 'min:1'],
            'response' => ['required', 'array'],
            'response_time_ms' => ['nullable', 'integer', 'min:0', 'max:300000'],
            'hint_used' => ['nullable', 'boolean'],
            'assistance_used' => ['nullable', 'boolean'],
        ];
    }
}
