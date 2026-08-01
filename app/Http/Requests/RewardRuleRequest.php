<?php

namespace App\Http\Requests;

use App\Enums\RewardRepeatPolicy;
use App\Enums\RewardType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RewardRuleRequest extends FormRequest
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
            'event_type' => ['required', 'string', 'max:120'],
            'source_type' => ['nullable', 'string', 'max:120'],
            'eligibility_conditions' => ['nullable', 'array'],
            'reward_type' => ['required', Rule::enum(RewardType::class)],
            'reward_amount' => ['required', 'integer', 'min:0', 'max:5000'],
            'badge_definition_id' => ['nullable', 'exists:badge_definitions,id'],
            'repeat_policy' => ['required', Rule::enum(RewardRepeatPolicy::class)],
            'maximum_awards' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'daily_cap' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'effective_from' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:effective_from'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:9999'],
        ];
    }
}
