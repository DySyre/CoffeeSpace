@props([
    'code',
    'title',
    'message',
])

<x-layouts.guest :title="$title" :centered="true" :show-back-link="false">
    <div class="flex flex-col items-center text-center">
        <div aria-hidden="true" class="mb-7 flex size-16 items-center justify-center rounded-full border border-line bg-soft text-brand">
            {{ $slot }}
        </div>
        <p aria-hidden="true" class="font-display text-7xl leading-none font-semibold tabular-nums tracking-[-.04em] text-brand">{{ $code }}</p>
        <p class="mt-5 text-sm leading-6 text-muted">{{ $message }}</p>
        <a href="{{ route('home') }}" class="auth-submit mt-8">Back to Discover</a>
    </div>
</x-layouts.guest>
