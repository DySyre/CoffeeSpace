<?php

namespace App\Livewire\Components;

use App\Models\PlannedVisit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Locked;
use Livewire\Component;

class NotificationMenu extends Component
{
    public bool $open = false;

    public string $notificationFilter = 'all';

    #[Locked]
    public int $notificationLimit = 20;

    public function setNotificationFilter(string $filter): void
    {
        abort_unless(in_array($filter, ['all', 'unread', 'read'], true), 404);
        $this->notificationFilter = $filter;
        $this->notificationLimit = 20;
    }

    public function loadOlder(): void
    {
        $this->notificationLimit = min($this->notificationLimit + 20, 500);
    }

    private function redirectLink(string $type, string|int $identifier, int $userId): string
    {
        return URL::signedRoute(
            'notifications.redirect',
            ['type' => $type, 'identifier' => $identifier, 'user' => $userId],
            absolute: false,
        );
    }

    public function render(): View
    {
        $viewer = auth()->user();
        $nonMessageActivities = fn ($query) => $query->where(function ($activity) {
            $activity->whereNull('data->category')->orWhere('data->category', '<>', 'MESSAGE');
        });
        $activityQuery = $viewer->notifications()->where($nonMessageActivities)->latest();
        if ($this->notificationFilter === 'unread') {
            $activityQuery->whereNull('read_at');
        } elseif ($this->notificationFilter === 'read') {
            $activityQuery->whereNotNull('read_at');
        }
        $activityCount = (clone $activityQuery)->count();
        $activities = $activityQuery->limit($this->notificationLimit)->get()->map(function ($notification) use ($viewer) {
            $data = $notification->data;
            $category = strtolower($data['category'] ?? 'activity');
            $actor = $data['actor_name'] ?? 'Someone';
            $item = $data['item_title'] ?? '';
            $detail = match ($category) {
                'comment' => $actor.' commented on '.($item ?: 'your post'),
                'saved' => $actor.' saved '.($item ?: 'your post'),
                default => $actor.' sent you an update',
            };

            return [
                'id' => $notification->id,
                'type' => $category,
                'title' => $data['heading'] ?? 'CoffeeSpace activity',
                'detail' => $detail,
                'url' => $this->redirectLink('activity', $notification->id, $viewer->id),
                'time' => $notification->created_at,
                'read' => $notification->read_at !== null,
            ];
        });

        $plans = $this->notificationFilter === 'read'
            ? collect()
            : PlannedVisit::query()->where('accepted_by_id', $viewer->id)->where('status', 'pending')->where('planned_at', '>', now())
            ->with(['user:id,name,profile_uuid', 'conversation:id,conversation_uuid'])->latest('created_at')->limit(20)->get()
            ->map(fn (PlannedVisit $plan) => [
                'id' => null,
                'type' => 'plan',
                'title' => $plan->user->name.' invited you for coffee',
                'detail' => $plan->cafe_name.' - '.$plan->planned_at->timezone(config('app.timezone'))->format('M j, g:i a'),
                'url' => $this->redirectLink('plan', $plan->id, $viewer->id),
                'time' => $plan->created_at,
                'read' => false,
            ]);

        $notifications = $activities->concat($plans)->sortByDesc('time')->values();

        return view('livewire.components.notification-menu', [
            'notifications' => $notifications,
            'unreadCount' => $viewer->unreadNotifications()->where($nonMessageActivities)->count() + PlannedVisit::query()
                ->where('accepted_by_id', $viewer->id)->where('status', 'pending')->where('planned_at', '>', now())->count(),
            'readCount' => $viewer->readNotifications()->where($nonMessageActivities)->count(),
            'hasMore' => $activityCount > $activities->count(),
        ]);
    }
}

