<x-layouts.coffee title="Coffee Map · CoffeeSpace" section="map">
    <p class="text-[10px] font-semibold tracking-[.2em] text-muted uppercase">A change of scenery</p>
    <h1 class="mt-2 font-display text-4xl">Find your next <em class="text-brand">coffee corner.</em></h1>
    <p class="mt-3 mb-7 text-sm text-muted">Explore Meycauayan, Bulacan. No location permission needed.</p>
    <div class="grid gap-6 xl:grid-cols-[1fr_280px]">
        <div class="overflow-hidden rounded-md border border-line bg-soft"><iframe title="OpenStreetMap of Meycauayan, Bulacan" src="https://www.openstreetmap.org/export/embed.html?bbox=120.93%2C14.71%2C121.01%2C14.77&amp;layer=mapnik" class="h-[55vh] min-h-96 w-full border-0" loading="lazy" referrerpolicy="no-referrer"></iframe><a href="https://www.openstreetmap.org/#map=14/14.740/120.970" target="_blank" rel="noopener noreferrer" class="block px-4 py-3 text-xs text-brand underline">View larger map · © OpenStreetMap contributors</a></div>
        <section class="rounded-md border border-line bg-surface p-5"><x-coffee-icon name="map" class="mb-4 size-7 text-brand"/><h2 class="font-display text-2xl">A whole neighborhood to discover.</h2><p class="mt-3 text-sm leading-7 text-muted">This first map shows the local area. Verified café pins, visit filters, and café details are coming in the map milestone.</p><p class="mt-4 rounded-sm bg-soft p-3 text-xs leading-6 text-muted">The cafés in Discover are design samples. They are not verified map listings.</p><a href="{{ route('home') }}" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-brand">Back to Discover <x-coffee-icon name="arrow" class="size-4"/></a></section>
    </div>
</x-layouts.coffee>
