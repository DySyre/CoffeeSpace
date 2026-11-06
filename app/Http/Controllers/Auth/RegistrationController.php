<?php

namespace App\Http\Controllers\Auth;

use App\Auth\PendingEmailVerification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class RegistrationController
{
    public function store(Request $request): RedirectResponse
    {
        if (is_string($email = $request->input('email'))) {
            $request->merge(['email' => Str::lower(trim($email))]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['bail', 'required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'string', 'min:8'],
        ]);

        // Breeze signs its verification URL and includes sha1(email) as the hash.
        // Reuse that URL format and key the encrypted pending record by its {id}.
        $id = hash('sha256', $validated['email']);
        $cacheKey = 'pending-registration:'.$id;
        $expiresAt = now()->addMinutes((int) config('auth.verification.expire', 60));

        Cache::put($cacheKey, Crypt::encryptString(json_encode([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ], JSON_THROW_ON_ERROR)), $expiresAt);

        try {
            (new PendingEmailVerification($id, $validated['name'], $validated['email']))
                ->notify(new VerifyEmailNotification);
        } catch (Throwable $exception) {
            Cache::forget($cacheKey);
            report($exception);

            return back()->withInput($request->only('name', 'email'))
                ->withErrors(['email' => 'We could not send the verification link. Please try again shortly.']);
        }

        return redirect()->route('register')->with(
            'status',
            'Check your email for a verification link. We will create your account after you verify your address.'
        );
    }
}
