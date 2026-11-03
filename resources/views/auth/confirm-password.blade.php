<x-layouts.guest title="Confirm your password">
    <form method="post" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-5">
        @csrf
        <x-auth-field name="password" label="Password" type="password" autocomplete="current-password" autofocus/>
        <button type="submit" class="auth-submit">Continue</button>
    </form>
</x-layouts.guest>
