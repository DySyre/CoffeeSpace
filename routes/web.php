<?php

use App\Http\Controllers\PostMediaController;
use App\Http\Controllers\NotificationRedirectController;
use App\Http\Controllers\ProfilePhotoController;
use App\Http\Controllers\ActivityPresenceController;
use App\Livewire\Page\Account;
use App\Livewire\Page\AddExperience;
use App\Livewire\Page\Appearance;
use App\Livewire\Page\ChatPage;
use App\Livewire\Page\CoffeeBudget;
use App\Livewire\Page\CoffeeMap;
use App\Livewire\Page\Discover;
use App\Livewire\Page\MyProfile;
use App\Livewire\Page\Password;
use App\Livewire\Page\PostDetail;
use App\Livewire\Page\Profile;
use Illuminate\Support\Facades\Route;

// Public pages.
Route::get('/', Discover::class)->name('home');
Route::get('/posts/{post:post_uuid}', PostDetail::class)->whereUuid('post')->name('post.show');
Route::get('/map', CoffeeMap::class)->name('coffee.map');
Route::get('/profile/{user:profile_uuid}', MyProfile::class)->whereUuid('user')->name('profile.show');
Route::get('/profiles/{user:profile_uuid}/photo', [ProfilePhotoController::class, 'publicPhoto'])->whereUuid('user')->name('profile.public-photo');
Route::get('/profiles/{user:profile_uuid}/cover', [ProfilePhotoController::class, 'publicPhoto'])->whereUuid('user')->defaults('kind', 'cover')->name('profile.public-cover');

// Media responses use a controller so images and video ranges can be streamed.
Route::get('/post-media/{media}', [PostMediaController::class, 'show'])->name('post-media.show');

Route::get('/dashboard', Discover::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::post('/activity/last-seen', ActivityPresenceController::class)
        ->middleware('throttle:12,1')
        ->name('activity.last-seen');
    Route::get('/notifications/{type}/{identifier}/{user}', NotificationRedirectController::class)
        ->whereIn('type', ['activity', 'plan'])
        ->whereNumber('user')
        ->middleware('signed:relative')
        ->name('notifications.redirect');
    Route::get('/ChatPage', ChatPage::class)->name('chat.index');
    Route::get('/profile/photo', ProfilePhotoController::class)->name('profile.photo');
    Route::get('/profile/cover', ProfilePhotoController::class)->defaults('kind', 'cover')->name('profile.cover');
    Route::get('/experiences/create', AddExperience::class)->name('coffee.post');
    Route::get('/budget', CoffeeBudget::class)->name('coffee.budget');

    Route::get('/profile', fn () => redirect(auth()->user()->profileUrl()))->name('coffee.profile');
    Route::redirect('/settings', '/settings/profile');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/profile', Profile::class)->name('profile');
        Route::get('/account', Account::class)->name('account');
        Route::get('/password', Password::class)->name('password');
        Route::get('/appearance', Appearance::class)->name('appearance');
    });
});

require __DIR__.'/auth.php';
