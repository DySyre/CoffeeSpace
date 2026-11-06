<?php

namespace App\Livewire\Page;

use App\Events\ChatUpdated;
use App\Models\Conversation;
use App\Models\ConversationUserSetting;
use App\Models\Message;
use App\Models\PlannedVisit;
use App\Models\User;
use App\Services\ActivityNotifier;
use App\Services\PlaceSearch;
use App\Services\RealtimeActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class ChatPage extends Component
{
    #[Url(as: 'conversation', except: '')]
    public string $conversationUuid = '';

    #[Url(as: 'with', except: '')]
    public string $recipientUuid = '';

    public string $body = '';

    public string $inboxFilter = 'all';

    public bool $cafeSearchOpen = false;

    public string $cafeSearchQuery = '';

    #[Locked]
    public array $cafeSearchResults = [];

    #[Locked]
    public string $cafeSearchError = '';

    public bool $planModalOpen = false;

    #[Locked]
    public ?int $planningMessageId = null;

    public string $planDate = '';

    public string $planTime = '';

    public function mount(): void
    {
        $conversationReference = request()->query('conversation', '');
        abort_unless(is_string($conversationReference), 404);

        if ($conversationReference !== '' && ctype_digit($conversationReference)) {
            $legacyConversation = Conversation::forUser(auth()->user())->findOrFail((int) $conversationReference);
            $this->redirectRoute('chat.index', ['conversation' => $legacyConversation->conversation_uuid], navigate: true);

            return;
        }

        $this->conversationUuid = $conversationReference;
        $this->recipientUuid = (string) request()->query('with', '');
        $this->selectedConversation();
        if ($this->conversationUuid !== '') {
            $this->markConversationRead();
        }
        $this->selectedRecipient();
    }

    public function openConversation(string $conversationUuid): void
    {
        $conversation = Conversation::forUser(auth()->user())
            ->whereDoesntHave('userSettings', fn ($settings) => $settings->where('user_id', auth()->id())->whereNotNull('deleted_at'))
            ->where('conversation_uuid', $conversationUuid)
            ->firstOrFail();
        $this->conversationUuid = $conversation->conversation_uuid;
        $this->recipientUuid = '';
        $this->reset('body');
        $this->markConversationRead();
    }

    public function setInboxFilter(string $filter): void
    {
        abort_unless(in_array($filter, ['all', 'unread', 'muted'], true), 404);
        $this->inboxFilter = $filter;
    }

    public function markConversationRead(): void
    {
        $conversation = $this->selectedConversation();
        if (! $conversation) {
            return;
        }
        $lastIncomingAt = $conversation->messages()->where('sender_id', '<>', auth()->id())->max('created_at');
        if (! $lastIncomingAt) {
            return;
        }
        $setting = ConversationUserSetting::firstOrNew([
            'conversation_id' => $conversation->id,
            'user_id' => auth()->id(),
        ]);
        if (! $setting->last_read_at || $setting->last_read_at->lt($lastIncomingAt)) {
            $setting->last_read_at = now();
            $setting->save();
        }
        auth()->user()->unreadNotifications()
            ->where('data->context->conversation_uuid', $conversation->conversation_uuid)
            ->update(['read_at' => now()]);
        app(RealtimeActivity::class)->notify(auth()->user(), 'MESSAGE');
    }

    public function refreshChat(?string $incomingConversationUuid = null, ?string $senderUuid = null): void
    {
        if ($incomingConversationUuid !== null
            && $this->conversationUuid === ''
            && $this->recipientUuid !== ''
            && $senderUuid !== null
            && hash_equals($this->recipientUuid, $senderUuid)) {
            $conversation = Conversation::forUser(auth()->user())
                ->where('conversation_uuid', $incomingConversationUuid)
                ->first();

            if ($conversation && $conversation->otherUser(auth()->user())->profile_uuid === $this->recipientUuid) {
                $this->conversationUuid = $conversation->conversation_uuid;
                $this->recipientUuid = '';
            }
        }

        if ($this->conversationUuid !== ''
            && ($incomingConversationUuid === null || hash_equals($this->conversationUuid, $incomingConversationUuid))) {
            $this->markConversationRead();
        }
    }

    public function startConversation(string $profileUuid): void
    {
        $recipient = User::where('profile_uuid', $profileUuid)->firstOrFail();
        abort_if($recipient->is(auth()->user()), 403);
        $this->assertNotBlocked(auth()->user(), $recipient);
        $this->conversationUuid = '';
        $this->recipientUuid = $recipient->profile_uuid;
        $this->reset('body');
    }

    public function sendMessage(): void
    {
        $this->body = trim($this->body);
        $this->validate(['body' => ['required', 'string', 'max:2000']]);

        $viewer = auth()->user();
        $conversation = $this->selectedConversation();
        $recipient = $conversation?->otherUser($viewer) ?? $this->selectedRecipient();

        abort_unless($recipient && ! $recipient->is($viewer), 403);
        $this->assertNotBlocked($viewer, $recipient);

        $rateKey = 'chat-messages:'.$viewer->id;
        if (RateLimiter::tooManyAttempts($rateKey, 30)) {
            throw ValidationException::withMessages(['body' => 'You have sent several messages. Please wait a minute and try again.']);
        }

        $conversation = DB::transaction(function () use ($viewer, $recipient) {
            $firstId = min($viewer->id, $recipient->id);
            $secondId = max($viewer->id, $recipient->id);

            return Conversation::firstOrCreate(['user_one_id' => $firstId, 'user_two_id' => $secondId]);
        });
        ConversationUserSetting::query()
            ->where('conversation_id', $conversation->id)
            ->whereIn('user_id', [$viewer->id, $recipient->id])
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null, 'updated_at' => now()]);

        $message = $conversation->messages()->make(['body' => $this->body]);
        $message->sender()->associate($viewer);
        $message->save();
        $recipientMuted = $conversation->settingFor($recipient)?->muted_at !== null;
        app(ActivityNotifier::class)->send(
            $recipient,
            'MESSAGE',
            'You have a new message',
            $viewer->name,
            '',
            'View message',
            route('chat.index', ['conversation' => $conversation->conversation_uuid]),
            allowEmail: ! $recipientMuted,
            context: ['conversation_uuid' => $conversation->conversation_uuid],
        );
        $conversation->touch();
        $this->broadcastChatUpdate($conversation, $viewer, $recipient, $message);
        RateLimiter::hit($rateKey, 60);
        $this->conversationUuid = $conversation->conversation_uuid;
        $this->recipientUuid = '';
        $this->reset('body');
    }

    public function openCafeSearch(): void
    {
        $this->selectedConversation();
        $this->selectedRecipient();
        abort_unless($this->conversationUuid !== '' || $this->recipientUuid !== '', 404);
        $this->cafeSearchOpen = true;
        $this->cafeSearchError = '';
        $this->cafeSearchResults = [];
    }

    public function searchCafes(PlaceSearch $places): void
    {
        $this->cafeSearchQuery = trim($this->cafeSearchQuery);
        $this->validate(['cafeSearchQuery' => ['required', 'string', 'min:3', 'max:200']]);
        $this->cafeSearchError = '';
        try {
            $this->cafeSearchResults = $places->search($this->cafeSearchQuery);
        } catch (\Throwable) {
            $this->cafeSearchError = 'Cafe search is unavailable right now. Please try again shortly.';
        }
    }

    public function shareCafe(int $index): void
    {
        $this->validate(['cafeSearchQuery' => ['required', 'string', 'min:3', 'max:200']]);
        abort_unless(array_key_exists($index, $this->cafeSearchResults), 404);
        $place = validator($this->cafeSearchResults[$index], [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ])->validate();

        $viewer = auth()->user();
        $conversation = $this->selectedConversation();
        $recipient = $conversation?->otherUser($viewer) ?? $this->selectedRecipient();
        abort_unless($recipient && ! $recipient->is($viewer), 403);
        $this->assertNotBlocked($viewer, $recipient);
        $conversation ??= DB::transaction(function () use ($viewer, $recipient) {
            $firstId = min($viewer->id, $recipient->id);
            $secondId = max($viewer->id, $recipient->id);

            return Conversation::firstOrCreate(['user_one_id' => $firstId, 'user_two_id' => $secondId]);
        });
        $this->assertNotBlocked($viewer, $conversation->otherUser($viewer));
        ConversationUserSetting::query()->where('conversation_id', $conversation->id)
            ->whereIn('user_id', [$viewer->id, $recipient->id])->whereNotNull('deleted_at')
            ->update(['deleted_at' => null, 'updated_at' => now()]);
        if (RateLimiter::tooManyAttempts('chat-messages:'.$viewer->id, 30)) {
            throw ValidationException::withMessages(['cafeSearchQuery' => 'You have shared several messages. Please wait a minute and try again.']);
        }

        $message = $conversation->messages()->make([
            'body' => 'Shared a cafe: '.$place['name'],
            'type' => 'cafe',
            'metadata' => [
                'name' => $place['name'], 'address' => $place['address'] ?? '',
                'latitude' => (float) $place['latitude'], 'longitude' => (float) $place['longitude'],
            ],
        ]);
        $message->sender()->associate($viewer);
        $message->save();
        app(ActivityNotifier::class)->send(
            $recipient,
            'MESSAGE',
            'A cafe was shared with you',
            $viewer->name,
            $place['name'],
            'View cafe',
            route('chat.index', ['conversation' => $conversation->conversation_uuid]),
            allowEmail: ! ($conversation->settingFor($recipient)?->muted_at !== null),
            context: ['conversation_uuid' => $conversation->conversation_uuid],
        );
        $conversation->touch();
        $this->broadcastChatUpdate($conversation, $viewer, $recipient, $message);
        RateLimiter::hit('chat-messages:'.$viewer->id, 60);
        $this->conversationUuid = $conversation->conversation_uuid;
        $this->recipientUuid = '';
        $this->cafeSearchOpen = false;
        $this->cafeSearchResults = [];
        $this->reset('cafeSearchQuery');
    }

    public function openPlanModal(int $messageId): void
    {
        $conversation = $this->selectedConversation();
        abort_unless($conversation, 404);
        $message = $conversation->messages()->where('type', 'cafe')->findOrFail($messageId);
        abort_unless(is_array($message->metadata), 404);
        $activePlan = PlannedVisit::query()->where('conversation_id', $conversation->id)
            ->where('planned_at', '>', now())->whereIn('status', ['pending', 'accepted'])->first();
        if ($activePlan && (int) $activePlan->message_id !== $message->id) {
            $this->dispatch('toast', message: 'There is already an active cafe plan in this conversation.');

            return;
        }
        $this->planningMessageId = $message->id;
        $this->planDate = now()->addDay()->toDateString();
        $this->planTime = '10:00';
        $this->planModalOpen = true;
    }

    public function saveCafePlan(): void
    {
        $this->validate([
            'planningMessageId' => ['required', 'integer'],
            'planDate' => ['required', 'date', 'after_or_equal:today'],
            'planTime' => ['required', 'date_format:H:i'],
        ]);
        $conversation = $this->selectedConversation();
        abort_unless($conversation, 404);
        $message = $conversation->messages()->where('type', 'cafe')->findOrFail($this->planningMessageId);
        $place = $message->metadata;
        abort_unless(is_array($place) && isset($place['latitude'], $place['longitude'], $place['name']), 404);
        $plannedAt = Carbon::createFromFormat('Y-m-d H:i', $this->planDate.' '.$this->planTime, config('app.timezone'));
        if ($plannedAt->isPast()) {
            $this->addError('planTime', 'Choose a date and time in the future.');

            return;
        }
        $viewer = auth()->user();
        $other = $conversation->otherUser($viewer);
        DB::transaction(function () use ($conversation, $message, $place, $plannedAt, $viewer, $other) {
            Conversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            $activePlan = PlannedVisit::query()->where('conversation_id', $conversation->id)
                ->where('planned_at', '>', now())->whereIn('status', ['pending', 'accepted'])->lockForUpdate()->first();
            if ($activePlan && (int) $activePlan->message_id !== $message->id) {
                throw ValidationException::withMessages(['planDate' => 'This conversation already has an active cafe plan. Wait until its date passes before adding another.']);
            }
            if ($activePlan) {
                if ((int) $activePlan->user_id !== $viewer->id) {
                    throw ValidationException::withMessages(['planDate' => 'Only the person who suggested this cafe can change the pending plan.']);
                }
                $activePlan->update([
                    'planned_at' => $plannedAt->utc(), 'status' => 'pending', 'responded_at' => null,
                ]);

                return;
            }
            PlannedVisit::create([
                'user_id' => $viewer->id, 'accepted_by_id' => $other->id,
                'message_id' => $message->id, 'conversation_id' => $conversation->id,
                'cafe_name' => $place['name'], 'address' => $place['address'] ?? '',
                'latitude' => $place['latitude'], 'longitude' => $place['longitude'],
                'planned_at' => $plannedAt->utc(), 'status' => 'pending',
            ]);
        });
        app(RealtimeActivity::class)->notify($other, 'PLAN');
        $this->broadcastChatUpdate($conversation, $viewer, $other);
        $this->planModalOpen = false;
        $this->dispatch('toast', message: 'Plan sent. It will appear on both maps after the other person accepts.');
    }

    public function respondToCafePlan(int $planId, bool $accept): void
    {
        $conversation = $this->selectedConversation();
        abort_unless($conversation, 404);
        $plan = PlannedVisit::query()->where('conversation_id', $conversation->id)->whereKey($planId)->firstOrFail();
        abort_unless((int) $plan->accepted_by_id === auth()->id(), 403);
        abort_unless($plan->status === 'pending' && $plan->planned_at->isFuture(), 409);
        $plan->update(['status' => $accept ? 'accepted' : 'declined', 'responded_at' => now()]);
        $viewer = auth()->user();
        app(RealtimeActivity::class)->notify($conversation->otherUser($viewer), 'PLAN');
        app(RealtimeActivity::class)->notify($viewer, 'PLAN');
        $this->broadcastChatUpdate($conversation, $viewer, $conversation->otherUser($viewer));
        $this->dispatch('toast', message: $accept ? 'Cafe plan accepted. It is now on both maps.' : 'Cafe plan declined.');
    }

    private function selectedConversation(): ?Conversation
    {
        if ($this->conversationUuid === '') {
            return null;
        }

        $conversation = Conversation::forUser(auth()->user())
            ->with(['userOne:id,name,profile_uuid,profile_photo', 'userTwo:id,name,profile_uuid,profile_photo', 'userSettings'])
            ->whereDoesntHave('userSettings', fn ($settings) => $settings->where('user_id', auth()->id())->whereNotNull('deleted_at'))
            ->where('conversation_uuid', $this->conversationUuid)
            ->firstOrFail();
        $this->assertNotBlocked(auth()->user(), $conversation->otherUser(auth()->user()));

        return $conversation;
    }

    private function broadcastChatUpdate(Conversation $conversation, User $sender, User $recipient, ?Message $message = null): void
    {
        try {
            event(new ChatUpdated(
                $conversation->conversation_uuid,
                $sender->profile_uuid,
                $recipient->profile_uuid,
                $message?->id,
            ));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function toggleMuteConversation(): void
    {
        $conversation = $this->selectedConversation();
        abort_unless($conversation, 404);

        $setting = ConversationUserSetting::firstOrNew([
            'conversation_id' => $conversation->id,
            'user_id' => auth()->id(),
        ]);
        $setting->muted_at = $setting->muted_at ? null : now();
        $setting->save();
    }

    public function deleteConversation(): void
    {
        $conversation = $this->selectedConversation();
        abort_unless($conversation, 404);

        $setting = ConversationUserSetting::firstOrNew([
            'conversation_id' => $conversation->id,
            'user_id' => auth()->id(),
        ]);
        $setting->deleted_at = now();
        $setting->save();

        $this->conversationUuid = '';
        $this->recipientUuid = '';
        $this->reset('body');
        $this->redirectRoute('chat.index', navigate: true);
    }

    public function blockConversation(): void
    {
        $conversation = $this->selectedConversation();
        abort_unless($conversation, 404);

        $viewer = auth()->user();
        $other = $conversation->otherUser($viewer);
        DB::transaction(function () use ($viewer, $other) {
            $viewer->blockedUsers()->syncWithoutDetaching([$other->id]);
            $viewer->following()->detach($other->id);
            $viewer->followers()->detach($other->id);
        });

        $this->conversationUuid = '';
        $this->recipientUuid = '';
        $this->reset('body');
        $this->redirectRoute('chat.index', navigate: true);
    }

    private function selectedRecipient(): ?User
    {
        if ($this->recipientUuid === '') {
            return null;
        }

        $recipient = User::where('profile_uuid', $this->recipientUuid)->firstOrFail();
        abort_if($recipient->is(auth()->user()), 403);
        $this->assertNotBlocked(auth()->user(), $recipient);

        return $recipient;
    }

    private function assertNotBlocked(User $viewer, User $recipient): void
    {
        abort_if($viewer->hasBlocked($recipient) || $recipient->hasBlocked($viewer), 404);
    }

    public function render(): View
    {
        $viewer = auth()->user();
        $baseConversationQuery = Conversation::forUser($viewer)
            ->whereDoesntHave('userSettings', fn ($settings) => $settings->where('user_id', $viewer->id)->whereNotNull('deleted_at'))
            ->whereNotExists(function ($blocks) use ($viewer) {
                $blocks->selectRaw('1')->from('user_blocks')->where(function ($pair) use ($viewer) {
                    $pair->where(function ($outgoing) use ($viewer) {
                        $outgoing->where('user_blocks.blocker_id', $viewer->id)->where(function ($other) {
                            $other->whereColumn('user_blocks.blocked_id', 'conversations.user_one_id')
                                ->orWhereColumn('user_blocks.blocked_id', 'conversations.user_two_id');
                        });
                    })->orWhere(function ($incoming) use ($viewer) {
                        $incoming->where('user_blocks.blocked_id', $viewer->id)->where(function ($other) {
                            $other->whereColumn('user_blocks.blocker_id', 'conversations.user_one_id')
                                ->orWhereColumn('user_blocks.blocker_id', 'conversations.user_two_id');
                        });
                    });
                });
            });
        $unreadConversations = fn ($query) => $query->whereHas('latestMessage', fn ($message) => $message
            ->where('sender_id', '<>', $viewer->id)
            ->whereRaw('messages.created_at > COALESCE((SELECT last_read_at FROM conversation_user_settings WHERE conversation_id = messages.conversation_id AND user_id = ?), ?)', [$viewer->id, '1970-01-01 00:00:00']));
        $mutedConversations = fn ($query) => $query->whereHas('userSettings', fn ($settings) => $settings
            ->where('user_id', $viewer->id)->whereNotNull('muted_at'));
        $unreadCount = (clone $baseConversationQuery)->where($unreadConversations)->count();
        $mutedCount = (clone $baseConversationQuery)->where($mutedConversations)->count();
        $conversationQuery = (clone $baseConversationQuery)
            ->with(['userOne:id,name,profile_uuid,profile_photo', 'userTwo:id,name,profile_uuid,profile_photo', 'latestMessage', 'userSettings']);
        if ($this->inboxFilter === 'unread') {
            $conversationQuery->where($unreadConversations);
        } elseif ($this->inboxFilter === 'muted') {
            $conversationQuery->where($mutedConversations);
        }
        $conversations = $conversationQuery->orderByDesc('updated_at')->limit(50)->get();

        $conversation = $this->selectedConversation();
        $recipient = $conversation?->otherUser($viewer) ?? $this->selectedRecipient();
        $isMuted = $conversation?->settingFor($viewer)?->muted_at !== null;
        $messages = $conversation
            ? $conversation->messages()->with(['sender:id,name,profile_uuid,profile_photo', 'plannedVisits'])->latest('id')->limit(100)->get()->reverse()->values()
            : collect();
        $activePlan = $conversation
            ? PlannedVisit::query()->where('conversation_id', $conversation->id)->where('planned_at', '>', now())->whereIn('status', ['pending', 'accepted'])->first()
            : null;

        return view('livewire.page.ChatPage', compact('conversations', 'conversation', 'recipient', 'messages', 'viewer', 'isMuted', 'activePlan', 'unreadCount', 'mutedCount'))
            ->layout('components.layouts.coffee', ['title' => 'Chat - CoffeeSpace', 'section' => 'chat']);
    }
}
