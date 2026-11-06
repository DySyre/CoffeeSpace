<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ActivityNotification extends Notification
{
    public function __construct(
        public readonly string $category,
        public readonly string $heading,
        public readonly string $actorName,
        public readonly string $itemTitle,
        public readonly string $actionLabel,
        public readonly string $url,
        public readonly array $context = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'category' => $this->category,
            'heading' => $this->heading,
            'actor_name' => $this->actorName,
            'item_title' => $this->itemTitle,
            'action_label' => $this->actionLabel,
            'url' => $this->url,
            'context' => $this->context,
        ];
    }
}
