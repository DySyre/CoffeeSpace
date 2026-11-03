<?php

use App\Http\Controllers\PostMediaController;
use App\Livewire\CreatePost;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'coffee.discover')->name('home');
Route::view('/map', 'coffee.map')->name('coffee.map');
Route::get('/experiences/create', CreatePost::class)
    ->middleware('auth')->name('coffee.post');
Route::get('/post-media/{media}', [PostMediaController::class, 'show'])->name('post-media.show');
Route::view('/budget', 'coffee.preview', ['section' => 'budget'])
    ->middleware('auth')->name('coffee.budget');
Route::redirect('/profile', '/settings/profile')->middleware('auth')->name('coffee.profile');

Route::view('dashboard', 'coffee.discover')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
