<div
    x-data="coffeeMap($wire, @js($postPins), @js($mapCenter), @js($currentLocation), @js($currentUser), @js($hasPlannedPins))"
    x-on:map-search-results.window="showSearchResults($event.detail.results)"
    x-on:map-place-saved.window="addWantToGoPlace($event.detail.place)"
    class="space-y-6"
>
    <p class="text-[10px] font-semibold tracking-[.2em] text-muted uppercase">Coffee Map</p>
    <h1 class="mt-2 font-display text-4xl">Find your next <em class="text-brand">coffee corner.</em></h1>
    <p class="mt-3 mb-7 max-w-2xl text-sm leading-6 text-muted">Explore places shared by the CoffeeSpace community. Your location is used only when you ask to search nearby, and is not saved to your profile.</p>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,.8fr)]">
        <section class="min-w-0 overflow-hidden rounded-md border border-line bg-surface" aria-label="CoffeeSpace map">
            <div wire:ignore x-ref="map" class="h-[55vh] min-h-96 w-full bg-soft" role="application" aria-label="Interactive map with pinned cafes and community posts"></div>
            <div class="flex flex-wrap items-center gap-x-5 gap-y-3 border-t border-line px-4 py-3" aria-label="Map legend">
                <label class="inline-flex cursor-pointer items-center gap-2 text-xs text-ink"><input type="checkbox" x-model="visibleLayers.been_here" x-on:change="drawPostPins()" class="size-4 accent-brand"><span class="map-burger-marker size-8"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4.5 10.5C4.5 7.4 7.7 5 11 5c1 0 1.8.2 2.5.5.7.3.6 1.1 1.3 1.2.7.1.8-.9 1.5-.8.8.1.5 1.2 1.1 1.6.6.5 1.6-.2 2.1.4.5.6-.1 1.4.2 2.6M4 13h16M5 16h14M6.5 19h11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="19" cy="5" r=".8" fill="var(--coffee-brand)"/><circle cx="21" cy="7" r=".65" fill="var(--coffee-brand)"/></svg></span>Been here</label>
                <label class="inline-flex cursor-pointer items-center gap-2 text-xs text-ink"><input type="checkbox" x-model="visibleLayers.want_to_go" x-on:change="drawPostPins()" class="size-4 accent-brand"><span class="map-status-legend map-want-marker"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M7 5.5h10v14l-5-3-5 3v-14Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>Want to go</label>
                <label class="inline-flex cursor-pointer items-center gap-2 text-xs text-ink"><input type="checkbox" x-model="visibleLayers.planned" x-on:change="drawPostPins()" class="size-4 accent-brand"><span class="map-status-legend map-planned-marker"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><rect x="4" y="5.5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M8 3.5v4M16 3.5v4M4 10h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="m8 15 2.2 2.2L16 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>Planned</label>
                @if($hasCommunityPins)
                    <span class="inline-flex items-center gap-2 text-xs text-ink"><span class="map-community-marker"></span>Community story</span>
                @endif
                <span x-cloak x-show="hasCurrentLocation" class="inline-flex items-center gap-2 text-xs text-ink">
                    <span class="map-current-avatar size-8">
                        @if($currentUser['photo'] ?? false)<img src="{{ $currentUser['photo'] }}" alt="">@else<span class="map-current-initials">{{ $currentUser['initials'] ?? 'You' }}</span>@endif
                    </span>Your location
                </span>
                <span class="ml-auto text-[10px] text-muted">Map data &copy; OpenStreetMap contributors</span>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3">
                <div>
                    <p class="text-xs text-muted">{{ count($postPins) }} {{ count($postPins) === 1 ? 'place' : 'places' }} on your map</p>
                    <p class="mt-1 text-[11px] text-muted">Add search results to Want to go, or pin a cafe in a post. “Been here” marks places you have posted about.</p>
                </div>
                <a href="https://www.openstreetmap.org/#map=14/{{ $mapCenter['latitude'] }}/{{ $mapCenter['longitude'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center gap-2 text-xs font-medium text-brand underline underline-offset-4">Open larger map <x-coffee-icon name="arrow" class="size-4"/></a>
            </div>
        </section>

        <section class="flex min-h-[420px] min-w-0 flex-col rounded-md border border-line bg-surface" aria-labelledby="map-place-search-heading">
            <div class="space-y-4 border-b border-line p-5">
                <div>
                    <h2 id="map-place-search-heading" class="font-display text-2xl font-semibold">Search the map</h2>
                    <p class="mt-2 text-sm leading-6 text-muted">Search by cafe, neighborhood, city, or address anywhere the map search can find it.</p>
                </div>
                <form wire:submit="searchMap" class="flex items-stretch gap-2">
                    <label class="sr-only" for="map-place-search">Search places on the map</label>
                    <input id="map-place-search" type="search" wire:model="mapSearchQuery" maxlength="200" placeholder="Cafe, city, or address" class="min-h-11 min-w-0 flex-1 rounded-sm border border-line bg-surface px-3 text-sm text-ink placeholder:text-muted" autocomplete="off">
                    <button type="submit" wire:loading.attr="disabled" wire:target="searchMap" class="inline-flex min-h-11 items-center gap-2 rounded-sm bg-brand px-4 text-sm font-medium text-on-brand disabled:opacity-60">
                        <span wire:loading.remove wire:target="searchMap">Search</span><span wire:loading wire:target="searchMap">Searching…</span><x-coffee-icon name="search" class="size-4"/>
                    </button>
                </form>
                @error('mapSearchQuery')<p role="alert" class="text-xs text-red-700 dark:text-red-300">Enter at least 3 characters to search.</p>@enderror
                @if($mapSearchError)<p role="alert" class="text-xs text-red-700 dark:text-red-300">{{ $mapSearchError }}</p>@endif
                @if($mapSearchCompleted)
                    <div class="space-y-2">
                        <p class="text-xs text-muted">{{ count($mapSearchResults) ? count($mapSearchResults).' map results' : 'No places found for that search.' }}</p>
                        @if(count($mapSearchResults))
                            <ul class="max-h-56 divide-y divide-line overflow-y-auto rounded-sm border border-line" aria-label="Map search results">
                                @foreach($mapSearchResults as $index => $place)
                                    <li wire:key="map-search-result-{{ $place['place_key'] }}" class="flex items-center gap-3 px-3 py-3">
                                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-soft text-brand"><x-coffee-icon name="map" class="size-4"/></span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-medium text-ink">{{ $place['name'] }}</span>
                                            <span class="mt-0.5 block truncate text-xs text-muted">{{ $place['address'] ?: $place['area'] }}</span>
                                        </span>
                                        @auth
                                            @if($place['want_to_go'])
                                                <span class="inline-flex shrink-0 items-center gap-1 text-xs font-medium text-brand"><x-coffee-icon name="check" class="size-4"/>In Want to go</span>
                                            @else
                                                <button type="button" wire:click="addMapPlaceToWantToGo({{ $index }})" wire:loading.attr="disabled" wire:target="addMapPlaceToWantToGo({{ $index }})" class="inline-flex min-h-10 shrink-0 items-center gap-1 rounded-sm border border-line px-2 text-xs font-medium text-brand hover:bg-soft disabled:opacity-50" aria-label="Add {{ $place['name'] }} to Want to go"><x-coffee-icon name="bookmark" class="size-4"/><span>Want to go</span></button>
                                            @endif
                                        @else
                                            <a href="{{ route('login') }}" class="shrink-0 text-xs font-medium text-brand underline underline-offset-4">Log in to save</a>
                                        @endauth
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </div>

            <div class="border-b border-line">
                <button type="button" x-on:click="nearbySettingsOpen = !nearbySettingsOpen" x-bind:aria-expanded="nearbySettingsOpen.toString()" aria-controls="nearby-search-settings" class="flex min-h-14 w-full items-center justify-between gap-3 px-5 py-3 text-left font-semibold text-ink">
                    <span>Nearby search settings</span><x-coffee-icon name="chevron-down" x-bind:class="nearbySettingsOpen ? 'rotate-180' : ''" class="size-4 text-muted transition-transform"/>
                </button>
                <div id="nearby-search-settings" x-cloak x-show="nearbySettingsOpen" class="space-y-5 px-5 pb-5">
                    <p class="text-sm leading-6 text-muted">Choose place types and a search distance. Allow location access to find nearby results with OpenStreetMap.</p>
                <fieldset class="mt-4">
                    <legend class="text-xs font-semibold text-ink">Place types</legend>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach(['cafe' => 'Cafe', 'burger' => 'Burger', 'matcha' => 'Matcha', 'fastfood' => 'Fast food'] as $value => $label)
                            <label class="flex min-h-10 cursor-pointer items-center gap-2 rounded-sm border border-line px-3 text-xs text-brand has-checked:border-brand has-checked:bg-soft">
                                <input type="checkbox" wire:model="selectedCategories" value="{{ $value }}" class="size-4 accent-brand">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @error('categories')<p role="alert" class="mt-2 text-xs text-red-700 dark:text-red-300">Choose at least one place type.</p>@enderror
                </fieldset>

                <fieldset class="mt-5">
                    <legend class="text-xs font-semibold text-ink">Search distance</legend>
                    <div class="mt-2 grid grid-cols-3 gap-2" aria-label="Distance presets">
                        @foreach(['1.0' => '1 km', '3.0' => '3 km', '5.0' => '5 km'] as $value => $label)
                            <button type="button" wire:click="$set('radiusKm', '{{ $value }}')" aria-pressed="{{ (float) $radiusKm === (float) $value ? 'true' : 'false' }}" @class(['min-h-10 rounded-sm border px-3 text-xs font-medium', 'border-brand bg-soft text-brand' => (float) $radiusKm === (float) $value, 'border-line text-muted hover:bg-soft' => (float) $radiusKm !== (float) $value])>{{ $label }}</button>
                        @endforeach
                    </div>
                    <label for="nearby-radius" class="mt-4 flex items-center justify-between gap-3 text-xs text-muted"><span>Fine-tune distance</span><output for="nearby-radius" class="font-semibold tabular-nums text-brand">{{ number_format((float) $radiusKm, 1) }} km</output></label>
                    <input id="nearby-radius" type="range" wire:model.live.debounce.150ms="radiusKm" min="0.5" max="5" step="0.5" class="mt-2 min-h-8 w-full accent-brand" aria-label="Search distance from 0.5 to 5 kilometers">
                    @error('radiusKm')<p role="alert" class="mt-2 text-xs text-red-700 dark:text-red-300">Choose a distance between 0.5 and 5 km.</p>@enderror
                </fieldset>

                <button type="button" x-on:click="requestLocation()" x-bind:disabled="locating || selectedCategories.length === 0" wire:loading.attr="disabled" wire:target="findNearbyCafes" class="mt-5 flex min-h-11 w-full items-center justify-center gap-2 rounded-sm bg-brand px-4 text-sm font-medium text-on-brand disabled:cursor-wait disabled:opacity-60">
                    <span x-show="!locating" wire:loading.remove wire:target="findNearbyCafes">{{ $nearbySearchCompleted ? 'Update nearby results' : 'Find nearby places' }}</span>
                    <span x-cloak x-show="locating">Waiting for location permission…</span>
                    <span wire:loading wire:target="findNearbyCafes">Searching OpenStreetMap…</span>
                    <x-coffee-icon name="map" class="size-4"/>
                </button>
                <p x-cloak x-show="locationError" x-text="locationError" role="alert" class="mt-3 text-sm leading-5 text-red-700 dark:text-red-300"></p>
                @if($mapError)<p role="alert" class="mt-3 text-sm leading-5 text-red-700 dark:text-red-300">{{ $mapError }}</p>@endif
            </div>
            </div>

            <div class="flex-1 overflow-y-auto">
                @if($nearbySearchCompleted && count($nearbyCafes))
                    <p class="px-5 pt-4 text-xs text-muted">{{ count($nearbyCafes) }} places within {{ number_format((float) $radiusKm, 1) }} km · nearest first</p>
                    <ul class="mt-2 divide-y divide-line">
                        @foreach($nearbyCafes as $cafe)
                            <li wire:key="nearby-cafe-{{ $cafe['osm_type'] }}-{{ $cafe['osm_id'] }}">
                                <div class="flex items-start gap-3 px-5 py-4">
                                    <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full border border-line bg-soft text-brand"><x-coffee-icon name="map" class="size-4"/></span>
                                    <div class="min-w-0 flex-1">
                                        <h3 class="break-words font-semibold text-ink">{{ $cafe['name'] }}</h3>
                                        @if(count($cafe['categories']))<div class="mt-2 flex flex-wrap gap-1.5">@foreach($cafe['categories'] as $category)<span class="rounded-sm bg-soft px-2 py-1 text-[10px] text-brand">{{ $category }}</span>@endforeach</div>@endif
                                        <p class="mt-1 text-xs leading-5 text-muted">{{ $cafe['address'] ?: 'Address not listed' }}</p>
                                        @if($cafe['opening_hours'])<p class="mt-1 text-xs leading-5 text-muted">Hours: {{ $cafe['opening_hours'] }}</p>@endif
                                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                            <span class="text-xs font-medium tabular-nums text-brand">{{ $cafe['distance_m'] < 1000 ? number_format($cafe['distance_m']).' m' : number_format($cafe['distance_m'] / 1000, 1).' km' }} away</span>
                                            <a href="https://www.openstreetmap.org/{{ $cafe['osm_type'] }}/{{ $cafe['osm_id'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-10 items-center gap-1 text-xs text-brand underline underline-offset-4">Open on OpenStreetMap <x-coffee-icon name="arrow" class="size-3"/></a>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <p class="border-t border-line px-5 py-3 text-[11px] leading-5 text-muted">Cafe names and details come from OpenStreetMap contributors and may be incomplete or out of date.</p>
                @elseif($nearbySearchCompleted)
                    <div class="px-6 py-12 text-center"><x-coffee-icon name="search" class="mx-auto size-7 text-brand"/><h3 class="mt-3 font-semibold">No matching places within {{ number_format((float) $radiusKm, 1) }} km</h3><p class="mt-2 text-sm leading-6 text-muted">OpenStreetMap may not have these place types mapped nearby. Adjust your filters or distance and update the results.</p></div>
                @elseif($mapLatitude !== null)
                    <div class="px-6 py-12 text-center"><x-coffee-icon name="map" class="mx-auto size-7 text-brand"/><h3 class="mt-3 font-semibold">Your location is pinned</h3><p class="mt-2 text-sm leading-6 text-muted">The map is centered on the location you shared. Update your search to load nearby places again.</p></div>
                @else
                    <div class="px-6 py-12 text-center"><x-coffee-icon name="coffee" class="mx-auto size-8 text-brand"/><h3 class="mt-3 font-semibold">Your next coffee stop is close by</h3><p class="mt-2 text-sm leading-6 text-muted">Tap the button above to ask your browser for location access and look for nearby cafes.</p></div>
                @endif
            </div>
        </section>
    </div>
</div>
