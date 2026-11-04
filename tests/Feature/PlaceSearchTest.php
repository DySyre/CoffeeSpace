<?php

use App\Livewire\Page\AddExperience;
use App\Models\User;
use App\Services\PlaceSearch;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function placeSearchResponse(): array
{
    return ['features' => [['geometry' => ['coordinates' => [120.95, 14.73]], 'properties' => ['name' => 'Coffee Corner', 'city' => 'Meycauayan', 'country' => 'Philippines']]]];
}

test('place searches are explicit cached requests and results preview without attaching a pin', function () {
    Http::preventStrayRequests();
    Http::fake(['photon.komoot.io/*' => Http::response(placeSearchResponse())]);
    $user = User::factory()->create();
    $component = Livewire::actingAs($user)->test(AddExperience::class);
    Http::assertNothingSent();
    $component->set('placeQuery', 'Coffee Corner Meycauayan')->call('searchPlaces')->assertHasNoErrors()
        ->assertSet('placeSearchCompleted', true)->assertSee('Coffee Corner')->call('previewPlace', 0)
        ->assertSet('cafeName', 'Coffee Corner')->assertSet('city', 'Meycauayan')
        ->assertSet('latitude', null)->assertSet('longitude', null)->assertDispatched('post-place-preview');
    app(PlaceSearch::class)->search('Coffee Corner Meycauayan');
    Http::assertSentCount(1);
});

test('short place queries do not contact the search provider', function () {
    Http::preventStrayRequests();
    $user = User::factory()->create();
    Livewire::actingAs($user)->test(AddExperience::class)->set('placeQuery', 'a')->call('searchPlaces')->assertHasErrors('placeQuery');
    Http::assertNothingSent();
});

test('a failed place search is recoverable and an invalid selection cannot add coordinates', function () {
    Http::preventStrayRequests();
    Http::fake(['photon.komoot.io/*' => Http::response([], 503)]);
    $user = User::factory()->create();
    Livewire::actingAs($user)->test(AddExperience::class)->set('placeQuery', 'Missing coffee location')->call('searchPlaces')
        ->assertHasErrors('placeQuery')->assertSet('placeResults', []);
    Livewire::actingAs($user)->test(AddExperience::class)->call('previewPlace', 99)->assertNotFound();
});
