<?php

namespace App\Livewire\Page;

use App\Auth\PendingEmailVerification;
use App\Models\User;
use App\Notifications\EmailChangeVerificationNotification;
use App\Notifications\VerifyEmailNotification;
use App\Services\EmailDeliveryLimiter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Throwable;

class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public string $emailChangeMessage = '';

    public string $bio = '';

    public string $birthdate = '';

    public bool $showActivity = true;

    public bool $notifyMessagesByEmail = false;

    public bool $notifyCommentsByEmail = false;

    public bool $notifySavesByEmail = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $notificationSettings = $user->notificationSettings;

        $this->name = $user->name;
        $this->email = $user->email;
        $this->bio = $user->bio ?? User::DEFAULT_BIO;
        $this->birthdate = $user->birthdate?->format('Y-m-d') ?? '';
        $this->showActivity = $user->show_activity;
        $this->notifyMessagesByEmail = $notificationSettings->notify_messages_by_email;
        $this->notifyCommentsByEmail = $notificationSettings->notify_comments_by_email;
        $this->notifySavesByEmail = $notificationSettings->notify_saves_by_email;
    }

    public function updatedShowActivity(): void
    {
        $this->validateOnly('showActivity', ['showActivity' => ['required', 'boolean']]);

        Auth::user()->show_activity = $this->showActivity;
        Auth::user()->save();
        $this->dispatch('presence-preference-updated', profileUuid: Auth::user()->profile_uuid, showActivity: $this->showActivity);
    }

    public function updatedNotifyMessagesByEmail(): void
    {
        $this->saveEmailPreference('notifyMessagesByEmail', 'notify_messages_by_email');
    }

    public function updatedNotifyCommentsByEmail(): void
    {
        $this->saveEmailPreference('notifyCommentsByEmail', 'notify_comments_by_email');
    }

    public function updatedNotifySavesByEmail(): void
    {
        $this->saveEmailPreference('notifySavesByEmail', 'notify_saves_by_email');
    }

    private function saveEmailPreference(string $property, string $column): void
    {
        $this->validateOnly($property, [$property => ['required', 'boolean']]);

        $user = Auth::user();
        $user->notificationSettings()->updateOrCreate([], [
            $column => $this->{$property},
        ]);
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $this->email = Str::lower(trim($this->email));

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:160'],
            'birthdate' => ['nullable', 'date', 'before_or_equal:today'],

            'email' => [
                'bail',
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ]);

        $requestedEmail = $validated['email'];
        unset($validated['email']);

        $user->fill($validated);
        $user->bio = trim($validated['bio'] ?? '');
        $user->birthdate = $validated['birthdate'] ?: null;
        $user->save();

        if ($requestedEmail !== $user->email) {
            $this->sendEmailChangeVerification($user, $requestedEmail);
        }

        $this->email = $user->email;

        $this->dispatch('profile-updated', name: $user->name);
    }

    private function sendEmailChangeVerification(User $user, string $email): void
    {
        $userCacheKey = 'pending-email-change-user:'.$user->id;
        $id = 'email-change-'.$user->id.'-'.hash('sha256', $email);
        $cacheKey = 'pending-email-change:'.$id;
        $expiresAt = now()->addMinutes((int) config('auth.verification.expire', 60));

        try {
            $sent = app(EmailDeliveryLimiter::class)->sendVerification(
                new PendingEmailVerification($id, $user->name, $email),
                $email,
                new EmailChangeVerificationNotification,
                function () use ($user, $userCacheKey, $id, $cacheKey, $email, $expiresAt): void {
                    $previousId = Cache::get($userCacheKey);

                    if (is_string($previousId)) {
                        Cache::forget('pending-email-change:'.$previousId);
                    }

                    Cache::put($cacheKey, Crypt::encryptString(json_encode([
                        'user_id' => $user->id,
                        'email' => $email,
                    ], JSON_THROW_ON_ERROR)), $expiresAt);
                    Cache::put($userCacheKey, $id, $expiresAt);
                },
            );
        } catch (Throwable $exception) {
            Cache::forget($cacheKey);
            Cache::forget($userCacheKey);
            report($exception);
            $this->addError('email', 'We could not send the verification link. Please try again shortly.');

            return;
        }

        if (! $sent) {
            $this->addError('email', 'A verification link was sent to this address recently. Please wait five minutes before requesting another.');

            return;
        }

        $this->emailChangeMessage = 'A verification link was sent to '.$email.'. Your current email stays active until you confirm the new address.';
    }

    public function updatedEmail(): void
    {
        $this->emailChangeMessage = '';
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $sent = app(EmailDeliveryLimiter::class)->sendVerification(
            $user,
            $user->getEmailForVerification(),
            new VerifyEmailNotification,
        );

        if (! $sent) {
            $this->addError('email', 'A verification link was sent recently. Please wait five minutes before requesting another.');

            return;
        }

        Session::flash('status', 'verification-link-sent');
        $this->dispatch('verification-email-sent');
    }

    public function render(): View
    {
        return view('livewire.page.Profile')->layout('components.layouts.app');
    }
}
