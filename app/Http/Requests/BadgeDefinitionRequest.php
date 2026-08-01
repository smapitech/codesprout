<?php

namespace App\Http\Requests;

use App\Enums\BadgeCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BadgeDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('administrator') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'short_description' => ['required', 'string', 'max:500'],
            'long_description' => ['nullable', 'string'],
            'badge_category' => ['required', Rule::enum(BadgeCategory::class)],
            'badge_image_path' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'qualification_type' => ['required', 'string', 'max:80'],
            'qualification_configuration' => ['nullable', 'array'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'repeatable' => ['boolean'],
            'maximum_awards' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
