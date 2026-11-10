<?php

use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Notifications\VerifyEmailNotification;
use App\Services\EmailDeliveryLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::view('login', 'auth.login')->name('login');
    Route::post('login', [SessionController::class, 'store'])->middleware('throttle:10,5')->name('login.store');

    Route::view('register', 'auth.register')->name('register');
    Route::post('register', [RegistrationController::class, 'store'])->middleware('throttle:3,5')->name('register.store');

    Route::view('forgot-password', 'auth.forgot-password')->name('password.request');
    Route::post('forgot-password', [PasswordController::class, 'email'])->middleware('throttle:6,1')->name('password.email');

    Route::view('reset-password/{token}', 'auth.reset-password')->name('password.reset');
    Route::post('reset-password', [PasswordController::class, 'update'])->middleware('throttle:6,1')->name('password.update');

});

// Breeze's signed verification URL serves both pending registrations and legacy users.
Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::middleware('auth')->group(function () {
    Route::view('verify-email', 'auth.verify-email')->name('verification.notice');
    Route::post('email/verification-notification', function (Request $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }
        $sent = app(EmailDeliveryLimiter::class)->sendVerification(
            $request->user(),
            $request->user()->getEmailForVerification(),
            new VerifyEmailNotification,
        );

        return back()->with('status', $sent
            ? 'Verification link sent.'
            : 'A verification link was sent recently. Please wait five minutes before requesting another.');
    })->middleware('throttle:6,1')->name('verification.send');

    Route::view('confirm-password', 'auth.confirm-password')->name('password.confirm');
    Route::post('confirm-password', [PasswordController::class, 'confirm'])->middleware('throttle:6,1')->name('password.confirm.store');
});

Route::post('logout', [SessionController::class, 'destroy'])->middleware('auth')->name('logout');
