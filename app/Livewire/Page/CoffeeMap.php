<?php

namespace App\Livewire\Page;

use App\Models\PlannedVisit;
use App\Models\Post;
use App\Services\NearbyCafeSearch;
use App\Services\PlaceSearch;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CoffeeMap extends Component
{
    #[Locked]
    public array $nearbyCafes = [];

    #[Locked]
    public bool $nearbySearchCompleted = false;

    #[Locked]
    public string $mapError = '';

    #[Locked]
    public ?float $mapLatitude = null;

    #[Locked]
    public ?float $mapLongitude = null;

    public string $radiusKm = '3.0';

    public array $selectedCategories = ['cafe'];

    public string $mapSearchQuery = '';

    #[Locked]
    public array $mapSearchResults = [];

    #[Locked]
    public bool $mapSearchCompleted = false;

    #[Locked]
    public string $mapSearchError = '';

    private function mapPlaceKey(string $name, float $latitude, float $longitude): string
    {
        return hash('sha256', implode('|', [
            mb_strtolower(trim($name)),
            number_format($latitude, 5, '.', ''),
            number_format($longitude, 5, '.', ''),
        ]));
    }

    public function searchMap(PlaceSearch $places): void
    {
        $this->mapSearchQuery = trim($this->mapSearchQuery);
        $this->validate(['mapSearchQuery' => ['required', 'string', 'min:3', 'max:200']]);
        $this->mapSearchResults = [];
        $this->mapSearchCompleted = false;
        $this->mapSearchError = '';

        $identity = auth()->check() ? 'user:'.auth()->id() : 'ip:'.hash('sha256', (string) request()->ip());
        $limitKey = 'map-place-search:'.$identity;
        if (RateLimiter::tooManyAttempts($limitKey, 15)) {
            $this->mapSearchError = 'Please wait a minute before searching the map again.';
            $this->dispatch('map-search-results', results: []);

            return;
        }
        RateLimiter::hit($limitKey, 60);

        try {
            $searchResults = $places->search($this->mapSearchQuery);
            $viewer = auth()->user();
            $savedPlaceKeys = $viewer?->savedPlaces()->pluck('place_key')->all() ?? [];
            if ($viewer) {
                $savedPostKeys = $viewer->savedPosts()->visibleTo($viewer)
                    ->whereNull('archived_at')->whereNotNull('latitude')->whereNotNull('longitude')
                    ->get(['cafe_name', 'latitude', 'longitude'])
                    ->map(fn (Post $post) => $this->mapPlaceKey($post->cafe_name, (float) $post->latitude, (float) $post->longitude))
                    ->all();
                $savedPlaceKeys = [...$savedPlaceKeys, ...$savedPostKeys];
            }
            $savedPlaceKeys = array_fill_keys($savedPlaceKeys, true);
            $this->mapSearchResults = collect($searchResults)->map(function (array $place) use ($savedPlaceKeys) {
                $place['place_key'] = $this->mapPlaceKey($place['name'], (float) $place['latitude'], (float) $place['longitude']);
                $place['want_to_go'] = isset($savedPlaceKeys[$place['place_key']]);

                return $place;
            })->all();
            $this->mapSearchCompleted = true;
            $this->dispatch('map-search-results', results: $this->mapSearchResults);
        } catch (\Throwable) {
            $this->mapSearchError = 'Map search is unavailable. Please try again shortly.';
            $this->dispatch('map-search-results', results: []);
        }
    }

    public function addMapPlaceToWantToGo(int $index): void
    {
        abort_unless(auth()->check(), 401);
        abort_unless(isset($this->mapSearchResults[$index]), 404);

        $place = $this->mapSearchResults[$index];
        $placeKey = $this->mapPlaceKey($place['name'], (float) $place['latitude'], (float) $place['longitude']);
        auth()->user()->savedPlaces()->firstOrCreate(
            ['place_key' => $placeKey],
            [
                'name' => $place['name'],
                'area' => $place['area'],
                'address' => $place['address'],
                'latitude' => $place['latitude'],
                'longitude' => $place['longitude'],
            ],
        );

        $place['place_key'] = $placeKey;
        $place['want_to_go'] = true;
        $this->mapSearchResults[$index] = $place;
        $this->dispatch('map-place-saved', place: [
            'place_key' => $placeKey,
            'latitude' => (float) $place['latitude'],
            'longitude' => (float) $place['longitude'],
            'cafe' => $place['name'],
            'city' => $place['area'] ?: $place['address'],
            'visited' => false,
            'want_to_go' => true,
            'planned' => false,
            'plans' => [],
            'community' => false,
            'post_count' => 0,
            'posts' => [],
        ]);
    }

    public function findNearbyCafes(mixed $latitude, mixed $longitude, NearbyCafeSearch $cafes): void
    {
        $coordinates = validator(
            [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'radiusKm' => $this->radiusKm,
                'categories' => $this->selectedCategories,
            ],
            [
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
                'radiusKm' => ['required', 'numeric', 'between:0.5,5'],
                'categories' => ['required', 'array', 'min:1'],
                'categories.*' => ['required', 'string', Rule::in(['cafe', 'burger', 'matcha', 'fastfood'])],
            ],
        )->validate();

        $this->nearbyCafes = [];
        $this->nearbySearchCompleted = false;
        $this->mapError = '';

        $latitude = (float) $coordinates['latitude'];
        $longitude = (float) $coordinates['longitude'];
        $this->mapLatitude = $latitude;
        $this->mapLongitude = $longitude;

        $identity = auth()->check() ? 'user:'.auth()->id() : 'ip:'.hash('sha256', (string) request()->ip());
        $limitKey = 'nearby-cafe-search:'.$identity;
        if (RateLimiter::tooManyAttempts($limitKey, 5)) {
            $this->mapError = 'Please wait a minute before searching nearby cafés again.';

            return;
        }
        RateLimiter::hit($limitKey, 60);

        try {
            $radiusMeters = (int) round((float) $coordinates['radiusKm'] * 1000);
            $this->nearbyCafes = $cafes->search($latitude, $longitude, $radiusMeters, $coordinates['categories']);
            $this->nearbySearchCompleted = true;
        } catch (\Throwable) {
            $this->mapError = 'Nearby cafés could not be loaded from OpenStreetMap. Please try again shortly.';
        }
    }

    public function render(): View
    {
        $viewer = auth()->user();
        $mappedPosts = Post::visibleTo($viewer)
            ->whereNull('archived_at')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('user:id,name,profile_uuid')
            ->orderByDesc('id')
            ->limit(250)
            ->get();
        $savedPosts = $viewer
            ? $viewer->savedPosts()->visibleTo($viewer)->whereNull('archived_at')->whereNotNull('latitude')->whereNotNull('longitude')->with('user:id,name,profile_uuid')->orderByDesc('posts.id')->limit(250)->get()
            : collect();
        $savedPostIds = $savedPosts->modelKeys();
        $mappedPosts = $mappedPosts->concat($savedPosts)->unique('id')->sortByDesc('id')->take(250);

        $postPins = $mappedPosts->groupBy(fn (Post $post) => $this->mapPlaceKey($post->cafe_name, (float) $post->latitude, (float) $post->longitude))
            ->map(function ($placePosts, $placeKey) use ($viewer, $savedPostIds) {
                /** @var Post $post */
                $post = $placePosts->first();

                return [
                    'place_key' => $placeKey,
                    'latitude' => (float) $post->latitude,
                    'longitude' => (float) $post->longitude,
                    'cafe' => $post->cafe_name,
                    'city' => $post->city,
                    'visited' => $viewer && $placePosts->contains(fn (Post $visit) => $visit->user_id === $viewer->id),
                    'want_to_go' => $placePosts->contains(fn (Post $visit) => in_array($visit->id, $savedPostIds, true)),
                    'planned' => false,
                    'plans' => [],
                    'community' => ! $viewer || $placePosts->contains(fn (Post $visit) => $visit->user_id !== $viewer->id),
                    'post_count' => $placePosts->count(),
                    'posts' => $placePosts->take(8)->map(fn (Post $visit) => [
                        'title' => $visit->title,
                        'author' => $visit->user->name,
                        'date' => $visit->created_at->format('M j, Y'),
                        'url' => $visit->postUrl(),
                    ])->values()->all(),
                ];
            })->values()->all();

        $savedPlaces = $viewer
            ? $viewer->savedPlaces()->orderByDesc('created_at')->limit(250)->get()
            : collect();
        foreach ($savedPlaces as $savedPlace) {
            $existingIndex = collect($postPins)->search(fn (array $pin) => $pin['place_key'] === $savedPlace->place_key);
            if ($existingIndex !== false) {
                $postPins[$existingIndex]['want_to_go'] = true;

                continue;
            }

            $postPins[] = [
                'place_key' => $savedPlace->place_key,
                'latitude' => $savedPlace->latitude,
                'longitude' => $savedPlace->longitude,
                'cafe' => $savedPlace->name,
                'city' => $savedPlace->area ?: $savedPlace->address,
                'visited' => false,
                'want_to_go' => true,
                'planned' => false,
                'plans' => [],
                'community' => false,
                'post_count' => 0,
                'posts' => [],
            ];
        }

        $plannedVisits = $viewer
            ? PlannedVisit::query()->where('status', 'accepted')->where('planned_at', '>=', now())
                ->where(fn ($query) => $query->where('user_id', $viewer->id)->orWhere('accepted_by_id', $viewer->id))
                ->orderBy('planned_at')->limit(250)->get()
            : collect();
        foreach ($plannedVisits as $visit) {
            $key = hash('sha256', implode('|', [
                mb_strtolower(trim($visit->cafe_name)),
                number_format($visit->latitude, 5, '.', ''),
                number_format($visit->longitude, 5, '.', ''),
            ]));
            $existingIndex = collect($postPins)->search(fn (array $pin) => ($pin['place_key'] ?? null) === $key);
            if ($existingIndex !== false) {
                $postPins[$existingIndex]['planned'] = true;
                $postPins[$existingIndex]['plans'][] = [
                    'date' => $visit->planned_at->timezone(config('app.timezone'))->format('M j, g:i a'),
                ];

                continue;
            }
            $postPins[] = [
                'place_key' => $key,
                'latitude' => $visit->latitude,
                'longitude' => $visit->longitude,
                'cafe' => $visit->cafe_name,
                'city' => $visit->address,
                'visited' => false,
                'want_to_go' => false,
                'planned' => true,
                'plans' => [['date' => $visit->planned_at->timezone(config('app.timezone'))->format('M j, g:i a')]],
                'community' => false,
                'post_count' => 0,
                'posts' => [],
            ];
        }

        $mapCenter = [
            'latitude' => $this->mapLatitude ?? ($postPins[0]['latitude'] ?? 14.740),
            'longitude' => $this->mapLongitude ?? ($postPins[0]['longitude'] ?? 120.970),
        ];
        $currentLocation = $this->mapLatitude !== null && $this->mapLongitude !== null
            ? ['latitude' => $this->mapLatitude, 'longitude' => $this->mapLongitude]
            : null;
        $hasVisitedPins = collect($postPins)->contains('visited', true);
        $hasPlannedPins = collect($postPins)->contains('planned', true);
        $hasCommunityPins = collect($postPins)->contains('community', true);
        $currentUser = $viewer ? [
            'photo' => $viewer->profilePhotoUrl(),
            'initials' => mb_substr($viewer->initials(), 0, 2),
        ] : null;

        return view('livewire.page.CoffeeMap')
            ->with(compact('postPins', 'mapCenter', 'currentLocation', 'hasVisitedPins', 'hasPlannedPins', 'hasCommunityPins', 'currentUser'))
            ->layout('components.layouts.coffee', ['title' => 'Coffee Map · CoffeeSpace', 'section' => 'map']);
    }
}
