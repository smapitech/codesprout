<?php

namespace App\Http\Requests\Typing;

use App\Enums\TypingInputMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TypingStartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('child') === true;
    }

    public function rules(): array
    {
        return [
            'input_method' => ['nullable', 'string', Rule::in(TypingInputMethod::values())],
            'keyboard_layout' => ['nullable', 'string', Rule::in(['qwerty'])],
            'client_session_identifier' => ['nullable', 'string', 'max:120'],
            'assignment_allocation_id' => ['nullable', 'integer', 'exists:assignment_allocations,id'],
            'assignment_attempt_id' => ['nullable', 'integer', 'exists:assignment_attempts,id'],
            'assignment_item_id' => ['nullable', 'integer', 'exists:assignment_items,id'],
            'lesson_stage_id' => ['nullable', 'integer', 'exists:lesson_stages,id'],
            'session_type' => ['nullable', 'string', Rule::in(['practice', 'assignment', 'assessment'])],
        ];
    }
}
