<x-layouts.guest title="Welcome back">
    <form method="post" action="{{ route('login.store') }}" class="flex flex-col gap-5">
        @csrf
        <x-auth-field name="email" label="Email" type="email" autocomplete="email" autofocus/>
        <x-auth-field name="password" label="Password" type="password" autocomplete="current-password"/>
        <div class="flex flex-wrap items-center justify-between gap-2 text-xs"><label class="flex items-center gap-2"><input type="checkbox" name="remember" value="1" @checked(old('remember')) class="accent-brand">Remember me</label><a href="{{ route('password.request') }}" class="auth-link-compact">Forgot password?</a></div>
        <button type="submit" class="auth-submit">Log in</button>
    </form>
    <div class="mt-7 space-y-3"><p class="text-center text-xs text-muted"></p><a href="{{ route('register') }}" class="auth-link">Create an account</a></div>
</x-layouts.guest>
