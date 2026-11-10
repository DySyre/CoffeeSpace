<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class EmailDeliveryLimiter
{
    public function sendVerification(
        object $recipient,
        string $email,
        Notification $notification,
        ?callable $prepare = null,
    ): bool {
        $emailHash = hash('sha256', Str::lower(trim($email)));
        $key = 'email-cooldown:verification:'.$emailHash;

        return $this->sendOnce($key, 300, function () use ($recipient, $notification, $prepare): void {
            if ($prepare !== null) {
                $prepare();
            }

            $recipient->notify($notification);
        });
    }

    public function sendActivity(User $recipient, string $category, Notification $notification): bool
    {
        $categoryKey = Str::slug(Str::lower($category));
        $key = 'email-cooldown:activity:'.$recipient->getKey().':'.$categoryKey;

        return $this->sendOnce($key, 600, fn () => $recipient->notify($notification));
    }

    private function sendOnce(string $key, int $cooldownSeconds, callable $send): bool
    {
        if (! Cache::add($key, true, now()->addSeconds($cooldownSeconds))) {
            return false;
        }

        try {
            $send();
        } catch (Throwable $exception) {
            Cache::forget($key);

            throw $exception;
        }

        return true;
    }
}
