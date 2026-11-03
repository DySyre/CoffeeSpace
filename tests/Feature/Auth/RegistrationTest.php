<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('registration refuses duplicate email and mismatched passwords', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $this->post('/register', [
        'name' => 'Another Person', 'email' => 'existing@example.com',
        'password' => 'password', 'password_confirmation' => 'different',
    ])->assertSessionHasErrors(['email', 'password']);

    $this->assertDatabaseCount('users', 1);
    $this->assertGuest();
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User', 'email' => 'test@example.com',
        'password' => 'password', 'password_confirmation' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
    expect(Hash::check('password', User::where('email', 'test@example.com')->sole()->password))->toBeTrue();
});
