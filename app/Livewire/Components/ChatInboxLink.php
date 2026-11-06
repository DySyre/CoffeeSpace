<?php

namespace App\Livewire\Components;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class ChatInboxLink extends Component
{
    public function render(): View
    {
        $unreadCount = auth()->user()->unreadNotifications()
            ->where('data->category', 'MESSAGE')
            ->count();

        return view('livewire.components.chat-inbox-link', compact('unreadCount'));
    }
}
