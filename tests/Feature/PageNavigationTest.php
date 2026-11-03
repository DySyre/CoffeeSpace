<?php

use App\Models\User;
use Database\Seeders\CoffeeDemoSeeder;

test('soft navigation to Discover includes the feed in its first response', function () {
    $this->seed(CoffeeDemoSeeder::class);

    $this->withHeader('X-Livewire-Navigate', '1')->get('/')
        ->assertOk()->assertSee('A little latte art appreciation.');
});

test('settings pages require authentication', function (string $path) {
    $this->get($path)->assertRedirect(route('login'));
})->with(['/settings/profile', '/settings/password', '/settings/appearance']);

test('signed in users can open the class based settings pages', function (string $path, string $text) {
    $user = User::factory()->create();

    $this->actingAs($user)->get($path)->assertOk()->assertSee($text);
})->with([
    ['/settings/password', 'Update password'],
    ['/settings/appearance', 'Appearance'],
]);

test('settings shortcut keeps its existing destination', function (string $path) {
    $user = User::factory()->create();

    $this->actingAs($user)->get($path)->assertRedirect('/settings/profile');
})->with(['/settings']);
