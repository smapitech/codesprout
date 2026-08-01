<?php

namespace App\Http\Requests\Typing;

use App\Enums\TypingInputMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TypingEventBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['child', 'administrator', 'teacher']) === true;
    }

    public function rules(): array
    {
        return [
            'batch_uuid' => ['required', 'uuid'],
            'events' => ['required', 'array', 'min:1', 'max:50'],
            'events.*.sequence_number' => ['required', 'integer', 'min:1', 'max:100000'],
            'events.*.typing_content_item_id' => ['nullable', 'integer', 'exists:typing_content_items,id'],
            'events.*.character_position' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'events.*.event_type' => ['required', 'string', Rule::in(['input', 'backspace', 'paste', 'prompt_replay', 'assistive_input'])],
            'events.*.expected_character' => ['nullable', 'string', 'max:20'],
            'events.*.entered_character' => ['nullable', 'string', 'max:20'],
            'events.*.correctness_state' => ['nullable', 'string', Rule::in(['correct', 'incorrect', 'corrected', 'assistance'])],
            'events.*.correction_sequence' => ['nullable', 'integer', 'min:1'],
            'events.*.input_method' => ['nullable', 'string', Rule::in(TypingInputMethod::values())],
            'events.*.modifier_state' => ['nullable', 'array'],
            'events.*.elapsed_offset_ms' => ['nullable', 'integer', 'min:0', 'max:3600000'],
            'events.*.metadata' => ['nullable', 'array'],
        ];
    }
}
