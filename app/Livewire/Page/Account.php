<?php

namespace App\Livewire\Page;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Account extends Component
{
    public function unblock(int $userId): void
    {
        abort_unless(Auth::check(), 401);
        $viewer = Auth::user();
        $blockedUser = $viewer->blockedUsers()->whereKey($userId)->firstOrFail();
        $viewer->blockedUsers()->detach($blockedUser->id);

        $this->dispatch('account-unblocked', name: $blockedUser->name);
    }

    public function render(): View
    {
        $blockedUsers = Auth::user()->blockedUsers()
            ->select(['users.id', 'users.name', 'users.profile_uuid', 'users.profile_photo'])
            ->orderBy('users.name')
            ->get();

        return view('livewire.page.Account', compact('blockedUsers'))
            ->layout('components.layouts.coffee', ['title' => 'Account settings', 'section' => 'profile']);
    }
}
