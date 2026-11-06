<?php

namespace App\Livewire\Components;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ActivityStatus extends Component
{
    #[Locked]
    public int $userId;

    public function mount(int $userId): void
    {
        $this->userId = $userId;
    }

    public function render(): View
    {
        $user = User::query()->select(['id', 'profile_uuid', 'show_activity', 'last_seen_at'])->findOrFail($this->userId);

        return view('livewire.components.activity-status', [
            'label' => $user->activityLabel(),
            'offlineLabel' => $user->offlineActivityLabel(),
            'profileUuid' => $user->profile_uuid,
            'online' => $user->isOnline(),
        ]);
    }
}
