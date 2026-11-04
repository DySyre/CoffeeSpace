<x-layouts.guest title="Reset your password">
    <form method="post" action="{{ route('password.email') }}" class="flex flex-col gap-5">
        @csrf
        <x-auth-field name="email" label="Email" type="email" autocomplete="email" autofocus/>
        <button type="submit" class="auth-submit">Send reset link</button>
    </form>
    <a href="{{ route('login') }}" class="auth-link mt-6">Back to login</a>
</x-layouts.guest>
