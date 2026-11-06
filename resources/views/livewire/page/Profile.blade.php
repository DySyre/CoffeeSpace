<section class="w-full" x-data="profileImageCropper($wire)" x-on:verification-email-sent.window="showToast('success', 'A new verification link has been sent to your email address.', 'Verification email sent')">
    @include('partials.settings-heading')

    <x-settings.layout heading="Profile" subheading="Update your name and email address">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" label="{{ __('Name') }}" type="text" name="name" required autofocus autocomplete="name" />
            <flux:input wire:model="birthdate" label="Birthdate" type="date" name="birthdate" max="{{ now()->toDateString() }}" />
            <flux:textarea wire:model="bio" label="Bio" rows="3" maxlength="160" />
            <p class="-mt-4 text-xs text-zinc-600 dark:text-zinc-400">Share a little about your coffee journey. Up to 160 characters.</p>

            <div>
                <flux:input wire:model="email" label="{{ __('Email') }}" type="email" name="email" required autocomplete="email" />
                <p class="mt-2 text-xs text-zinc-600 dark:text-zinc-400">Changing your email sends a verification link. Your current address stays active until you confirm the new one.</p>
                @if($emailChangeMessage)<p role="status" class="mt-2 text-sm text-brand">{{ $emailChangeMessage }}</p>@endif
                @if(session('status') === 'email-address-verified')<p role="status" class="mt-2 text-sm text-brand">Your new email address is verified and saved.</p>@endif
                @error('email')<p role="alert" class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                    <div>
                        <p class="mt-2 text-sm text-gray-800">
                            {{ __('Your email address is unverified.') }}

                            <button
                                wire:click.prevent="resendVerificationNotification"
                                class="mt-3 inline-flex min-h-11 items-center justify-center rounded-sm bg-gradient-to-r from-black via-zinc-900 to-zinc-700 px-5 text-sm font-semibold text-white shadow-sm transition hover:from-zinc-900 hover:to-black focus:outline-hidden focus:ring-2 focus:ring-black focus:ring-offset-2"
                            >
                                {{ __('Resend verification email') }}
                            </button>
                        </p>

                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <div class="my-8 border-t border-zinc-200 pt-6 dark:border-zinc-700">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Email notifications</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Choose which activity updates you receive by email. All are off by default.</p>
            <div class="mt-4 flex flex-col gap-5">
                <div class="flex items-center justify-between gap-6">
                    <div><p class="text-sm font-medium text-zinc-900 dark:text-white">New messages</p><p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">Email me when someone sends me a direct message.</p></div>
                    <flux:switch wire:model.live="notifyMessagesByEmail" aria-label="Email me about new messages" />
                </div>
                <div class="flex items-center justify-between gap-6">
                    <div><p class="text-sm font-medium text-zinc-900 dark:text-white">Comments on my posts</p><p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">Email me when someone comments on one of my posts.</p></div>
                    <flux:switch wire:model.live="notifyCommentsByEmail" aria-label="Email me about comments on my posts" />
                </div>
                <div class="flex items-center justify-between gap-6">
                    <div><p class="text-sm font-medium text-zinc-900 dark:text-white">People who save my posts</p><p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">Email me when someone saves one of my posts.</p></div>
                    <flux:switch wire:model.live="notifySavesByEmail" aria-label="Email me when someone saves my posts" />
                </div>
            </div>
        </div>

        <div class="my-8 border-t border-zinc-200 pt-6 dark:border-zinc-700">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Privacy</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Choose whether other people can see when you are online or when you were last active.</p>
            <div class="mt-4 flex items-center justify-between gap-6">
                <div>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Online status and last seen</p>
                    <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">When hidden, your activity status is not shown on your profile or in chats. This is separate from email message notifications.</p>
                </div>
                <flux:switch wire:model.live="showActivity" aria-label="Show online status and last seen" />
            </div>
            @error('showActivity')<p role="alert" class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
    <x-photo-toast/>
</section>
