<div>
    <h1 class="sr-only">Discover coffee stories</h1>
    <div class="mx-auto max-w-[760px]">
        <section aria-label="Discover stories" class="min-w-0">
            <div class="mb-6 flex items-center justify-between border-b border-line">
                <div class="flex gap-1" aria-label="Feed filters">
                    @foreach(['for-you' => 'For You', 'nearby' => 'Nearby', 'following' => 'Following'] as $key => $label)
                        <button wire:click="$set('filter', '{{ $key }}')" aria-pressed="{{ $filter === $key ? 'true' : 'false' }}" class="feed-filter {{ $filter === $key ? 'border-brand text-brand' : 'border-transparent text-muted hover:border-line hover:text-ink' }}">{{ $label }}</button>
                    @endforeach
                </div>
                <div class="flex items-center gap-3"><span class="hidden text-xs text-muted sm:inline">Sample stories</span><button type="button" wire:click="$refresh" wire:loading.attr="disabled" class="flex min-h-11 min-w-11 items-center justify-center px-2 text-xs text-brand disabled:opacity-50" aria-label="Refresh feed"><span class="sm:hidden"><x-coffee-icon name="refresh"/></span><span class="hidden sm:inline">Refresh</span></button></div>
            </div>

            <label for="feed-search" class="sr-only">Search sample stories by café, creator, or drink</label>
            <div class="mb-5 flex items-center gap-3 rounded-md border border-line bg-surface px-4 py-3 sm:hidden"><x-coffee-icon name="search" class="size-4 text-muted"/><input id="feed-search" wire:model.live.debounce.300ms="search" maxlength="100" placeholder="Search café stories…" class="w-full bg-transparent text-sm outline-none" @if(request('focus') === 'search') autofocus @endif /></div>
            @if($search)<p class="mb-4 text-sm text-muted">Results for “{{ $search }}” <button wire:click="$set('search', '')" class="ml-2 text-brand underline">Clear search</button></p>@endif
            <div wire:loading wire:target.except="loadMore" class="w-full"><x-feed-skeleton/></div>
            <div class="flex flex-col gap-6" wire:loading.remove wire:target.except="loadMore" aria-live="polite">
                @forelse($stories as $story)
                    <article wire:key="story-{{ $story['id'] }}" class="overflow-hidden rounded-md border border-line bg-surface">
                        <div class="flex items-center gap-3 px-5 py-4"><span class="avatar {{ $story['id'] === 2 ? 'bg-[#d9c5ac]' : '' }}">{{ $story['initials'] }}</span><div><p class="text-sm font-semibold">{{ $story['handle'] }}</p><p class="mt-1 text-xs text-muted">{{ $story['city'] }} <span class="px-1">·</span> {{ $story['time'] }}</p></div></div>
                        @if(count($story['media']))
                            <x-post-gallery :media="$story['media']" :title="$story['title']" />
                        @elseif($story['image'])
                            <div class="relative"><img src="{{ asset('images/'.$story['image']) }}" alt="{{ $story['alt'] }}" class="aspect-[1.6] w-full object-cover" {{ $loop->first ? 'fetchpriority=high' : 'loading=lazy' }}/>@if($story['rating'])<span class="absolute right-4 bottom-4 flex items-center gap-1.5 rounded-sm bg-surface/95 px-2.5 py-1.5 text-xs font-semibold"><x-coffee-icon name="star" class="size-3.5 fill-caramel text-caramel"/> {{ $story['rating'] }} <span class="text-xs font-normal text-muted">/ 5</span></span>@endif</div>
                        @endif
                        <div class="p-5">
                            <div class="mb-4 flex items-center gap-4 text-muted"><a href="{{ route('register') }}" aria-label="Join CoffeeSpace to like stories" class="feed-action hover:text-brand"><x-coffee-icon name="heart"/>{{ $story['likes'] }}</a><button x-data="{ copied: false }" x-on:click="(async () => { try { await navigator.clipboard.writeText('{{ route('home') }}'); copied = true; setTimeout(() => copied = false, 2500) } catch (error) { copied = false; alert('Copy this page URL from your browser to share CoffeeSpace.') } })()" class="feed-action hover:text-brand" aria-label="Copy CoffeeSpace link"><x-coffee-icon name="share"/><span x-text="copied ? 'Copied!' : 'Share'">Share</span></button><a href="{{ route('register') }}" class="feed-action ml-auto hover:text-brand" aria-label="Join CoffeeSpace to save stories"><x-coffee-icon name="bookmark"/></a></div>
                            <h2 class="feed-title">{{ $story['title'] }}</h2><p class="feed-copy mt-3">{{ $story['story'] }}</p>
                            @if($story['latitude'] !== null && $story['longitude'] !== null)
                                <a href="https://www.openstreetmap.org/?mlat={{ $story['latitude'] }}&mlon={{ $story['longitude'] }}#map=17/{{ $story['latitude'] }}/{{ $story['longitude'] }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex min-h-11 items-center gap-2 text-sm text-brand"><x-coffee-icon name="map"/>View location pin</a>
                            @endif
                            <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-line pt-4"><a href="{{ route('coffee.map') }}" wire:navigate class="flex items-center gap-1 rounded-sm bg-soft px-2.5 py-1.5 text-xs text-brand"><x-coffee-icon name="map" class="size-3"/>{{ $story['cafe'] }}</a>@if($story['tag'])<span class="rounded-sm bg-page px-2.5 py-1.5 text-xs text-muted">{{ $story['tag'] }}</span>@endif @if($story['price'] !== null)<span class="ml-auto text-xs text-muted">₱{{ $story['price'] }} <span class="text-xs">/ cup</span></span>@endif</div>
                        </div>
                        <livewire:posts.post-comments :post-id="$story['id']" :comment-count="$story['comments_count']" :key="'post-comments-'.$story['id']" />
                    </article>
                @empty
                    <div class="rounded-md border border-line bg-surface px-6 py-14 text-center"><x-coffee-icon class="mx-auto mb-4 size-9 text-brand"/><h2 class="font-display text-2xl">{{ $filter === 'following' ? 'Your people. Your kind of coffee.' : 'No stories brewing here yet.' }}</h2><p class="mx-auto mt-3 max-w-xs text-sm leading-6 text-muted">{{ $filter === 'following' ? 'Following will bring your favorite creators together. Explore the sample stories while we build the community.' : 'Try another café, creator, or drink in the sample stories.' }}</p><button wire:click="$set('filter', 'for-you')" class="mt-5 text-sm text-brand underline">Back to For You</button></div>
                @endforelse
            </div>
            @if($hasMore)
                <div wire:key="feed-more-{{ $visibleCount }}-{{ $filter }}-{{ md5($search) }}" x-data="{ loading: false }"
                    x-intersect.margin.300px="if (!loading) { loading = true; $wire.loadMore({{ $visibleCount }}).finally(() => loading = false) }" class="py-6">
                    <div wire:loading wire:target="loadMore" class="w-full"><x-feed-skeleton/></div>
                    <button type="button" wire:click="loadMore({{ $visibleCount }})" wire:loading.attr="disabled" wire:target="loadMore" class="mx-auto block min-h-11 px-4 text-xs text-brand disabled:opacity-50">Load more stories</button>
                </div>
            @elseif($stories->isNotEmpty())
                <p wire:loading.remove class="py-8 text-center text-xs text-muted">You’re all caught up on the sample stories. Time for a coffee?</p>
            @endif
        </section>

    </div>
</div>
