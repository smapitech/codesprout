<?php

namespace App\Http\Requests\Html;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HtmlAttemptCompleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('child') === true || $this->user()?->hasAnyRole(['teacher', 'administrator']) === true;
    }

    public function rules(): array
    {
        return [
            'source_html' => ['required', 'string', 'max:8000'],
            'input_method' => ['nullable', 'string', Rule::in(['guided_code', 'visual_builder', 'symbol_palette', 'physical_keyboard', 'touch', 'assistive_input'])],
            'active_duration_ms' => ['nullable', 'integer', 'min:0', 'max:1800000'],
            'assistance_count' => ['nullable', 'integer', 'min:0', 'max:50'],
            'idempotency_key' => ['nullable', 'string', 'max:160'],
        ];
    }
}
