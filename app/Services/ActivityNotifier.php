<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\ActivityEmailNotification;
use App\Notifications\ActivityNotification;

class ActivityNotifier
{
    public function send(
        User $recipient,
        string $category,
        string $heading,
        string $actorName,
        string $itemTitle,
        string $actionLabel,
        string $url,
        bool $allowEmail = true,
        array $context = [],
    ): void {
        $recipient->notify(new ActivityNotification(
            $category,
            $heading,
            $actorName,
            $itemTitle,
            $actionLabel,
            $url,
            $context,
        ));

        app(RealtimeActivity::class)->notify($recipient, $category);

        $emailPreference = match (strtoupper($category)) {
            'COMMENT' => 'notify_comments_by_email',
            'SAVED' => 'notify_saves_by_email',
            'MESSAGE' => 'notify_messages_by_email',
            default => null,
        };

        if (! $allowEmail || ! $emailPreference || ! $recipient->hasVerifiedEmail() || ! $recipient->notificationSettings->{$emailPreference}) {
            return;
        }

        $recipient->notify((new ActivityEmailNotification(
            $category,
            $heading,
            $actorName,
            $itemTitle,
            $actionLabel,
            $url,
        ))->afterCommit());
    }
}
