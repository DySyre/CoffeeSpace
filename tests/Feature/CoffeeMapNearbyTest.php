<?php

use App\Livewire\Page\CoffeeMap;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function createNearbyMapPost(User $user, string $cafe, float $latitude, float $longitude): void
{
    $user->posts()->create([
        'author_handle' => 'coffee.friend',
        'title' => $cafe.' visit',
        'body' => 'A coffee story nearby.',
        'cafe_name' => $cafe,
        'city' => 'Manila',
        'visibility' => 'public',
        'latitude' => $latitude,
        'longitude' => $longitude,
    ]);
}

test('nearby search puts OpenStreetMap cafés and visible community stories on the map', function () {
    Http::preventStrayRequests();
    Http::fake([
        'overpass-api.de/api/interpreter' => Http::response([
            'elements' => [[
                'type' => 'node',
                'id' => 721,
                'lat' => 14.6001,
                'lon' => 120.9848,
                'tags' => ['name' => 'Map Café', 'amenity' => 'cafe'],
            ]],
        ]),
    ]);

    $user = User::factory()->create(['is_active' => true]);
    createNearbyMapPost($user, 'Community Corner', 14.6003, 120.9849);

    Livewire::actingAs($user)
        ->test(CoffeeMap::class)
        ->call('findNearbyCafes', 14.5995, 120.9842)
        ->assertSet('nearbySearchCompleted', true)
        ->assertSee('OpenStreetMap cafés')
        ->assertSee('Map Café')
        ->assertSee('CoffeeSpace community stories')
        ->assertSee('Community Corner')
        ->assertDispatched('nearby-cafes-updated');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'overpass-api.de/api/interpreter')
        && str_contains($request['data'], 'around:3000'));
});

test('community café stories still appear when OpenStreetMap is unavailable', function () {
    Http::preventStrayRequests();
    Http::fake([
        'overpass-api.de/api/interpreter' => Http::response([], 503),
    ]);

    $user = User::factory()->create(['is_active' => true]);
    createNearbyMapPost($user, 'Community Coffee', 14.6003, 120.9849);

    Livewire::actingAs($user)
        ->test(CoffeeMap::class)
        ->call('findNearbyCafes', 14.5995, 120.9842)
        ->assertSet('nearbySearchCompleted', true)
        ->assertSee('Nearby cafés could not be loaded from OpenStreetMap')
        ->assertSee('Community Coffee')
        ->assertDispatched('nearby-cafes-updated');
});
