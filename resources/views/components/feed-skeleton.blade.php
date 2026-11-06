@props(['id' => 'discover-feed-preview'])

<skullet-reveal id="{{ $id }}" loading label="Loading coffee stories" class="discover-skullet flex flex-col gap-6">
    @foreach (range(1, 2) as $card)
        <article class="overflow-hidden rounded-lg border border-line bg-surface">
            <header class="flex items-center gap-3 px-5 py-4">
                <span class="size-10 shrink-0 rounded-full bg-soft" skullet-block></span>
                <span class="flex min-w-0 flex-1 flex-col gap-2">
                    <span class="h-3 w-28 rounded-sm bg-soft" skullet-block></span>
                    <span class="h-2.5 w-36 max-w-full rounded-sm bg-soft" skullet-block></span>
                </span>
            </header>
            <div class="aspect-[1.6] w-full bg-soft" skullet-block></div>
            <div class="flex flex-col gap-4 p-5">
                <div class="flex items-center gap-4 text-muted">
                    <span class="size-4 rounded-full bg-soft" skullet-block></span>
                    <span class="h-3 w-5 rounded-sm bg-soft" skullet-block></span>
                    <span class="size-4 rounded-sm bg-soft" skullet-block></span>
                    <span class="h-3 w-12 rounded-sm bg-soft" skullet-block></span>
                    <span class="ml-auto size-4 rounded-sm bg-soft" skullet-block></span>
                    <span class="h-3 w-10 rounded-sm bg-soft" skullet-block></span>
                </div>
                <div class="h-5 w-3/5 max-w-full rounded-sm bg-soft" skullet-block></div>
                <div class="flex flex-col gap-2" aria-hidden="true">
                    <span class="h-3 w-full rounded-sm bg-soft" skullet-block></span>
                    <span class="h-3 w-11/12 rounded-sm bg-soft" skullet-block></span>
                    <span class="h-3 w-2/3 rounded-sm bg-soft" skullet-block></span>
                </div>
                <div class="mt-2 h-7 w-32 rounded-sm bg-soft" skullet-block></div>
            </div>
        </article>
    @endforeach
</skullet-reveal>
