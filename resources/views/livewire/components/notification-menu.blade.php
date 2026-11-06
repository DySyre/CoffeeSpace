<div class="relative" x-data="{ open: @entangle('open'), ringing: false, ringBell() { this.ringing = false; this.$nextTick(() => { this.ringing = true; setTimeout(() => this.ringing = false, 700); }); } }" x-on:coffee-activity-updated.window="if ($event.detail.category !== 'MESSAGE') $wire.$refresh()" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false" x-on:livewire:navigating.window="open = false">
    <button type="button" x-on:click="open = !open; ringBell()" x-bind:aria-expanded="open.toString()" aria-haspopup="dialog" aria-label="Notifications{{ $unreadCount ? ', '.$unreadCount.' unread' : '' }}" title="Notifications" class="relative flex size-10 items-center justify-center rounded-sm border border-line text-brand transition-colors hover:bg-soft">
        <x-lucide-bell x-bind:class="ringing ? 'notification-bell-ring' : ''" class="size-5" aria-hidden="true" />
        @if ($unreadCount)<span class="absolute -right-1 -top-1 flex min-w-4 items-center justify-center rounded-full bg-brand px-1 text-[10px] font-semibold leading-4 text-on-brand">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>@endif
    </button>
    <section x-cloak x-show="open" x-transition.origin.top.right role="dialog" aria-label="Notifications" class="fixed inset-x-3 top-20 z-50 max-h-[78vh] w-auto overflow-y-auto rounded-md border border-line bg-surface text-ink shadow-lg sm:absolute sm:inset-x-auto sm:top-full sm:right-0 sm:mt-2 sm:w-[min(88vw,360px)]">
        <header class="border-b border-line px-4 py-3">
            <div class="flex items-center justify-between gap-3"><h2 class="font-display text-lg font-semibold">Notifications</h2>@if ($unreadCount)<span class="text-xs text-muted">{{ $unreadCount }} unread</span>@endif</div>
            <nav class="mt-3 flex gap-1 border-b border-line" role="tablist" aria-label="Notification status">
                <button type="button" role="tab" aria-selected="{{ $notificationFilter === 'all' ? 'true' : 'false' }}" wire:click="setNotificationFilter('all')" @class(['min-h-9 border-b-2 px-3 text-xs font-medium', 'border-brand text-brand' => $notificationFilter === 'all', 'border-transparent text-muted hover:text-ink' => $notificationFilter !== 'all'])>All</button>
                <button type="button" role="tab" aria-selected="{{ $notificationFilter === 'unread' ? 'true' : 'false' }}" wire:click="setNotificationFilter('unread')" @class(['min-h-9 border-b-2 px-3 text-xs font-medium', 'border-brand text-brand' => $notificationFilter === 'unread', 'border-transparent text-muted hover:text-ink' => $notificationFilter !== 'unread'])>Unread @if($unreadCount)<span class="ml-1 rounded-full bg-soft px-1.5 py-0.5 text-[10px]">{{ $unreadCount }}</span>@endif</button>
                <button type="button" role="tab" aria-selected="{{ $notificationFilter === 'read' ? 'true' : 'false' }}" wire:click="setNotificationFilter('read')" @class(['min-h-9 border-b-2 px-3 text-xs font-medium', 'border-brand text-brand' => $notificationFilter === 'read', 'border-transparent text-muted hover:text-ink' => $notificationFilter !== 'read'])>Read @if($readCount)<span class="ml-1 rounded-full bg-soft px-1.5 py-0.5 text-[10px]">{{ $readCount }}</span>@endif</button>
            </nav>
        </header>
        @forelse ($notifications as $notification)
            <div @class(['flex items-center gap-3 border-b border-line px-4 py-3 last:border-0 transition-colors hover:bg-soft', 'bg-soft/70' => ! $notification['read']])>
                <a href="{{ $notification['url'] }}" wire:navigate x-on:click="open = false" class="flex min-w-0 flex-1 items-start gap-2 focus-visible:outline-2 focus-visible:outline-brand">
                    <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full bg-soft text-brand"><x-coffee-icon :name="$notification['type'] === 'plan' ? 'map' : ($notification['type'] === 'saved' ? 'bookmark' : 'comment')" class="size-4" /></span>
                    <span aria-hidden="true" @class(['-ml-1 mt-1 size-2 shrink-0 rounded-full', 'bg-brand' => ! $notification['read'], 'bg-transparent' => $notification['read']])></span>
                    <span class="min-w-0"><span class="block text-sm font-semibold">@if(! $notification['read'])<span class="sr-only">Unread. </span>@endif{{ $notification['title'] }}</span><span class="mt-1 block truncate text-xs text-muted">{{ $notification['detail'] }}</span><time class="mt-1 block text-[10px] text-muted">{{ $notification['time']->diffForHumans() }}</time></span>
                </a>
            </div>
        @empty
            <p class="px-4 py-8 text-center text-sm text-muted">{{ $notificationFilter === 'unread' ? 'You are all caught up.' : ($notificationFilter === 'read' ? 'No past notifications yet.' : 'No notifications yet.') }}</p>
        @endforelse
        @if($hasMore)
            <button type="button" wire:click="loadOlder" wire:loading.attr="disabled" wire:target="loadOlder" class="min-h-11 w-full border-t border-line px-4 text-xs font-semibold text-brand hover:bg-soft disabled:opacity-60">Load older notifications</button>
        @endif
    </section>
</div>
