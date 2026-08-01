<?php

namespace App\Http\Requests\Auth;

use App\Models\ChildProfile;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChildPinLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'learner_id' => ['required', 'string', 'max:50'],
            'pin' => ['required', 'digits:4'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $childProfile = ChildProfile::query()
            ->where('learner_id', $this->string('learner_id'))
            ->with('user')
            ->first();

        if (! $childProfile || ! $childProfile->user || ! $childProfile->user->isActiveAccount()) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'learner_id' => __('auth.failed'),
            ]);
        }

        if (! Hash::check($this->string('pin'), $childProfile->pin_hash)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'pin' => __('auth.failed'),
            ]);
        }

        $childProfile->forceFill([
            'last_pin_verified_at' => now(),
        ])->save();

        Auth::login($childProfile->user, false);

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'learner_id' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('learner_id')).'|'.$this->ip());
    }
}
