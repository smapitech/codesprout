<?php

namespace App\Http\Requests\Games;

use App\Enums\GameDifficulty;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GameStartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('child') === true;
    }

    public function rules(): array
    {
        return [
            'difficulty' => ['nullable', 'string', Rule::in(GameDifficulty::values())],
            'client_session_identifier' => ['nullable', 'string', 'max:120'],
            'assignment_allocation_id' => ['nullable', 'integer', 'exists:assignment_allocations,id'],
            'assignment_attempt_id' => ['nullable', 'integer', 'exists:assignment_attempts,id'],
            'assignment_item_id' => ['nullable', 'integer', 'exists:assignment_items,id'],
            'lesson_stage_id' => ['nullable', 'integer', 'exists:lesson_stages,id'],
        ];
    }
}
