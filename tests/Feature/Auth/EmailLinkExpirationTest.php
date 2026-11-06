<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

test('the verification email sends a signed URL expiring in exactly five minutes', function () {
    $this->freezeTime();
    $user = User::factory()->unverified()->create();
    $mail = (new VerifyEmailNotification)->toMail($user);
    $url = $mail->viewData['url'];
    parse_str(parse_url($url, PHP_URL_QUERY), $query);
    expect(parse_url($url, PHP_URL_PATH))->toBe('/verify-email/'.$user->id.'/'.sha1($user->email));
    expect((int) $query['expires'])->toBe(now()->addMinutes(5)->timestamp);
    expect($query['signature'])->not->toBeEmpty();
});

test('the actual emailed verification link is accepted before five minutes and rejected afterwards', function (int $age, bool $valid) {
    $this->freezeTime();
    $user = User::factory()->unverified()->create();
    $url = (new VerifyEmailNotification)->toMail($user)->viewData['url'];
    $this->travel($age)->seconds();
    $response = $this->actingAs($user)->get($url);
    if ($valid) {
        $response->assertRedirect();
        expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
    } else {
        $response->assertForbidden();
        expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
    }
})->with([[299, true], [301, false]]);

test('the password reset email sends the reset page URL with its token and encoded email', function () {
    $user = User::factory()->create(['email' => 'coffee+friend@example.test']);
    $token = Password::broker()->createToken($user);
    $url = (new ResetPasswordNotification($token))->toMail($user)->viewData['url'];
    parse_str(parse_url($url, PHP_URL_QUERY), $query);
    expect(parse_url($url, PHP_URL_PATH))->toBe('/reset-password/'.$token);
    expect($query['email'])->toBe($user->email);
});

test('password reset tokens work before five minutes and expire afterwards', function (int $age, bool $valid) {
    $this->freezeTime();
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);
    $this->travel($age)->seconds();
    $response = $this->post('/reset-password', ['token' => $token, 'email' => $user->email, 'password' => 'NewCoffeePassword!23', 'password_confirmation' => 'NewCoffeePassword!23']);
    if ($valid) {
        $response->assertSessionHasNoErrors()->assertRedirect(route('login'));
        expect(Hash::check('NewCoffeePassword!23', $user->refresh()->password))->toBeTrue();
        expect(Password::broker()->tokenExists($user, $token))->toBeFalse();
    } else {
        $response->assertSessionHasErrors('email');
        expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
    }
})->with([[299, true], [301, false]]);
