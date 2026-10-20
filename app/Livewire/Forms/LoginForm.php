<?php

namespace App\Livewire\Forms;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = $this->only(['email', 'password']);

        if (! Auth::attempt($credentials, $this->remember)) {
            // Permitir ingreso tanto con "nombre.apellido@buinseguro.cl" como "nombreapellido@buinseguro.cl"
            $normalizedEmail = mb_strtolower(trim($this->email));
            if (str_ends_with($normalizedEmail, '@buinseguro.cl')) {
                $localNoDots = str_replace(['.', ' '], '', Str::before($normalizedEmail, '@'));
                $matchedUser = \App\Models\User::where('email', 'like', '%@buinseguro.cl')
                    ->get()
                    ->first(function ($u) use ($localNoDots) {
                        $uLocal = str_replace('.', '', Str::before(mb_strtolower($u->email), '@'));
                        return $uLocal === $localNoDots;
                    });

                if ($matchedUser && Auth::attempt(['email' => $matchedUser->email, 'password' => $this->password], $this->remember)) {
                    RateLimiter::clear($this->throttleKey());
                    return;
                }
            }

            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
