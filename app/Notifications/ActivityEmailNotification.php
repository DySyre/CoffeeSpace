<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActivityEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $category,
        public readonly string $heading,
        public readonly string $actorName,
        public readonly string $itemTitle,
        public readonly string $actionLabel,
        public readonly string $url,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->heading.' - CoffeeSpace')
            ->view('emails.ActivityNotification', [
                'category' => $this->category,
                'heading' => $this->heading,
                'actorName' => $this->actorName,
                'itemTitle' => $this->itemTitle,
                'actionLabel' => $this->actionLabel,
                'url' => $this->url,
                'user' => $notifiable,
            ]);
    }
}
