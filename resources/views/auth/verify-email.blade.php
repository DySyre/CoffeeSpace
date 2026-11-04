<x-layouts.guest title="Check your inbox">
    <p class="mb-6 text-sm text-muted">Your account is almost ready. Follow the verification link we sent to your email address before continuing to CoffeeSpace.</p>
    <form method="post" action="{{ route('verification.send') }}">@csrf<button type="submit" class="auth-submit">Resend verification email</button></form>
    <form method="post" action="{{ route('logout') }}" class="mt-5 text-center">@csrf<button type="submit" class="text-xs text-muted underline">Log out</button></form>
</x-layouts.guest>
