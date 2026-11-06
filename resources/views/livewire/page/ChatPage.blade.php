<div class="mx-auto max-w-[1280px]" x-data="chatRealtime($wire, @js($viewer->profile_uuid))">
    <div x-data="{ chatToast: '' }" x-on:toast.window="chatToast = $event.detail.message; setTimeout(() => chatToast = '', 4000)" x-cloak x-show="chatToast" x-transition role="status" aria-live="polite" class="fixed right-4 top-4 z-[120] max-w-sm rounded-md border border-line bg-surface px-4 py-3 text-sm text-ink shadow-lg" x-text="chatToast"></div>

    <section class="grid h-[calc(100dvh-14rem)] min-h-[320px] overflow-hidden rounded-md border border-line bg-surface lg:h-[calc(100dvh-10rem)] lg:min-h-[420px] lg:grid-cols-[320px_minmax(0,1fr)]" aria-label="Chat">
        <aside @class(['flex min-h-0 flex-col border-r border-line', 'hidden lg:flex' => $recipient || $conversation]) aria-label="Conversations">
            <div class="shrink-0 border-b border-line p-5">
                <div class="flex items-center justify-between gap-3"><h2 class="font-display text-xl font-semibold">Inbox</h2><span class="text-[10px] text-muted">Direct messages</span></div>
                <nav class="mt-4 flex gap-1 border-b border-line" role="tablist" aria-label="Inbox filters">
                    <button type="button" role="tab" aria-selected="{{ $inboxFilter === 'all' ? 'true' : 'false' }}" wire:click="setInboxFilter('all')" @class(['min-h-10 border-b-2 px-3 text-xs font-medium transition', 'border-brand text-brand' => $inboxFilter === 'all', 'border-transparent text-muted hover:text-ink' => $inboxFilter !== 'all'])>All</button>
                    <button type="button" role="tab" aria-selected="{{ $inboxFilter === 'unread' ? 'true' : 'false' }}" wire:click="setInboxFilter('unread')" @class(['flex min-h-10 items-center gap-1.5 border-b-2 px-3 text-xs font-medium transition', 'border-brand text-brand' => $inboxFilter === 'unread', 'border-transparent text-muted hover:text-ink' => $inboxFilter !== 'unread'])>Unread @if($unreadCount)<span class="rounded-full bg-soft px-1.5 py-0.5 text-[10px] tabular-nums">{{ $unreadCount }}</span>@endif</button>
                    <button type="button" role="tab" aria-selected="{{ $inboxFilter === 'muted' ? 'true' : 'false' }}" wire:click="setInboxFilter('muted')" @class(['flex min-h-10 items-center gap-1.5 border-b-2 px-3 text-xs font-medium transition', 'border-brand text-brand' => $inboxFilter === 'muted', 'border-transparent text-muted hover:text-ink' => $inboxFilter !== 'muted'])>Muted @if($mutedCount)<span class="rounded-full bg-soft px-1.5 py-0.5 text-[10px] tabular-nums">{{ $mutedCount }}</span>@endif</button>
                </nav>
            </div>
            <div class="min-h-0 flex-1 divide-y divide-line overflow-y-auto overscroll-contain">
                @forelse($conversations as $item)
                    @php($other = $item->otherUser($viewer))
                    @php($itemSetting = $item->settingFor($viewer))
                    @php($itemUnread = $item->latestMessage && $item->latestMessage->sender_id !== $viewer->id && (! $itemSetting?->last_read_at || $item->latestMessage->created_at->gt($itemSetting->last_read_at)))
                    <button type="button" wire:click="openConversation('{{ $item->conversation_uuid }}')" @class(['flex min-h-[76px] w-full items-center gap-3 px-4 py-3 text-left hover:bg-soft focus-visible:outline-2 focus-visible:outline-inset focus-visible:outline-brand', 'bg-soft' => $conversation?->id === $item->id])>
                        <x-user-avatar :user="$other"/>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center justify-between gap-2"><span class="flex min-w-0 items-center gap-1.5 truncate text-sm {{ $itemUnread ? 'font-bold' : 'font-semibold' }}">{{ $other->name }} @if($itemSetting?->muted_at)<x-coffee-icon name="moon" class="size-3.5 text-muted"/>@endif</span><span class="flex shrink-0 items-center gap-2">@if($itemUnread)<span class="size-2 rounded-full bg-brand" aria-label="Unread"></span>@endif<time class="text-[10px] text-muted">{{ $item->latestMessage?->created_at?->diffForHumans(short: true) }}</time></span></span>
                            <span class="mt-1 block truncate text-xs text-muted">{{ $item->latestMessage?->body ?? 'Start the conversation' }}</span>
                        </span>
                    </button>
                @empty
                    <div class="px-5 py-10 text-center">
                        <x-coffee-icon name="comment" class="mx-auto size-7 text-brand"/>
                        <p class="mt-3 text-sm font-semibold">{{ $inboxFilter === 'unread' ? 'You’re all caught up' : ($inboxFilter === 'muted' ? 'No muted conversations' : 'Your inbox is ready') }}</p>
                        <p class="mt-1 text-xs leading-5 text-muted">{{ $inboxFilter === 'unread' ? 'New messages will appear here.' : ($inboxFilter === 'muted' ? 'Conversations you mute will be collected here.' : 'Visit a creator’s profile and choose Chat to start a conversation.') }}</p>
                    </div>
                @endforelse
            </div>
        </aside>

        <div @class(['flex min-h-0 min-w-0 flex-col', 'hidden lg:flex' => ! $recipient && ! $conversation])>
            @if($recipient && ! $conversation)
                <header class="flex shrink-0 items-center gap-3 border-b border-line px-4 py-3 sm:px-6">
                    <button type="button" wire:click="$set('recipientUuid', '')" class="flex size-10 shrink-0 items-center justify-center text-brand lg:hidden" aria-label="Back to inbox"><x-coffee-icon name="arrow" class="rotate-180"/></button>
                    <x-user-avatar :user="$recipient"/>
                    <div class="min-w-0"><h2 class="truncate text-sm font-semibold">{{ $recipient->name }}</h2><livewire:components.activity-status :user-id="$recipient->id" :key="'chat-activity-'.$recipient->id" /></div>
                </header>
                <div class="flex min-h-0 flex-1 flex-col items-center justify-center overflow-y-auto px-6 py-12 text-center">
                    <div class="flex size-14 items-center justify-center rounded-full bg-soft text-brand"><x-coffee-icon name="comment" class="size-6"/></div>
                    <h3 class="mt-4 font-display text-xl font-semibold">Say hello to {{ $recipient->name }}</h3>
                    <p class="mt-2 max-w-sm text-sm leading-6 text-muted">Ask about a caf&eacute;, share a recommendation, or plan a coffee meetup.</p>
                </div>
            @elseif($conversation)
                @php($other = $conversation->otherUser($viewer))
                <header class="flex shrink-0 items-center gap-3 border-b border-line px-4 py-3 sm:px-6">
                    <button type="button" wire:click="$set('conversationUuid', '')" class="flex size-10 shrink-0 items-center justify-center text-brand lg:hidden" aria-label="Back to inbox"><x-coffee-icon name="arrow" class="rotate-180"/></button>
                    <x-user-avatar :user="$other"/>
                    <div class="min-w-0 flex-1"><h2 class="truncate text-sm font-semibold">{{ $other->name }}</h2><livewire:components.activity-status :user-id="$other->id" :key="'chat-activity-'.$other->id" /></div>
                    <div class="relative ml-auto" x-data="{ open: false }" x-on:keydown.escape.window="open = false" x-on:click.outside="open = false">
                        <button type="button" x-on:click="open = !open" x-bind:aria-expanded="open.toString()" aria-haspopup="menu" aria-label="Conversation actions" class="flex size-10 items-center justify-center rounded-full text-muted hover:bg-soft hover:text-ink"><x-coffee-icon name="more" class="size-5"/></button>
                        <div x-cloak x-show="open" x-transition.origin.top.right role="menu" aria-label="Conversation actions" class="absolute right-0 z-20 mt-2 w-56 rounded-md border border-line bg-surface p-1 shadow-lg">
                            <button type="button" role="menuitem" wire:click="openCafeSearch" x-on:click="open = false" class="flex min-h-11 w-full items-center gap-3 rounded-sm px-3 text-left text-sm text-ink hover:bg-soft"><x-coffee-icon name="search" class="size-4"/>Share a cafe</button>
                            <div class="my-1 border-t border-line"></div>
                            <button type="button" role="menuitem" wire:click="deleteConversation" wire:confirm="Delete this conversation from your inbox? The other person will keep their copy." wire:loading.attr="disabled" wire:target="deleteConversation" x-on:click="open = false" class="flex min-h-11 w-full items-center gap-3 rounded-sm px-3 text-left text-sm text-red-700 hover:bg-soft disabled:opacity-50"><x-coffee-icon name="trash" class="size-4"/>Delete conversation</button>
                            <button type="button" role="menuitem" wire:click="blockConversation" wire:confirm="Block {{ $other->name }}? This will also hide the conversation and stop new messages." wire:loading.attr="disabled" wire:target="blockConversation" x-on:click="open = false" class="flex min-h-11 w-full items-center gap-3 rounded-sm px-3 text-left text-sm text-ink hover:bg-soft disabled:opacity-50"><x-coffee-icon name="block" class="size-4"/>Block {{ $other->name }}</button>
                            <div class="my-1 border-t border-line"></div>
                            <button type="button" role="menuitem" wire:click="toggleMuteConversation" wire:loading.attr="disabled" wire:target="toggleMuteConversation" x-on:click="open = false" class="flex min-h-11 w-full items-center gap-3 rounded-sm px-3 text-left text-sm text-ink hover:bg-soft disabled:opacity-50"><x-coffee-icon name="moon" class="size-4"/>{{ $isMuted ? 'Unmute conversation' : 'Mute conversation' }}</button>
                        </div>
                    </div>
                </header>
                <div wire:key="chat-messages-{{ $conversation->conversation_uuid }}" class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain px-4 py-5 sm:px-6" x-data="{ followLatest: true, currentConversationUuid: @js($conversation->conversation_uuid) }" x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)" x-on:scroll="followLatest = $el.scrollHeight - $el.scrollTop - $el.clientHeight < 96" x-on:chat-message-updated.window="if (followLatest && $event.detail.conversationUuid === currentConversationUuid) $nextTick(() => $el.scrollTo({ top: $el.scrollHeight, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' }))" aria-live="polite" aria-label="Messages">
                    @forelse($messages as $message)
                        <div @class(['flex items-end gap-2', 'justify-end' => $message->sender_id === $viewer->id])>
                            @if($message->sender_id !== $viewer->id)<x-user-avatar :user="$message->sender"/>@endif
                            <div @class(['max-w-[min(82%,520px)] rounded-md px-4 py-3', 'bg-brand text-on-brand' => $message->sender_id === $viewer->id && $message->type !== 'cafe', 'bg-soft text-ink' => $message->sender_id !== $viewer->id || $message->type === 'cafe'])>
                                @if($message->type === 'cafe' && is_array($message->metadata))
                                    <div class="min-w-64">
                                        <p class="text-[10px] font-semibold uppercase tracking-widest text-brand">Cafe recommendation</p>
                                        <h3 class="mt-1 font-display text-lg font-semibold">{{ $message->metadata['name'] }}</h3>
                                        <p class="mt-1 text-xs leading-5 text-muted">{{ $message->metadata['address'] ?: 'Address not listed' }}</p>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @if($activePlan && $activePlan->message_id !== $message->id)
                                                <span class="rounded-sm bg-page px-3 py-2 text-xs text-muted">Another cafe plan is active in this conversation.</span>
                                            @elseif($activePlan && $activePlan->status === 'pending')
                                                @if($activePlan->accepted_by_id === $viewer->id)
                                                    <span class="rounded-sm bg-page px-3 py-2 text-xs text-muted">{{ $activePlan->user_id === $viewer->id ? 'Awaiting acceptance' : 'Plan invitation' }} · {{ $activePlan->planned_at->timezone(config('app.timezone'))->format('M j, g:i a') }}</span>
                                                    <button type="button" wire:click="respondToCafePlan({{ $activePlan->id }}, true)" class="min-h-10 rounded-sm bg-brand px-3 text-xs font-medium text-on-brand">Accept plan</button>
                                                    <button type="button" wire:click="respondToCafePlan({{ $activePlan->id }}, false)" class="min-h-10 rounded-sm border border-line px-3 text-xs">Decline</button>
                                                @else
                                                    <span class="rounded-sm bg-page px-3 py-2 text-xs text-muted">Waiting for {{ $conversation->otherUser($viewer)->name }} · {{ $activePlan->planned_at->timezone(config('app.timezone'))->format('M j, g:i a') }}</span>
                                                @endif
                                            @elseif($activePlan && $activePlan->status === 'accepted')
                                                <span class="rounded-sm bg-page px-3 py-2 text-xs font-medium text-brand">Accepted · {{ $activePlan->planned_at->timezone(config('app.timezone'))->format('M j, g:i a') }}</span>
                                            @else
                                                <button type="button" wire:click="openPlanModal({{ $message->id }})" class="min-h-10 rounded-sm bg-brand px-3 text-xs font-medium text-on-brand hover:opacity-90">Set as plan</button>
                                            @endif
                                            <a href="https://www.openstreetmap.org/?mlat={{ $message->metadata['latitude'] }}&mlon={{ $message->metadata['longitude'] }}#map=16/{{ $message->metadata['latitude'] }}/{{ $message->metadata['longitude'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-10 items-center rounded-sm border border-line px-3 text-xs font-medium text-brand">View map</a>
                                        </div>
                                    </div>
                                @else
                                    <p class="whitespace-pre-wrap break-words text-sm leading-6">{{ $message->body }}</p>
                                @endif
                                <time datetime="{{ $message->created_at->toIso8601String() }}" @class(['mt-2 block text-[10px]', 'text-on-brand/75' => $message->sender_id === $viewer->id && $message->type !== 'cafe', 'text-muted' => $message->sender_id !== $viewer->id || $message->type === 'cafe'])>{{ $message->created_at->format('M j, g:i a') }}</time>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-sm text-muted">Send a message to begin.</div>
                    @endforelse
                </div>
            @else
                <div class="flex flex-1 flex-col items-center justify-center px-8 py-14 text-center">
                    <div class="flex size-16 items-center justify-center rounded-full bg-soft text-brand"><x-coffee-icon name="comment" class="size-7"/></div>
                    <h2 class="mt-5 font-display text-2xl font-semibold">Good coffee starts with a conversation.</h2>
                    <p class="mt-2 max-w-sm text-sm leading-6 text-muted">Choose a conversation from your inbox, or open a creator’s profile and send them a message.</p>
                    <a href="{{ route('home') }}" wire:navigate class="mt-5 inline-flex min-h-11 items-center gap-2 rounded-sm bg-brand px-5 text-sm font-medium text-on-brand"><x-coffee-icon name="discover"/>Discover creators</a>
                </div>
            @endif

            @if($recipient || $conversation)
                <form wire:submit="sendMessage" class="shrink-0 border-t border-line p-3 sm:p-5">
                    <label for="chat-message" class="sr-only">Write a message</label>
                    <div class="flex items-end gap-2">
                        <textarea id="chat-message" wire:model="body" rows="2" maxlength="2000" placeholder="Write a message..." class="max-h-36 min-h-12 flex-1 resize-y rounded-sm border border-line bg-page px-3 py-3 text-sm leading-5 placeholder:text-muted focus:border-brand focus:outline-2 focus:outline-brand" @error('body') aria-invalid="true" aria-describedby="chat-message-error" @enderror></textarea>
                        <button type="submit" wire:loading.attr="disabled" wire:target="sendMessage" aria-label="Send message" title="Send message" class="flex size-12 shrink-0 items-center justify-center rounded-sm bg-brand text-on-brand transition hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand disabled:cursor-wait disabled:opacity-60"><x-coffee-icon name="send" class="size-5"/><span class="sr-only" wire:loading wire:target="sendMessage">Sending message</span></button>
                    </div>
                    @error('body')<p id="chat-message-error" role="alert" class="mt-2 text-xs text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
                    <p class="mt-2 text-right text-[10px] text-muted">Messages are visible only to you and {{ $recipient?->name ?? $conversation?->otherUser($viewer)->name }}.</p>
                </form>
            @endif
        </div>
    </section>
