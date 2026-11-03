<?php

namespace App\Livewire\Page;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class CoffeeMap extends Component
{
    public function render(): View
    {
        return view('livewire.page.CoffeeMap')
            ->layout('components.layouts.coffee', ['title' => 'Coffee Map · CoffeeSpace', 'section' => 'map']);
    }
}
