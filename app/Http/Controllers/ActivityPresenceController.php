<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivityPresenceController
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        if (! $user->last_seen_at || $user->last_seen_at->lte(now()->subMinute())) {
            $user->forceFill(['last_seen_at' => now()])->saveQuietly();
        }

        return response()->noContent();
    }
}
