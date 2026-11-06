<?php

namespace App\Auth;

use Illuminate\Notifications\RoutesNotifications;

class PendingEmailVerification
{
    use RoutesNotifications;

    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
    ) {}

    public function getKey(): string
    {
        return $this->id;
    }

    public function getEmailForVerification(): string
    {
        return $this->email;
    }

    public function routeNotificationForMail(mixed $notification = null): string
    {
        return $this->email;
    }
}
