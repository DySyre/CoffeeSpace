<?php

use App\Http\Controllers\PostMediaController;
use App\Http\Controllers\ProfilePhotoController;
use App\Livewire\Page\AddExperience;
use App\Livewire\Page\Appearance;
use App\Livewire\Page\CoffeeBudget;
use App\Livewire\Page\CoffeeMap;
use App\Livewire\Page\Discover;
use App\Livewire\Page\MyProfile;
use App\Livewire\Page\Password;
use App\Livewire\Page\Profile;
use Illuminate\Support\Facades\Route;

// Public pages.
Route::get('/', Discover::class)->name('home');
Route::get('/map', CoffeeMap::class)->name('coffee.map');

// Media responses use a controller so images and video ranges can be streamed.
Route::get('/post-media/{media}', [PostMediaController::class, 'show'])->name('post-media.show');

Route::get('/dashboard', Discover::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile/photo', ProfilePhotoController::class)->name('profile.photo');
    Route::get('/experiences/create', AddExperience::class)->name('coffee.post');
    Route::get('/budget', CoffeeBudget::class)->name('coffee.budget');

    Route::get('/profile', MyProfile::class)->name('coffee.profile');
    Route::redirect('/settings', '/settings/profile');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/profile', Profile::class)->name('profile');
        Route::get('/password', Password::class)->name('password');
        Route::get('/appearance', Appearance::class)->name('appearance');
    });
});

require __DIR__.'/auth.php';
