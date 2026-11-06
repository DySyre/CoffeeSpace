<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create(['is_active' => true]);

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertStatus(200);
});

test('email can be verified', function () {
    $user = User::factory()->unverified()->create(['is_active' => true]);

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create(['is_active' => true]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('the new email is saved only after its owner opens the signed verification link', function () {
    $user = User::factory()->create(['email' => 'old@example.com', 'is_active' => true]);
    $newEmail = 'new@example.com';
    $id = 'email-change-'.$user->id.'-'.hash('sha256', $newEmail);

    Cache::put('pending-email-change:'.$id, Crypt::encryptString(json_encode([
        'user_id' => $user->id,
        'email' => $newEmail,
    ], JSON_THROW_ON_ERROR)), now()->addMinutes(5));
    Cache::put('pending-email-change-user:'.$user->id, $id, now()->addMinutes(5));

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(5),
        ['id' => $id, 'hash' => sha1($newEmail)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    $response->assertRedirect(route('settings.profile'))
        ->assertSessionHas('status', 'email-address-verified');
    expect($user->fresh()->email)->toBe($newEmail)
        ->and($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('another signed-in user cannot apply a pending email change', function () {
    $owner = User::factory()->create(['email' => 'owner@example.com', 'is_active' => true]);
    $otherUser = User::factory()->create(['email' => 'other@example.com', 'is_active' => true]);
    $newEmail = 'new@example.com';
    $id = 'email-change-'.$owner->id.'-'.hash('sha256', $newEmail);

    Cache::put('pending-email-change:'.$id, Crypt::encryptString(json_encode([
        'user_id' => $owner->id,
        'email' => $newEmail,
    ], JSON_THROW_ON_ERROR)), now()->addMinutes(5));

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(5),
        ['id' => $id, 'hash' => sha1($newEmail)]
    );

    $this->actingAs($otherUser)->get($verificationUrl)->assertForbidden();

    expect($owner->fresh()->email)->toBe('owner@example.com');
});
