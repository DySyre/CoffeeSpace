<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $conversationUuid,
        public string $senderUuid,
        public string $recipientUuid,
        public ?int $messageId = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.'.$this->senderUuid),
            new PrivateChannel('users.'.$this->recipientUuid),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_uuid' => $this->conversationUuid,
            'sender_uuid' => $this->senderUuid,
            'message_id' => $this->messageId,
        ];
    }
}
