<?php

use App\Livewire\Discover\DiscoverFeed;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\CoffeeDemoSeeder;
use Livewire\Livewire;

test('private posts are excluded from the feed and its search', function () {
    $this->seed(CoffeeDemoSeeder::class);
    Post::where('cafe_name', 'Manchi Café')->update(['visibility' => 'private']);

    Livewire::test(DiscoverFeed::class)->set('search', 'Manchi')
        ->assertDontSee('A slow afternoon, a really good cup.')
        ->assertSee('No stories brewing here yet.');
});

test('scrolling appends the next batch and stops at the end', function () {
    $this->seed(CoffeeDemoSeeder::class);

    Livewire::test(DiscoverFeed::class)
        ->assertSee('Your sign to take the scenic coffee break.')
        ->assertDontSee('A slow afternoon, a really good cup.')
        ->call('loadMore', 2)
        ->assertSee('A slow afternoon, a really good cup.')
        ->assertSee('You’re all caught up')
        ->assertDontSee('Load more stories')
        ->assertSet('visibleCount', 3)
        ->call('loadMore', 2)
        ->assertSet('visibleCount', 3);
});

test('changing the search or filter restarts pagination', function () {
    $this->seed(CoffeeDemoSeeder::class);

    Livewire::test(DiscoverFeed::class)
        ->call('loadMore', 2)
        ->set('search', 'coffee')
        ->assertSet('visibleCount', 2)
        ->set('search', '')
        ->call('loadMore', 2)
        ->set('filter', 'nearby')
        ->assertSet('visibleCount', 2)
        ->assertDontSee('Load more stories');
});

test('discover initially sends a loading placeholder instead of story data', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Loading coffee stories')
        ->assertDontSee('A slow afternoon, a really good cup.');
});

test('refresh keeps the selected feed filter and search', function () {
    $this->seed(CoffeeDemoSeeder::class);

    Livewire::test(DiscoverFeed::class)
        ->set('filter', 'nearby')
        ->set('search', 'Manchi')
        ->call('$refresh')
        ->assertSet('filter', 'nearby')
        ->assertSet('search', 'Manchi')
        ->assertSee('A slow afternoon, a really good cup.')
        ->assertDontSee('A little latte art appreciation.');
});

test('guests can search sample stories by cafe', function () {
    $this->seed(CoffeeDemoSeeder::class);

    Livewire::test(DiscoverFeed::class)
        ->set('search', 'Common Grounds')
        ->assertSee('A little latte art appreciation.')
        ->assertDontSee('A slow afternoon, a really good cup.');
});

test('nearby stories only show the selected sample area', function () {
    $this->seed(CoffeeDemoSeeder::class);

    Livewire::test(DiscoverFeed::class)
        ->set('filter', 'nearby')
        ->assertSee('A slow afternoon, a really good cup.')
        ->assertDontSee('A little latte art appreciation.');
});

test('following shows an honest empty state', function () {
    $this->seed(CoffeeDemoSeeder::class);

    Livewire::test(DiscoverFeed::class)
        ->set('filter', 'following')
        ->assertSee('Your people. Your kind of coffee.')
        ->assertDontSee('A slow afternoon, a really good cup.');
});

test('unmatched searches show a helpful empty state', function () {
    $this->seed(CoffeeDemoSeeder::class);

    Livewire::test(DiscoverFeed::class)
        ->set('search', 'no-matching-cafe')
        ->assertSee('No stories brewing here yet.');
});

test('personal sections require authentication', function (string $url) {
    $this->get($url)->assertRedirect(route('login'));
})->with(['/experiences/create', '/budget', '/profile']);

test('the area map is public', function () {
    $this->get('/map')->assertOk()->assertSee('OpenStreetMap contributors');
});

test('signed in users see the correct personal milestone preview', function (string $url, string $message) {
    $user = User::factory()->create();

    $this->actingAs($user)->get($url)->assertOk()->assertSee($message);
})->with([
    ['/experiences/create', 'Publish post'],
    ['/budget', 'budget analytics are not connected yet.'],
]);
