<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('users.{profileUuid}', function (User $user, string $profileUuid): bool {
    return hash_equals((string) $user->profile_uuid, $profileUuid);
});

Broadcast::channel('activity.{profileUuid}', function (User $user, string $profileUuid): bool {
    return hash_equals((string) $user->profile_uuid, $profileUuid);
});

Broadcast::channel('online', function (User $user): array {
    return ['profile_uuid' => $user->show_activity ? $user->profile_uuid : null];
});
