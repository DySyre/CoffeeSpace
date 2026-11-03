<x-layouts.guest title="Welcome back">
    <form method="post" action="{{ route('login.store') }}" class="flex flex-col gap-5">
        @csrf
        <x-auth-field name="email" label="Email" type="email" autocomplete="email" autofocus/>
        <x-auth-field name="password" label="Password" type="password" autocomplete="current-password"/>
        <div class="flex items-center justify-between text-xs"><label class="flex items-center gap-2"><input type="checkbox" name="remember" value="1" @checked(old('remember')) class="accent-brand">Remember me</label><a href="{{ route('password.request') }}" class="text-muted underline underline-offset-4">Forgot password?</a></div>
        <button type="submit" class="auth-submit">Log in</button>
    </form>
    <p class="mt-7 text-center text-xs text-muted">New here? <a href="{{ route('register') }}" class="font-medium text-brand underline underline-offset-4">Create an account</a></p>
</x-layouts.guest>