@if($cafeSearchOpen)
    <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4" role="presentation" x-data x-on:keydown.escape.window="$wire.set('cafeSearchOpen', false)">
        <section role="dialog" aria-modal="true" aria-labelledby="cafe-search-title" class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-md border border-line bg-surface p-5 shadow-xl sm:p-6">
            <div class="flex items-start justify-between gap-4"><div><p class="text-[10px] font-semibold uppercase tracking-[.2em] text-brand">Share a place</p><h2 id="cafe-search-title" class="mt-1 font-display text-2xl font-semibold">Find a cafe</h2></div><button type="button" x-data="{ spilling: false, closing: false }" x-on:mouseenter="spilling = true" x-on:mouseleave="if (!closing) spilling = false" x-on:focus="spilling = true" x-on:blur="if (!closing) spilling = false" x-on:click="if (!closing) { closing = true; spilling = true; setTimeout(() => $wire.set('cafeSearchOpen', false), 520) }" class="relative flex size-10 items-center justify-center rounded-full text-muted transition-colors hover:bg-soft focus-visible:outline-2 focus-visible:outline-brand" aria-label="Close cafe search" title="Close cafe search"><svg viewBox="0 0 32 32" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><g x-bind:class="spilling ? 'coffee-spill-cup' : ''"><path d="M7 13h14v8a5 5 0 0 1-5 5h-4a5 5 0 0 1-5-5v-8Z"/><path d="M21 15h2a3 3 0 0 1 0 6h-2"/><path d="M5 28h19"/></g><path x-cloak x-show="spilling" class="coffee-spill-pour" d="M22 14c2 0 2 2 4 2m-3 3c2 0 2 2 4 2"/><path x-cloak x-show="spilling" class="coffee-spill-drop" d="M29 20c-1 1-1 2 0 2s1-1 0-2Z"/></svg><span x-cloak x-show="spilling" x-transition.opacity aria-hidden="true" class="absolute right-0 top-0 flex size-4 items-center justify-center rounded-full bg-surface text-brand shadow-sm"><x-lucide-x class="size-3"/></span></button></div>
            <form wire:submit="searchCafes" class="mt-5 flex gap-2"><label for="chat-cafe-search" class="sr-only">Search cafes</label><input id="chat-cafe-search" wire:model="cafeSearchQuery" maxlength="200" placeholder="Cafe, neighborhood, or city" class="min-h-11 min-w-0 flex-1 rounded-sm border border-line bg-page px-3 text-sm"><button type="submit" wire:loading.attr="disabled" wire:target="searchCafes" aria-label="Search places" title="Search places" class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand text-on-brand transition-transform active:scale-95 disabled:opacity-70"><x-lucide-search class="size-5" wire:loading.class="chat-search-sweep" wire:target="searchCafes"/><span class="sr-only">Search</span></button></form>
            <p wire:loading wire:target="searchCafes" class="mt-3 text-xs text-muted">Searching places…</p>
            @error('cafeSearchQuery')<p class="mt-2 text-xs text-red-700">Enter at least 3 characters.</p>@enderror
            @if($cafeSearchError)<p role="alert" class="mt-3 text-sm text-red-700">{{ $cafeSearchError }}</p>@endif
            <ul class="mt-4 divide-y divide-line">@foreach($cafeSearchResults as $index => $place)<li wire:key="chat-cafe-{{ $index }}" class="flex items-start justify-between gap-4 py-4"><div class="min-w-0"><h3 class="font-semibold">{{ $place['name'] }}</h3><p class="mt-1 text-xs leading-5 text-muted">{{ $place['address'] ?: 'Address not listed' }}</p></div><button type="button" wire:click="shareCafe({{ $index }})" wire:loading.attr="disabled" wire:target="shareCafe" class="min-h-10 shrink-0 rounded-sm border border-brand px-3 text-xs font-semibold text-brand">Share</button></li>@endforeach</ul>
        </section>
    </div>
