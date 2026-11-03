<?php

use App\Models\User;

test('confirm password screen can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/confirm-password');

    $response->assertStatus(200);
});

test('password can be confirmed', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->post('/confirm-password', ['password' => 'password']);

    $response
        ->assertSessionHasNoErrors()
        ->assertSessionHas('auth.password_confirmed_at')
        ->assertRedirect(route('dashboard'));
});

test('password is not confirmed with invalid password', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->post('/confirm-password', ['password' => 'wrong-password']);

    $response->assertSessionHasErrors(['password'])->assertSessionMissing('auth.password_confirmed_at');
});
