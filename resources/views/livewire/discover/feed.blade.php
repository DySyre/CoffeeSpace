<div x-data="pullToRefresh($wire)">
    <h1 class="sr-only">Discover coffee stories</h1>
    <div class="mx-auto max-w-[760px]">
        <section aria-label="Discover stories" class="min-w-0">
            <div x-cloak x-show="pullDistance > 0 || refreshing" x-bind:style="'height:' + indicatorHeight + 'px'" class="flex items-center justify-center gap-2 overflow-hidden text-sm text-brand sm:hidden" role="status" aria-live="polite">
                <button type="button" x-on:click="refreshFeed()" x-bind:disabled="refreshing || !refreshReady" aria-label="Refresh pulled feed" class="flex size-11 shrink-0 items-center justify-center rounded-full border border-line bg-surface disabled:opacity-70"><span x-bind:class="refreshing ? 'animate-spin motion-reduce:animate-none' : ''"><x-coffee-icon name="refresh"/></span></button>
                <span x-text="refreshing ? 'Refreshing...' : refreshReady ? 'Release to refresh' : 'Pull to refresh'"></span>
            </div>
            <button type="button" x-on:click="refreshFeed()" class="sr-only focus:not-sr-only focus:min-h-11 focus:px-3 focus:text-brand sm:hidden">Refresh stories</button>
            <p x-cloak x-show="refreshError" x-text="refreshError" role="alert" class="mb-3 text-sm text-red-700 dark:text-red-300"></p>
            <div class="mb-6 flex items-center justify-between border-b border-line">
                <div class="flex gap-1" aria-label="Feed filters">
                    @foreach(['for-you' => 'For You', 'nearby' => 'Nearby', 'following' => 'Following'] as $key => $label)
                        @if($key === 'nearby')
                            <button type="button" x-data="{ locating: false }"
                                x-on:click="if (locating) return; if ($wire.nearbyLatitude !== null && $wire.nearbyLongitude !== null) { $wire.setNearbyLocation($wire.nearbyLatitude, $wire.nearbyLongitude); return; } if (!navigator.geolocation) { $wire.nearbyLocationUnavailable(); return; } locating = true; navigator.geolocation.getCurrentPosition(position => { locating = false; $wire.setNearbyLocation(position.coords.latitude, position.coords.longitude); }, () => { locating = false; $wire.nearbyLocationUnavailable(); }, { enableHighAccuracy: false, maximumAge: 300000, timeout: 12000 });"
                                x-bind:disabled="locating" x-bind:aria-busy="locating.toString()" aria-pressed="{{ $filter === $key ? 'true' : 'false' }}" class="feed-filter {{ $filter === $key ? 'border-brand text-brand' : 'border-transparent text-muted hover:border-line hover:text-ink' }} disabled:opacity-60"><span x-text="locating ? 'Locating...' : 'Nearby'">Nearby</span></button>
                        @else
                            <button wire:click="$set('filter', '{{ $key }}')" aria-pressed="{{ $filter === $key ? 'true' : 'false' }}" class="feed-filter {{ $filter === $key ? 'border-brand text-brand' : 'border-transparent text-muted hover:border-line hover:text-ink' }}">{{ $label }}</button>
                        @endif
                    @endforeach
                </div>
                <div class="hidden items-center gap-3 sm:flex"><button type="button" wire:click="refreshFeed" wire:loading.attr="disabled" wire:target="refreshFeed" class="flex min-h-11 min-w-11 items-center justify-center px-2 text-xs text-brand disabled:opacity-50" aria-label="Refresh feed"><span wire:loading.remove wire:target="refreshFeed" class="hidden sm:inline">Refresh</span><span wire:loading wire:target="refreshFeed" class="hidden sm:inline">Refreshing…</span></button></div>
            </div>

            @if($filter === 'nearby' && ! $nearbyLocationError && $nearbyLatitude !== null)
                <p class="mb-4 text-sm text-muted">Showing stories within {{ number_format($nearbyRadiusKm, 0) }} km of your location.</p>
            @elseif($filter === 'nearby' && ! $nearbyLocationError && $nearbyLatitude === null)
                <p role="status" class="mb-4 text-sm text-muted">Select Nearby to share your location and find coffee stories in your area. Your location is only used for this search and is not saved.</p>
            @endif
            @if($nearbyLocationError)<p role="alert" class="mb-4 text-sm text-red-700 dark:text-red-300">{{ $nearbyLocationError }}</p>@endif
            @if($search && mb_strlen(trim($search)) < 3)
                <p role="status" class="mb-4 text-sm text-muted">Enter at least 3 characters to search.</p>
            @elseif($search)
                <p class="mb-4 text-sm text-muted">Results for &ldquo;{{ $search }}&rdquo; <button wire:click="$set('search', '')" class="ml-2 text-brand underline">Clear search</button></p>
            @endif
            <div wire:loading wire:target="filter, search, refreshFeed, setNearbyLocation, nearbyLocationUnavailable" class="w-full"><x-feed-skeleton id="discover-filter-preview"/></div>
            <div class="flex flex-col gap-6" wire:loading.remove wire:target="filter, search, refreshFeed, setNearbyLocation, nearbyLocationUnavailable" aria-live="polite">
                @if($search && $profileResults->isNotEmpty())
                    <section aria-labelledby="discover-people-heading" class="overflow-hidden rounded-lg border border-line bg-surface">
                        <h2 id="discover-people-heading" class="border-b border-line px-5 py-3 text-sm font-semibold">People</h2>
                        <ul class="divide-y divide-line">
                            @foreach($profileResults as $profile)
                                <li>
                                    <a href="{{ $profile->profileUrl() }}" wire:navigate class="flex min-h-14 items-center gap-3 px-4 py-3 text-sm font-medium text-ink transition-colors hover:bg-soft hover:text-brand sm:px-5">
                                        <x-user-avatar :user="$profile" />
                                        <span class="min-w-0 truncate">{{ $profile->name }}</span>
                                        <x-coffee-icon name="arrow" class="ml-auto size-4 shrink-0 text-muted" />
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
                @forelse($stories as $story)
                    <article wire:key="story-{{ $story['id'] }}" class="overflow-hidden rounded-lg border border-line bg-surface">
                        <div class="flex items-center gap-3 px-5 py-4"><a href="{{ $story['profile_url'] }}" wire:navigate aria-label="View {{ $story['name'] }} profile" class="avatar {{ $story['id'] === 2 ? 'bg-[#d9c5ac]' : '' }}">{{ $story['initials'] }}</a><div><a href="{{ $story['profile_url'] }}" wire:navigate class="text-sm font-semibold hover:text-brand">{{ $story['handle'] }}</a><p class="mt-1 text-xs text-muted">{{ $story['city'] }} <span class="px-1">·</span> {{ $story['time'] }}</p>@if($story['visibility'] === 'followers')<span class="mt-1 block text-xs font-medium text-brand">Followers only</span>@endif</div></div>
                        @if(count($story['media']))
                            <x-post-gallery :media="$story['media']" :title="$story['title']" :like-post-id="auth()->check() ? $story['id'] : null" />
                        @elseif($story['image'])
                            <div class="relative" @auth x-data="{ photoLikeVisible: false }" x-on:dblclick.prevent.stop="if (!photoLikeVisible) { photoLikeVisible = true; $wire.likePost({{ $story['id'] }}); setTimeout(() => photoLikeVisible = false, 620) }" @endauth>
                                <img src="{{ asset('images/'.$story['image']) }}" alt="{{ $story['alt'] }}" class="aspect-[1.6] w-full object-cover" {{ $loop->first ? 'fetchpriority=high' : 'loading=lazy' }}/>
                                @auth<span x-cloak x-show="photoLikeVisible" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="scale-100 opacity-100" x-transition:leave-end="scale-75 opacity-0" class="pointer-events-none absolute inset-0 flex items-center justify-center"><x-coffee-icon name="heart" class="size-24 fill-current text-red-500 drop-shadow-lg photo-like-pop"/></span>@endauth
                                @if($story['rating'])<span class="absolute right-4 bottom-4 flex items-center gap-1.5 rounded-sm bg-surface/95 px-2.5 py-1.5 text-xs font-semibold"><x-coffee-icon name="star" class="size-3.5 fill-caramel text-caramel"/> {{ $story['rating'] }} <span class="text-xs font-normal text-muted">/ 5</span></span>@endif
                            </div>
                        @endif
                        <div class="p-5">
                            <div class="mb-4 flex items-center gap-4 text-muted">
                                @auth
                                    <button type="button" x-data="{ actionMotion: '' }" x-on:click="actionMotion = $el.getAttribute('aria-pressed') === 'true' ? 'feed-action-shrink' : 'feed-action-pop'; setTimeout(() => actionMotion = '', 460);" wire:click="toggleLike({{ $story['id'] }})" wire:loading.attr="disabled" wire:target="toggleLike({{ $story['id'] }})" aria-pressed="{{ $story['liked'] ? 'true' : 'false' }}" aria-label="{{ $story['liked'] ? 'Unlike' : 'Like' }} {{ $story['title'] }}" class="feed-action hover:text-brand {{ $story['liked'] ? 'text-brand' : '' }}"><span class="relative flex size-6 items-center justify-center"><span aria-hidden="true" x-bind:class="actionMotion === 'feed-action-pop' ? 'like-rays-active' : ''" class="like-pop-rays"><i style="--ray-angle: 0deg"></i><i style="--ray-angle: 45deg"></i><i style="--ray-angle: 90deg"></i><i style="--ray-angle: 135deg"></i><i style="--ray-angle: 180deg"></i><i style="--ray-angle: 225deg"></i><i style="--ray-angle: 270deg"></i><i style="--ray-angle: 315deg"></i></span><x-coffee-icon name="heart" x-bind:class="actionMotion === 'feed-action-pop' ? 'like-heart-pop' : actionMotion" class="{{ $story['liked'] ? 'fill-current' : '' }}"/></span>{{ $story['likes'] }}</button>
                                @else
                                    <a href="{{ route('login') }}" aria-label="Log in to like stories" class="feed-action hover:text-brand"><x-coffee-icon name="heart"/>{{ $story['likes'] }}</a>
                                @endauth
                                <button x-data="{ copied: false, popping: false }" x-on:click="popping = true; setTimeout(() => popping = false, 380); (async () => { try { await navigator.clipboard.writeText('{{ $story['post_url'] }}'); copied = true; setTimeout(() => copied = false, 2500) } catch (error) { copied = false; alert('Copy this page URL from your browser to share CoffeeSpace.') } })()" class="feed-action hover:text-brand" aria-label="Copy post link"><x-coffee-icon name="share" x-bind:class="popping ? 'feed-action-pop' : ''"/><span x-text="copied ? 'Copied!' : 'Share'">Share</span></button>
                                @auth
                                    <button type="button" x-data="{ actionMotion: '' }" x-on:click="actionMotion = $el.getAttribute('aria-pressed') === 'true' ? 'feed-action-shrink' : 'feed-action-pop'; setTimeout(() => actionMotion = '', 380);" wire:click="toggleSave({{ $story['id'] }})" wire:loading.attr="disabled" wire:target="toggleSave({{ $story['id'] }})" aria-pressed="{{ $story['saved'] ? 'true' : 'false' }}" aria-label="{{ $story['saved'] ? 'Remove from saved' : 'Save' }} {{ $story['title'] }}" class="feed-action ml-auto hover:text-brand {{ $story['saved'] ? 'text-brand' : '' }}"><x-coffee-icon name="bookmark" x-bind:class="actionMotion" class="{{ $story['saved'] ? 'fill-current' : '' }}"/><span>{{ $story['saved'] ? 'Saved' : 'Save' }}</span></button>
                                @else
                                    <a href="{{ route('login') }}" class="feed-action ml-auto hover:text-brand" aria-label="Log in to save stories"><x-coffee-icon name="bookmark"/>Save</a>
                                @endauth
                            </div>
                            <h2 class="feed-title"><a href="{{ $story['post_url'] }}" wire:navigate>{{ $story['title'] }}</a></h2><p class="feed-copy mt-3">{{ $story['story'] }}</p>
                            <x-post-amenities :amenities="$story['amenities']" :tag="$story['tag']" :price="$story['price']"/>
                        </div>
                        <livewire:posts.post-comments :post-id="$story['id']" :comment-count="$story['comments_count']" :cafe-name="$story['cafe']" :tag="$story['tag']" :price="$story['price']" :key="'post-comments-'.$story['id']" />
                    </article>
                @empty
                    @if($profileResults->isEmpty())
                    <div class="rounded-lg border border-line bg-surface px-6 py-14 text-center">
                        <x-coffee-icon class="mx-auto mb-4 size-9 text-brand"/>
                        <h2 class="font-display text-2xl">{{ $filter === 'nearby' ? 'No nearby stories yet.' : ($filter === 'following' ? 'Your people. Your kind of coffee.' : 'No stories brewing here yet.') }}</h2>
                        <p class="mx-auto mt-3 max-w-xs text-sm leading-6 text-muted">{{ $filter === 'nearby' ? ($nearbyLocationError ?: ($nearbyLatitude === null ? 'Select Nearby above to share your location.' : 'No pinned coffee stories were found within '.number_format($nearbyRadiusKm, 0).' km.')) : ($filter === 'following' ? 'Follow creators from their profiles to see their public coffee stories here.' : 'Try another café, creator, or drink in the sample stories.') }}</p>
                        <button wire:click="$set('filter', 'for-you')" class="mt-5 text-sm text-brand underline">Back to For You</button>
                    </div>
                    @endif
                @endforelse
            </div>
            @if($hasMore)
                <div wire:key="feed-more-{{ $visibleCount }}-{{ $filter }}-{{ md5($search) }}" x-data="{ loading: false }"
                    x-intersect.margin.1000px="if (!loading) { loading = true; $wire.loadMore({{ $visibleCount }}).finally(() => loading = false) }" class="py-6">
                    <div wire:loading wire:target="loadMore" class="w-full"><x-feed-skeleton id="discover-load-more-preview"/></div>
                    <button type="button" wire:click="loadMore({{ $visibleCount }})" wire:loading.attr="disabled" wire:target="loadMore" class="mx-auto block min-h-11 px-4 text-xs text-brand disabled:opacity-50">Load more stories</button>
                </div>
            @elseif($stories->isNotEmpty())
                <p wire:loading.remove class="py-8 text-center text-xs text-muted">You’re all caught up on the sample stories. Time for a coffee?</p>
            @endif
        </section>

    </div>
</div>
