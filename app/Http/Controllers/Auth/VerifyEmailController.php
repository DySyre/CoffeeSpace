<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use JsonException;

class VerifyEmailController extends Controller
{
    /**
     * Complete a pending registration or verify an existing account with a signed link.
     */
    public function __invoke(Request $request, string $id, string $hash): RedirectResponse
    {
        $cacheKey = 'pending-registration:'.$id;
        $encryptedPending = Cache::get($cacheKey);

        if ($encryptedPending !== null) {
            try {
                $pending = json_decode(Crypt::decryptString($encryptedPending), true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException|DecryptException) {
                Cache::forget($cacheKey);

                return redirect()->route('register')->withErrors([
                    'email' => 'This verification link is no longer valid. Please register again.',
                ]);
            }

            abort_unless(hash_equals(sha1($pending['email'] ?? ''), $hash), 403);

            if (User::where('email', $pending['email'])->exists()) {
                Cache::forget($cacheKey);

                return redirect()->route('login')->with('status', 'That email address already has an account. Please log in.');
            }

            $user = new User;
            $user->forceFill([
                'name' => $pending['name'],
                'email' => $pending['email'],
                'password' => $pending['password'],
                'email_verified_at' => now(),
            ])->save();

            Cache::forget($cacheKey);
            event(new Registered($user));
            event(new Verified($user));
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        $emailChangeCacheKey = 'pending-email-change:'.$id;
        $encryptedEmailChange = Cache::get($emailChangeCacheKey);

        if ($encryptedEmailChange !== null) {
            try {
                $emailChange = json_decode(Crypt::decryptString($encryptedEmailChange), true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException|DecryptException) {
                Cache::forget($emailChangeCacheKey);

                return redirect()->route('settings.profile')->withErrors([
                    'email' => 'This email verification link is no longer valid. Please request a new one.',
                ]);
            }

            abort_unless(hash_equals(sha1($emailChange['email'] ?? ''), $hash), 403);

            $user = $request->user();

            if (! $user) {
                return redirect()->guest(route('login'));
            }

            abort_unless((int) $emailChange['user_id'] === (int) $user->getKey(), 403);

            if (User::where('email', $emailChange['email'])->whereKeyNot($user->getKey())->exists()) {
                Cache::forget($emailChangeCacheKey);
                Cache::forget('pending-email-change-user:'.$user->id);

                return redirect()->route('settings.profile')->withErrors([
                    'email' => 'That email address is now in use. Please choose another address.',
                ]);
            }

            $user->forceFill([
                'email' => $emailChange['email'],
                'email_verified_at' => now(),
            ])->save();

            Cache::forget($emailChangeCacheKey);
            Cache::forget('pending-email-change-user:'.$user->id);
            event(new Verified($user));

            return redirect()->route('settings.profile')->with('status', 'email-address-verified');
        }

        // Preserve Breeze verification links issued to accounts created before this flow.
        $user = $request->user();

        if (! $user || ! ctype_digit($id) || ! hash_equals((string) $user->getKey(), $id)
            || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            if (! $user && ctype_digit($id)) {
                return redirect()->guest(route('login'));
            }

            throw new AuthorizationException;
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            /** @var MustVerifyEmail $user */
            event(new Verified($user));
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
