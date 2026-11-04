<?php

use App\Livewire\Page\CoffeeBudget;
use App\Models\User;
use App\Services\PlaceSearch;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function budgetCafeSearch(): void
{
    app()->instance(PlaceSearch::class, Mockery::mock(PlaceSearch::class, function ($mock) {
        $mock->shouldReceive('search')->andReturn([['name' => 'Cafe One', 'area' => 'Manila', 'address' => 'Manila', 'latitude' => 14.6, 'longitude' => 120.9]]);
    }));
}

test('budget is private and purchases drive totals and favorites', function () {
    $this->travelTo(now('Asia/Manila')->setDate(2026, 11, 5)->setTime(12, 0));
    $user = User::factory()->create();
    $other = User::factory()->create();
    budgetCafeSearch();
    Livewire::actingAs($other)->test(CoffeeBudget::class)->set('cafeQuery', 'Cafe One')->call('searchCafes')->call('selectCafe', 0)->set('item', 'Other drink')->set('price', '999')->set('date', '2026-11-05')->call('addPurchase');
    Livewire::actingAs($user)->test(CoffeeBudget::class)
        ->set('cafeQuery', 'Cafe One')->call('searchCafes')->call('selectCafe', 0)->set('item', 'Latte')->set('price', '125.50')->set('quantity', '2')->set('date', '2026-11-05')
        ->call('addPurchase')->assertHasNoErrors()->assertSee('251.00')->assertSee('Latte')->assertDontSee('Other drink')
        ->set('tab', 'wrapped')->assertSee('251.00')->assertSee('Cafe One');
    expect(DB::table('coffee_purchases')->where('user_id', $user->id)->value('unit_price_centavos'))->toBe(12550);
    $this->get('/budget')->assertOk();
});

test('limits validate and purchases cannot be deleted by another user', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    budgetCafeSearch();
    Livewire::actingAs($owner)->test(CoffeeBudget::class)->set('daily', '12.345')->call('saveLimits')->assertHasErrors('daily')
        ->set('daily', '100.25')->call('saveLimits')->assertHasNoErrors()
        ->set('cafeQuery', 'Cafe One')->call('searchCafes')->call('selectCafe', 0)->set('item', 'Coffee')->set('price', '50')->call('addPurchase')->assertHasNoErrors();
    $id = DB::table('coffee_purchases')->value('id');
    Livewire::actingAs($other)->test(CoffeeBudget::class)->call('deletePurchase', $id);
    expect(DB::table('coffee_purchases')->where('id', $id)->exists())->toBeTrue();
    expect(DB::table('coffee_budget_limits')->where('user_id', $owner->id)->value('daily_centavos'))->toBe(10025);
});

test('a purchase requires a selected map place and chart shows calendar weeks', function () {
    $this->travelTo(now('Asia/Manila')->setDate(2026, 11, 5)->setTime(12, 0));
    $user = User::factory()->create();
    Livewire::actingAs($user)->test(CoffeeBudget::class)
        ->assertSee('W44')->assertSee('Nov 1–1')->assertSee('W45')->assertSee('Nov 2–8')
        ->set('item', 'Coffee')->set('price', '50')->set('cafe', 'Unselected café')->call('addPurchase')->assertHasErrors('cafe');
});

test('guests are redirected from budget', function () {
    $this->get('/budget')->assertRedirect(route('login'));
});
