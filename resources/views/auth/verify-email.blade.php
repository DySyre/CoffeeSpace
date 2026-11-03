<x-layouts.guest title="Check your inbox">
    <p class="mb-6 text-sm text-muted">Follow the link in your email to verify your account.</p>
    <form method="post" action="{{ route('verification.send') }}">@csrf<button type="submit" class="auth-submit">Resend verification email</button></form>
    <form method="post" action="{{ route('logout') }}" class="mt-5 text-center">@csrf<button type="submit" class="text-xs text-muted underline">Log out</button></form>
</x-layouts.guest>
