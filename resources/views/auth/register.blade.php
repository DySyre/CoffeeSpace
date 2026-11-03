<x-layouts.guest title="Find your space">
    <form method="post" action="{{ route('register.store') }}" class="flex flex-col gap-5">
        @csrf
        <x-auth-field name="name" label="Name" autocomplete="name" autofocus/>
        <x-auth-field name="email" label="Email" type="email" autocomplete="email"/>
        <x-auth-field name="password" label="Password" type="password" autocomplete="new-password"/>
        <x-auth-field name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password"/>
        <button type="submit" class="auth-submit">Create account</button>
    </form>
    <p class="mt-7 text-center text-xs text-muted">Already here? <a href="{{ route('login') }}" class="font-medium text-brand underline underline-offset-4">Log in</a></p>
</x-layouts.guest>