@endif

@if($planModalOpen)
    <div class="fixed inset-0 z-[110] flex items-center justify-center bg-black/50 p-4" role="presentation" x-data x-on:keydown.escape.window="$wire.set('planModalOpen', false)" x-on:click.self="$wire.set('planModalOpen', false)">
        <section role="dialog" aria-modal="true" aria-labelledby="plan-cafe-title" class="w-full max-w-md rounded-md border border-line bg-surface p-5 shadow-xl sm:p-6">
            <h2 id="plan-cafe-title" class="font-display text-2xl font-semibold">Plan a cafe visit</h2><p class="mt-2 text-sm text-muted">Choose a date and time, then invite the other person. The cafe will appear on both maps after they accept.</p>
            <form wire:submit="saveCafePlan" class="mt-5 space-y-4">
                <div><label for="plan-date" class="mb-1 block text-sm font-medium">Date</label><input id="plan-date" type="date" wire:model="planDate" min="{{ now()->toDateString() }}" required class="min-h-11 w-full rounded-sm border border-line bg-page px-3 text-sm">@error('planDate')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror</div>
                <div><label for="plan-time" class="mb-1 block text-sm font-medium">Time</label><input id="plan-time" type="time" wire:model="planTime" required class="min-h-11 w-full rounded-sm border border-line bg-page px-3 text-sm">@error('planTime')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror</div>
                @error('planningMessageId')<p class="text-xs text-red-700">{{ $message }}</p>@enderror
                <div class="flex justify-end gap-2 pt-2"><button type="button" wire:click="$set('planModalOpen', false)" class="min-h-11 rounded-sm border border-line px-4 text-sm">Cancel</button><button type="submit" wire:loading.attr="disabled" wire:target="saveCafePlan" class="min-h-11 rounded-sm bg-brand px-4 text-sm font-medium text-on-brand"><span wire:loading.remove wire:target="saveCafePlan">Send plan</span><span wire:loading wire:target="saveCafePlan">Sending…</span></button></div>
            </form>
        </section>
    </div>
@endif
</div>
