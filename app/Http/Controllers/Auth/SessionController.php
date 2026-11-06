<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class SessionController extends Controller
{
    public function store(Request $request): Response
    {
        if (is_string($email = $request->input('email'))) {
            $request->merge(['email' => Str::lower(trim($email))]);
        }

        $credentials = $request->validate([
            'email' => ['bail', 'required', 'string', 'lowercase', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);
        $key = Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            event(new Lockout($request));
            $retryAfter = max(1, RateLimiter::availableIn($key));

            throw new ThrottleRequestsException(__('auth.throttle', [
                'seconds' => $retryAfter,
                'minutes' => ceil($retryAfter / 60),
            ]), null, ['Retry-After' => (string) $retryAfter]);
        }

        $inactiveUser = User::query()->where('email', $credentials['email'])->where('is_active', false)->first();

        if ($inactiveUser && Hash::check($credentials['password'], $inactiveUser->password)) {
            return response()->view('errors.account-deactivated', status: 403);
        }

        if (! Auth::attempt(collect($credentials)->only('email', 'password')->all() + ['is_active' => true], $request->boolean('remember'))) {
            $attempts = RateLimiter::hit($key, 300);

            if ($attempts >= 5) {
                event(new Lockout($request));
                $retryAfter = max(1, RateLimiter::availableIn($key));

                throw new ThrottleRequestsException(__('auth.throttle', [
                    'seconds' => $retryAfter,
                    'minutes' => ceil($retryAfter / 60),
                ]), null, ['Retry-After' => (string) $retryAfter]);
            }

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(Auth::user()->isAdmin() ? route('admin.index') : route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
