<div role="status" aria-label="Loading coffee stories" class="flex flex-col gap-6">
    <span class="sr-only">Loading coffee stories…</span>
    @foreach(range(1, 2) as $card)
        <div aria-hidden="true" class="overflow-hidden rounded-md border border-line bg-surface motion-safe:animate-pulse">
            <div class="flex items-center gap-3 px-5 py-4">
                <div class="size-10 rounded-full bg-soft"></div>
                <div class="flex flex-col gap-2"><div class="h-3 w-28 rounded-sm bg-soft"></div><div class="h-2 w-40 rounded-sm bg-soft"></div></div>
            </div>
            <div class="aspect-[1.6] w-full bg-soft"></div>
            <div class="flex flex-col gap-4 p-5"><div class="h-4 w-24 rounded-sm bg-soft"></div><div class="h-5 w-3/4 rounded-sm bg-soft"></div><div class="h-3 w-full rounded-sm bg-soft"></div><div class="h-3 w-2/3 rounded-sm bg-soft"></div><div class="mt-2 h-7 w-32 rounded-sm bg-soft"></div></div>
        </div>
    @endforeach
</div>
