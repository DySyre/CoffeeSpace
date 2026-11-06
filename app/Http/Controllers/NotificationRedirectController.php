<?php

namespace App\Http\Controllers;

use App\Models\PlannedVisit;
use App\Services\RealtimeActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NotificationRedirectController
{
    public function __invoke(Request $request, string $type, string $identifier, int $user): RedirectResponse
    {
        abort_unless((int) $request->user()->getKey() === $user, 404);
        $activityNotification = null;

        if ($type === 'activity') {
            $activityNotification = $request->user()->notifications()->whereKey($identifier)->firstOrFail();
            $destination = $activityNotification->data['url'] ?? '';
        } elseif ($type === 'plan') {
            $plan = PlannedVisit::query()
                ->where('accepted_by_id', $request->user()->getKey())
                ->with('conversation:id,conversation_uuid')
                ->findOrFail($identifier);
            abort_unless($plan->conversation, 404);
            $destination = route('chat.index', ['conversation' => $plan->conversation->conversation_uuid], absolute: false);
        } else {
            abort(404);
        }

        $parts = parse_url($destination);
        $path = is_array($parts) ? ($parts['path'] ?? '') : '';
        abort_unless(Str::startsWith($path, '/') && ! Str::startsWith($path, '//'), 404);

        if (isset($parts['query'])) {
            $path .= '?'.$parts['query'];
        }

        if ($activityNotification) {
            $activityNotification->markAsRead();
            app(RealtimeActivity::class)->notify($request->user(), 'READ');
        }

        return redirect()->to($path);
    }
}
