<?php

namespace App\Services;

use App\Events\UserActivityChanged;
use App\Models\User;
use Throwable;

class RealtimeActivity
{
    public function notify(User $recipient, string $category): void
    {
        try {
            event(new UserActivityChanged($recipient->profile_uuid, strtoupper($category)));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
