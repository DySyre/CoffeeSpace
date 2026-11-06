<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserActivityChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $profileUuid,
        public readonly string $category,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('activity.'.$this->profileUuid)];
    }

    public function broadcastAs(): string
    {
        return 'user.activity.updated';
    }

    public function broadcastWith(): array
    {
        return ['category' => $this->category];
    }
}
