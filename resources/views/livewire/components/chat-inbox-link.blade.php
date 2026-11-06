<div x-data x-on:coffee-activity-updated.window="if (['MESSAGE', 'SYNC'].includes($event.detail.category)) $wire.$refresh()">
    <a href="{{ route('chat.index') }}" wire:navigate x-data="{ inboxPopping: false }" x-on:click="inboxPopping = true; setTimeout(() => inboxPopping = false, 360)" x-bind:class="searchOpen ? 'max-sm:hidden' : ''" aria-label="Open inbox{{ $unreadCount ? ', '.$unreadCount.' unread messages' : '' }}" title="Inbox" class="relative flex size-10 shrink-0 items-center justify-center rounded-sm border border-line text-brand hover:bg-soft">
        <x-coffee-icon name="comment" x-bind:class="inboxPopping ? 'inbox-pop' : ''"/>
        @if($unreadCount)<span class="absolute -right-1 -top-1 flex min-w-4 items-center justify-center rounded-full bg-brand px-1 text-[10px] font-semibold leading-4 text-on-brand">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>@endif
    </a>
</div>
