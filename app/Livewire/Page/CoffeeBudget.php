<?php

namespace App\Livewire\Page;

use App\Services\PlaceSearch;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CoffeeBudget extends Component
{
    public string $tab = 'spending';

    public string $period = 'month';

    public int $selectedBar = 0;

    public string $item = '';

    public string $cafe = '';

    public string $cafeQuery = '';

    #[Locked]
    public array $cafeResults = [];

    #[Locked]
    public ?array $selectedCafe = null;

    public function searchCafes(PlaceSearch $places): void
    {
        abort_unless(auth()->check(), 401);
        $this->cafeQuery = trim($this->cafeQuery);
        $this->validate(['cafeQuery' => ['required', 'string', 'min:3', 'max:200']]);
        $this->cafeResults = [];
        $this->selectedCafe = null;
        $this->cafe = '';
        $key = 'budget-place-search:'.auth()->id();
        if (RateLimiter::tooManyAttempts($key, 15)) {
            $this->addError('cafeQuery', 'Please wait a minute before searching again.');

            return;
        }
        RateLimiter::hit($key, 60);
        try {
            $this->cafeResults = $places->search($this->cafeQuery);
            if (! count($this->cafeResults)) {
                $this->addError('cafeQuery', 'No places found. Try a different search.');
            }
        } catch (\Throwable) {
            $this->addError('cafeQuery', 'Place search is unavailable. Please try again.');
        }
    }

    public function selectCafe(int $index): void
    {
        abort_unless(isset($this->cafeResults[$index]), 404);
        $this->selectedCafe = $this->cafeResults[$index];
        $this->cafe = $this->selectedCafe['name'];
        $this->cafeResults = [];
        $this->resetValidation('cafe');
    }

    public string $price = '';

    public string $quantity = '1';

    public string $date = '';

    public string $daily = '';

    public string $weekly = '';

    public string $monthly = '';

    public function mount(): void
    {
        $this->date = $this->today()->toDateString();
        $limits = DB::table('coffee_budget_limits')->where('user_id', auth()->id())->first();
        foreach (['daily', 'weekly', 'monthly'] as $key) {
            $value = $limits->{$key.'_centavos'} ?? null;
            $this->$key = $value === null ? '' : number_format($value / 100, 2, '.', '');
        }
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::now('Asia/Manila')->startOfDay();
    }

    private function cents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public function saveLimits(): void
    {
        abort_unless(auth()->check(), 401);
        $this->validate(array_fill_keys(['daily', 'weekly', 'monthly'], ['nullable', 'regex:/^\d{1,7}(\.\d{1,2})?$/']));
        $values = [];
        foreach (['daily', 'weekly', 'monthly'] as $key) {
            $values[$key.'_centavos'] = $this->$key === '' ? null : $this->cents($this->$key);
        }
        DB::table('coffee_budget_limits')->updateOrInsert(['user_id' => auth()->id()], $values + ['updated_at' => now(), 'created_at' => now()]);
        session()->flash('budget_message', 'Your limits have been saved.');
    }

    public function addPurchase(): void
    {
        abort_unless(auth()->check(), 401);
        $this->item = trim($this->item);
        $this->cafe = trim($this->cafe);
        $this->validate([
            'item' => ['required', 'string', 'max:120'],
            'cafe' => ['required', 'string', 'max:120'],
            'price' => ['required', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$this->today()->toDateString()],
        ]);
        if (! $this->selectedCafe || $this->cafe !== $this->selectedCafe['name']) {
            $this->addError('cafe', 'Choose a café from the map search results.');

            return;
        }
        DB::table('coffee_purchases')->insert(['user_id' => auth()->id(), 'item_name' => $this->item, 'cafe_name' => $this->cafe ?: null, 'unit_price_centavos' => $this->cents($this->price), 'quantity' => (int) $this->quantity, 'purchased_on' => $this->date, 'created_at' => now(), 'updated_at' => now()]);
        $this->reset('item', 'cafe', 'cafeQuery', 'selectedCafe', 'price', 'quantity');
        session()->flash('budget_message', 'Purchase added to your budget.');
    }

    public function deletePurchase(int $id): void
    {
        abort_unless(auth()->check(), 401);
        DB::table('coffee_purchases')->where('user_id', auth()->id())->where('id', $id)->delete();
    }

    public function selectPeriod(string $period): void
    {
        if (in_array($period, ['week', 'month', 'year'], true)) {
            $this->period = $period;
            $this->selectedBar = 0;
        }
    }

    public function render(): View
    {
        abort_unless(auth()->check(), 401);
        $today = $this->today();
        $purchases = DB::table('coffee_purchases')->where('user_id', auth()->id())->whereBetween('purchased_on', [$today->startOfYear()->min($today->startOfWeek())->toDateString(), $today->toDateString()])->orderByDesc('purchased_on')->orderByDesc('id')->get();
        $sum = fn ($rows) => $rows->sum(fn ($row) => $row->unit_price_centavos * $row->quantity);
        $yearPurchases = $purchases->filter(fn ($row) => $row->purchased_on >= $today->startOfYear()->toDateString());
        $week = $purchases->filter(fn ($row) => $row->purchased_on >= $today->startOfWeek()->toDateString());
        $month = $purchases->filter(fn ($row) => $row->purchased_on >= $today->startOfMonth()->toDateString());
        $day = $purchases->where('purchased_on', $today->toDateString());
        $limits = DB::table('coffee_budget_limits')->where('user_id', auth()->id())->first();
        $bars = [];
        $count = $this->period === 'week' ? 7 : ($this->period === 'month' ? (int) $today->startOfMonth()->startOfWeek()->diffInWeeks($today->endOfMonth()->startOfWeek()) + 1 : 12);
        for ($i = 0; $i < $count; $i++) {
            if ($this->period === 'week') {
                $start = $today->startOfWeek()->addDays($i);
                $end = $start;
                $label = $start->format('D');
            } elseif ($this->period === 'month') {
                $calendarStart = $today->startOfMonth()->startOfWeek()->addWeeks($i);
                $start = $calendarStart->max($today->startOfMonth());
                $end = $calendarStart->endOfWeek()->min($today->endOfMonth());
                $label = 'W'.$calendarStart->isoWeek();
            } else {
                $start = $today->startOfYear()->addMonths($i);
                $end = $start->endOfMonth();
                $label = $start->format('M');
            }
            $range = $this->period === 'month' ? $start->format('M j').'–'.($start->month === $end->month ? $end->format('j') : $end->format('M j')) : null;
            $bars[] = ['label' => $label, 'range' => $range, 'amount' => $sum($purchases->filter(fn ($row) => $row->purchased_on >= $start->toDateString() && $row->purchased_on <= $end->toDateString()))];
        }
        $favorite = function ($rows) {
            return $rows->groupBy(fn ($row) => mb_strtolower(trim(preg_replace('/\s+/', ' ', $row->item_name))))->map(fn ($group) => ['name' => $group->first()->item_name, 'quantity' => $group->sum('quantity')])->sortByDesc('quantity')->first();
        };
        $cafe = $yearPurchases->filter(fn ($row) => filled($row->cafe_name))->groupBy(fn ($row) => mb_strtolower(trim($row->cafe_name)))->sortByDesc(fn ($group) => $group->count())->first();

        return view('livewire.page.CoffeeBudget', ['bars' => $bars, 'total' => $sum(match ($this->period) {
            'week' => $week, 'year' => $yearPurchases, default => $month
        }), 'spending' => ['daily' => $sum($day), 'weekly' => $sum($week), 'monthly' => $sum($month)], 'limits' => $limits, 'weekFavorite' => $favorite($week), 'monthFavorite' => $favorite($month), 'yearFavorite' => $favorite($yearPurchases), 'yearTotal' => $sum($yearPurchases), 'favoriteCafe' => $cafe?->first()->cafe_name, 'purchases' => $purchases->take(20), 'yearCount' => $yearPurchases->sum('quantity'), 'year' => $today->year])
            ->layout('components.layouts.coffee', ['title' => 'Coffee Budget · CoffeeSpace', 'section' => 'budget']);
    }
}
