<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

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
    Notification::fake();

    $email = 'test@example.com';
    $response = $this->post('/register', [
        'name' => 'Test User', 'email' => 'test@example.com',
        'password' => 'password', 'password_confirmation' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('register'));

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => $email]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(5),
        ['id' => hash('sha256', $email), 'hash' => sha1($email)]
    );

    $this->get($verificationUrl)->assertRedirect(route('dashboard', absolute: false).'?verified=1');

    $this->assertAuthenticated();
    $user = User::where('email', $email)->sole();
    expect(Hash::check('password', $user->password))->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeTrue();
});
