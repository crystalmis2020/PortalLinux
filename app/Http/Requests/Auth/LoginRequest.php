<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $user = \App\Models\User::where('username', $this->username)->first();

        if (!$user || !$this->passwordMatches($user, (string) $this->password)) {
            RateLimiter::hit($this->throttleKey());

            if ($user && $this->matchesLowercasePassword($user, (string) $this->password)) {
                throw ValidationException::withMessages([
                    'username' => 'Password case mismatch. Please check uppercase/lowercase letters.',
                ]);
            }

            throw ValidationException::withMessages([
                'username' => __('auth.failed'),
            ]);
        }

        Auth::login($user);

        RateLimiter::clear($this->throttleKey());
    }

    protected function passwordMatches(User $user, string $password): bool
    {
        $storedHash = (string) $user->password;

        if (hash_equals($storedHash, hash('sha256', $password))) {
            return true;
        }

        if (!$this->isLaravelPasswordHash($storedHash)) {
            return false;
        }

        try {
            return Hash::check($password, $storedHash);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function matchesLowercasePassword(User $user, string $password): bool
    {
        if ($password === strtolower($password)) {
            return false;
        }

        return hash_equals((string) $user->password, hash('sha256', strtolower($password)));
    }

    protected function isLaravelPasswordHash(string $storedHash): bool
    {
        return preg_match('/^\$(2y|2a|2b|argon2i|argon2id)\$/', $storedHash) === 1;
    }



    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('username')).'|'.$this->ip());
    }
}
