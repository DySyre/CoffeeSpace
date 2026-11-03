<?php

namespace App\Livewire\Page;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class Discover extends Component
{
    public function render(): View
    {
        return view('livewire.page.Discover')
            ->layout('components.layouts.coffee', ['title' => 'Discover · CoffeeSpace', 'section' => 'discover']);
    }
}
