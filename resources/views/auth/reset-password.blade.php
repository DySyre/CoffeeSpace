<x-layouts.guest title="Choose a new password">
    <form method="post" action="{{ route('password.update') }}" class="flex flex-col gap-5">
        @csrf
        <input type="hidden" name="token" value="{{ request()->route('token') }}">
        <x-auth-field name="email" label="Email" type="email" autocomplete="email" :value="request('email', '')"/>
        <x-auth-field name="password" label="New password" type="password" autocomplete="new-password"/>
        <x-auth-field name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password"/>
        <button type="submit" class="auth-submit">Reset password</button>
    </form>
</x-layouts.guest>
