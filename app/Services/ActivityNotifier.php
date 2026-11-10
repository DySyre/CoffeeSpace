<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\ActivityEmailNotification;
use App\Notifications\ActivityNotification;
use Throwable;

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

        $emailEnabled = $emailPreference
            ? (bool) $recipient->notificationSettings()->value($emailPreference)
            : false;

        if (! $allowEmail || ! $emailPreference || ! $recipient->hasVerifiedEmail() || ! $emailEnabled) {
            return;
        }

        try {
            app(EmailDeliveryLimiter::class)->sendActivity(
                $recipient,
                $category,
                new ActivityEmailNotification(
                    $category,
                    $heading,
                    $actorName,
                    $itemTitle,
                    $actionLabel,
                    $url,
                ),
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
