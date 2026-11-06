<?php

namespace App\Livewire\Page;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Profile extends Component
{
    public string $name = '';

    public string $email = '';

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
        $settings = $user->notificationSettings()->firstOrNew();
        $settings->forceFill([$column => $this->{$property}]);
        $user->notificationSettings()->save($settings);
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->email = $user->email;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:160'],
            'birthdate' => ['nullable', 'date', 'before_or_equal:today'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ]);

        $user->fill($validated);
        $user->bio = trim($validated['bio'] ?? '');
        $user->birthdate = $validated['birthdate'] ?: null;

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
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

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
        $this->dispatch('verification-email-sent');
    }

    public function render(): View
    {
        return view('livewire.page.Profile')->layout('components.layouts.app');
    }
}
