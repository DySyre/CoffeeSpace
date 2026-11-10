<?php

use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;

test('expired requests render the CoffeeSpace 419 recovery page', function () {
    config(['app.debug' => false]);
    Route::get('/_test/expired-page', fn () => abort(419));

    $this->get('/_test/expired-page')->assertStatus(419)
        ->assertSee('Page expired')->assertSee('Sign in again')->assertSee('Back to Discover')
        ->assertSee(url('/login'))->assertSee(url('/'));
});

test('server errors render the CoffeeSpace 500 page without exposing exception details', function () {
    config(['app.debug' => false]);
    Exceptions::fake([RuntimeException::class]);
    Route::get('/_test/server-error', fn () => throw new RuntimeException('Private database credentials or internal stack details'));

    $this->get('/_test/server-error')->assertStatus(500)
        ->assertSee('Server error')->assertSee('Back to Discover')
        ->assertDontSee('Private database credentials or internal stack details');
});

test('recovery pages render without a session or frontend asset manifest', function (string $code) {
    app()->forgetInstance('session');
    $html = view('errors.'.$code)->render();

    expect($html)->toContain('CoffeeSpace', '<style>', 'noindex, nofollow', '<main')
        ->not->toContain('/build/assets/', '/livewire/', 'csrf-token');
})->with(['419', '500']);
