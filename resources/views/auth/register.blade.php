<x-layouts.guest title="Find your space">
    <form method="post" action="{{ route('register.store') }}" class="flex flex-col gap-5">
        @csrf
        <x-auth-field name="name" label="Name" autocomplete="name" autofocus/>
        <x-auth-field name="email" label="Email" type="email" autocomplete="email"/>
        <p class="-mt-2 text-xs leading-5 text-muted">We will email you a verification link. Your account is created only after you click it.</p>
        <x-auth-field name="password" label="Password" type="password" autocomplete="new-password"/>
        <x-auth-field name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password"/>
        <button type="submit" class="auth-submit">Create account</button>
    </form>
    <div class="mt-7 space-y-3"><p class="text-center text-xs text-muted">Already have an account?</p><a href="{{ route('login') }}" class="auth-link">Log in</a></div>
</x-layouts.guest>
