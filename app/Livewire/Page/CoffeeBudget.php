<?php

namespace App\Livewire\Page;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class CoffeeBudget extends Component
{
    public function render(): View
    {
        return view('livewire.page.CoffeeBudget')
            ->layout('components.layouts.coffee', ['title' => 'Coffee Budget · CoffeeSpace', 'section' => 'budget']);
    }
}
