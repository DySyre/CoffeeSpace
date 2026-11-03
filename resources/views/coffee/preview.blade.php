<x-layouts.coffee :title="($section === 'budget' ? 'Coffee Budget' : 'Add Experience').' · CoffeeSpace'" :section="$section">
    <p class="text-[10px] font-semibold tracking-[.2em] text-muted uppercase">Your coffee journey</p>
    <h1 class="mt-2 font-display text-4xl">{{ $section === 'budget' ? 'A little more mindful. Just as delicious.' : 'Every cup has a story.' }}</h1>
    <section class="mt-8 max-w-2xl rounded-md border border-line bg-surface p-8 sm:p-12">
        <x-coffee-icon :name="$section" class="mb-5 size-10 text-brand"/>
        <h2 class="font-display text-2xl">{{ $section === 'budget' ? 'Your coffee budget starts with your first visit.' : 'Your next coffee memory starts here.' }}</h2>
        <p class="mt-4 text-sm leading-7 text-muted">{{ $section === 'budget' ? 'Spending summaries will be calculated from your saved orders and extra expenses. There are no recorded visits yet.' : 'The experience editor is the next milestone: café, photos, story, ratings, and the little details of your order.' }}</p>
        <p class="mt-4 rounded-sm bg-soft px-4 py-3 text-xs leading-6 text-muted">Foundation preview — {{ $section === 'budget' ? 'budget analytics are not connected yet.' : 'posting and photo uploads are not enabled yet.' }}</p>
        <a href="{{ route('home') }}" class="mt-6 inline-flex items-center gap-3 rounded-sm bg-brand px-5 py-3 text-xs font-semibold text-on-brand">Discover a little inspiration <x-coffee-icon name="arrow" class="size-4"/></a>
    </section>
</x-layouts.coffee>
