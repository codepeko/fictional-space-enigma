<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $username_rule = (strpos($this->email, '@') !== false) ? 'required|max:150|email' : 'required|alpha_num|max:20';
        return [
            'email' => $username_rule,
            'password' => 'required|string|min:6|max:100',
            'g-recaptcha-response' => (config('settings.captcha')) ? 'required|captcha' : 'nullable'
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

        if (strpos($this->email, '@') === false && Auth::attempt(["name" => $this->email, "password" => $this->password], $this->boolean('remember'))) {

            if (\Auth::user()->status == 2) {
                \Auth::logout();
                throw ValidationException::withMessages([
                    'email' => __('Your account is banned'),
                ]);                 
            }
            session()->flash('success',__('You succesfully logged in'));
        }        
        elseif (strpos($this->email, '@') !== false && Auth::attempt(["email" => $this->email, "password" => $this->password], $this->boolean('remember'))) {

            if (\Auth::user()->status == 2) {
                \Auth::logout();
                throw ValidationException::withMessages([
                    'email' => __('Your account is banned'),
                ]);                 
            }
            session()->flash('success',__('You succesfully logged in'));
        }
        else{
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);            
        }

        RateLimiter::clear($this->throttleKey());
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
        return Str::transliterate(Str::lower($this->input('email')).'|'.$this->ip());
    }
}
