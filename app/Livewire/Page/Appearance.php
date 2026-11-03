<?php

namespace App\Livewire\Page;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class Appearance extends Component
{
    public function render(): View
    {
        return view('livewire.page.Appearance')->layout('components.layouts.app');
    }
}
