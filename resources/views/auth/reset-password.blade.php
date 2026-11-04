<x-layouts.guest title="Choose a new password">
    <form method="post" action="{{ route('password.update') }}" class="flex flex-col gap-5">
        @csrf
        <input type="hidden" name="token" value="{{ request()->route('token') }}">
        <input type="hidden" name="email" value="{{ old('email', request()->query('email', '')) }}">
        @if(request()->query('email'))
            <p class="-mt-3 text-sm text-muted">Resetting password for <span class="font-medium text-ink">{{ request()->query('email') }}</span></p>
        @endif
        @error('email')<p role="alert" class="text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
        <x-auth-field name="password" label="New password" type="password" autocomplete="new-password"/>
        <x-auth-field name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password"/>
        <button type="submit" class="auth-submit">Reset password</button>
    </form>
</x-layouts.guest>
