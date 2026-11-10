<div class="mx-auto max-w-[960px]" x-data="profileImageCropper($wire)" x-on:profile-photo-saved.window="photoUploading = false" x-on:cover-photo-saved.window="coverUploading = false" x-on:profile-image-result.window="handleImageResult($event.detail)" x-on:post-edit-opened.window="$nextTick(() => $refs.editPost.showModal())" x-on:post-edit-closed.window="if ($refs.editPost?.open) $refs.editPost.close(); clearEditPreviews()" x-on:post-managed.window="$el.querySelectorAll('dialog[open]').forEach(dialog => { dialog.querySelectorAll('video').forEach(video => video.pause()); dialog.close(); }); clearEditPreviews(); showToast('success', $event.detail.message, 'Post updated')">
    <div class="mb-6 flex items-center justify-between gap-4">
        <h1 class="font-display text-2xl font-semibold">{{ $isOwner ? 'My Profile' : 'Coffee Profile' }}</h1>
        <div class="flex items-center gap-2">
            <button type="button" x-on:click="openProfileShare(@js($user->profileUrl()), @js($user->name))" class="group flex min-h-11 items-center rounded-sm border border-line px-2 text-brand hover:bg-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand motion-reduce:transition-none">
                <x-lucide-share class="size-5 shrink-0" aria-hidden="true"/>
                <span class="max-w-0 overflow-hidden whitespace-nowrap text-sm font-medium opacity-0 transition-all duration-200 group-hover:ml-2 group-hover:max-w-48 group-hover:opacity-100 group-focus-visible:ml-2 group-focus-visible:max-w-48 group-focus-visible:opacity-100 motion-reduce:transition-none">Share profile</span>
            </button>
            @if($isOwner)<a href="{{ route('settings.profile') }}" wire:navigate aria-label="Profile settings" class="flex size-11 items-center justify-center rounded-sm border border-line text-brand hover:bg-soft"><x-coffee-icon name="settings"/></a>@endif
        </div>
    </div>

    <section aria-label="Profile cover">
        <div class="relative flex aspect-[3/1] min-h-44 w-full min-w-0 items-center justify-center overflow-hidden bg-soft">
            @if($user->cover_photo)
                <img src="{{ $user->coverPhotoUrl() }}" alt="Your profile cover" class="absolute inset-0 size-full object-cover">
            @else
                <div class="px-5 pb-12 text-center text-muted"><x-coffee-icon name="image" class="mx-auto size-7 text-brand"/><p class="mt-2 text-sm">A favorite corner. A place that feels like you.</p></div>
            @endif
            @if($isOwner)<button type="button" x-on:click="$refs.coverPhoto.click()" x-bind:disabled="coverUploading" aria-label="{{ $user->cover_photo ? 'Change cover photo' : 'Add cover photo' }}" title="{{ $user->cover_photo ? 'Change cover photo' : 'Add cover photo' }}" class="group absolute right-3 bottom-3 z-10 flex min-h-11 items-center rounded-sm border border-line bg-surface px-3 text-sm font-medium text-brand disabled:opacity-50">
                <x-coffee-icon name="image"/>
                <span class="max-w-0 overflow-hidden whitespace-nowrap opacity-0 transition-all duration-200 group-hover:ml-2 group-hover:max-w-48 group-hover:opacity-100 group-focus-visible:ml-2 group-focus-visible:max-w-48 group-focus-visible:opacity-100 motion-reduce:transition-none">{{ $user->cover_photo ? 'Change cover photo' : 'Add cover photo' }}</span>
            </button>@endif
        </div>
        @if($isOwner)<input x-ref="coverPhoto" wire:key="cover-photo-{{ $user->cover_photo['path'] ?? 'new' }}" x-on:change="chooseImage($event, 'cover')" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" hidden aria-label="Upload cover photo" x-on:livewire-upload-start="coverUploading = true" x-on:livewire-upload-finish="coverUploading = false" x-on:livewire-upload-error="coverUploading = false" x-on:livewire-upload-cancel="coverUploading = false">
        <p class="sr-only">JPEG or PNG, up to 10 MB. A wide photo works best.</p>@endif
    </section>

    <section aria-label="Your coffee profile" class="border-b border-line px-1 pb-6 sm:px-5">
        <div class="flex items-start gap-4 sm:gap-6">
            <div class="relative z-10 -mt-8 shrink-0 sm:-mt-12">
                @if($isOwner)<button type="button" x-on:click="$refs.profilePhoto.click()" x-bind:disabled="photoUploading" class="group relative flex size-24 items-center justify-center overflow-hidden rounded-full border-4 border-page bg-soft sm:size-32 font-display text-xl font-semibold text-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand disabled:opacity-50" aria-label="{{ $user->profile_photo ? 'Change profile photo' : 'Add profile photo' }}">
                    @if($user->profile_photo)
                        <img src="{{ $user->profilePhotoUrl() }}" alt="Your profile photo" class="size-full object-cover">
                    @else
                        <span aria-hidden="true">{{ mb_substr($user->initials(), 0, 2) }}</span>
                    @endif
                    <span class="absolute inset-0 hidden items-center justify-center bg-brand text-on-brand group-hover:flex group-focus-visible:flex"><x-coffee-icon name="camera"/></span>
                </button>
                <input x-ref="profilePhoto" wire:key="profile-photo-{{ $user->profile_photo['path'] ?? 'new' }}" type="file" x-on:change="chooseImage($event, 'photo')" accept=".jpg,.jpeg,.png,image/jpeg,image/png" hidden aria-label="Upload profile photo" x-on:livewire-upload-start="photoUploading = true" x-on:livewire-upload-finish="photoUploading = false" x-on:livewire-upload-error="photoUploading = false" x-on:livewire-upload-cancel="photoUploading = false">
                @else
                    <span class="flex size-24 items-center justify-center overflow-hidden rounded-full border-4 border-page bg-soft text-xl font-semibold text-brand sm:size-32">
                        @if($user->profile_photo)<img src="{{ $user->profilePhotoUrl() }}" alt="{{ $user->name }} profile photo" class="size-full object-cover">@else{{ mb_substr($user->initials(), 0, 2) }}@endif
                    </span>
                @endif
            </div>
            <div class="min-w-0 flex-1 pt-4">
                <h2 class="break-words font-display text-xl font-semibold">{{ $user->name }}</h2>
                @if(!$blockedByViewer)<livewire:components.activity-status :user-id="$user->id" :key="'profile-activity-'.$user->id" />@endif
                @if($isOwner)
                    <p class="mt-2 break-words text-sm leading-6 text-muted">{{ $user->bio ?? \App\Models\User::DEFAULT_BIO }}</p>
                @elseif($blockedByViewer)<p class="mt-2 text-sm text-muted">Profile activity is hidden while you have this account blocked.</p>
                @else<p class="mt-2 break-words text-sm leading-6 text-muted">{{ $user->bio ?? \App\Models\User::DEFAULT_BIO }}</p>@endif
            </div>
        </div>
        @if(!$isOwner)<div class="mt-5 flex flex-wrap items-center gap-2 sm:justify-end" aria-label="Profile social actions" aria-describedby="social-actions-help">
            @if(!$blockedByViewer)
                @auth
                    <button type="button" wire:click="toggleFollow" wire:loading.attr="disabled" wire:target="toggleFollow" class="flex min-h-11 items-center gap-2 rounded-sm bg-brand px-5 text-sm font-medium text-on-brand disabled:opacity-60"><x-coffee-icon name="follow"/>{{ $isFollowing ? 'Unfollow' : ($followsViewer ? 'Follow back' : 'Follow') }}</button>
                @else
                    <a href="{{ route('login') }}" class="flex min-h-11 items-center gap-2 rounded-sm bg-brand px-5 text-sm font-medium text-on-brand"><x-coffee-icon name="follow"/>Log in to follow</a>
                @endauth
                @auth<a href="{{ route('chat.index', $existingConversationUuid ? ['conversation' => $existingConversationUuid] : ['with' => $user->profile_uuid]) }}" wire:navigate class="flex min-h-11 items-center gap-2 rounded-sm border border-line px-5 text-sm font-medium text-brand hover:bg-soft"><x-coffee-icon name="comment"/>Chat</a>@endauth
            @else
                <p class="mr-auto text-sm text-muted">You blocked this account. Unblock it to view posts or send messages.</p>
            @endif
            <div class="relative" x-data="{ menuOpen: false }" x-on:click.outside="menuOpen = false" x-on:keydown.escape.stop="menuOpen = false; $refs.moreButton.focus()">
                <button type="button" x-ref="moreButton" x-on:click="menuOpen = !menuOpen; if (menuOpen) $nextTick(() => $refs.blockOption.focus())" x-bind:aria-expanded="menuOpen" aria-controls="profile-more-options" aria-label="More profile options" class="flex size-11 items-center justify-center rounded-sm border border-line text-brand hover:bg-soft"><x-coffee-icon name="more"/></button>
                <div id="profile-more-options" x-cloak x-show="menuOpen" class="absolute top-full right-0 z-20 mt-2 w-52 rounded-sm border border-line bg-surface p-2">
                    @auth
                        <button type="button" x-ref="blockOption" wire:click="toggleBlock" wire:loading.attr="disabled" wire:target="toggleBlock" x-on:click="menuOpen = false" class="flex min-h-11 w-full items-center gap-2 px-3 text-left text-sm {{ $blockedByViewer ? 'text-brand' : 'text-red-700 dark:text-red-300' }}"><x-coffee-icon name="block"/>{{ $blockedByViewer ? 'Unblock account' : 'Block account' }}</button>
                    @else
                        <a x-ref="blockOption" href="{{ route('login') }}" class="flex min-h-11 w-full items-center gap-2 px-3 text-left text-sm text-muted"><x-coffee-icon name="block"/>Log in to block</a>
                    @endauth
                </div>
            </div>
        </div>
        <p id="social-actions-help" class="mt-2 text-xs text-muted sm:text-right">{{ $blockedByViewer ? 'Unblock this account to restore profile access.' : 'Start a private conversation.' }}</p>@endif
        @if($isOwner)<p class="sr-only">Tap your photo to change it. JPEG or PNG, up to 10 MB.</p>@endif
        <dl class="mt-6 grid max-w-lg grid-cols-4 gap-5">
            <div><dd class="font-semibold tabular-nums">{{ number_format($storyCount) }}</dd><dt class="mt-1 text-xs text-muted">Stories</dt></div>
            <div><dd class="font-semibold tabular-nums">{{ number_format($placeCount) }}</dd><dt class="mt-1 text-xs text-muted">Places</dt></div>
            <div><dd class="font-semibold tabular-nums">{{ number_format($followerCount) }}</dd><dt class="mt-1 text-xs text-muted">Followers</dt></div>
            <div><dd class="font-semibold tabular-nums">{{ number_format($followingCount) }}</dd><dt class="mt-1 text-xs text-muted">Following</dt></div>
        </dl>
    </section>

    <div role="tablist" aria-label="Profile content" class="mt-6 flex border-b border-line"
        x-on:keydown.right.prevent="tab = ['stories', 'collections', 'saved'][(['stories', 'collections', 'saved'].indexOf(tab) + 1) % 3]; $nextTick(() => $el.querySelector('[aria-selected=true]').focus())"
        x-on:keydown.left.prevent="tab = ['stories', 'collections', 'saved'][(['stories', 'collections', 'saved'].indexOf(tab) + 2) % 3]; $nextTick(() => $el.querySelector('[aria-selected=true]').focus())">
        @foreach(['stories' => 'Posts', 'collections' => 'Collections', 'saved' => 'Saved'] as $key => $label)
            <button id="profile-tab-{{ $key }}" type="button" role="tab" aria-controls="profile-panel-{{ $key }}" x-bind:aria-selected="tab === '{{ $key }}'" x-bind:tabindex="tab === '{{ $key }}' ? 0 : -1" x-on:click="tab = '{{ $key }}'; $wire.set('tab', '{{ $key }}')" x-bind:class="tab === '{{ $key }}' ? 'border-brand text-brand' : 'border-transparent text-muted'" class="min-h-12 flex-1 border-b-2 px-3 text-sm font-medium">{{ $label }}</button>
        @endforeach
    </div>

    <section id="profile-panel-stories" role="tabpanel" aria-labelledby="profile-tab-stories" x-show="tab === 'stories' && $wire.tab === 'stories'" class="mt-4">
            @if($isOwner)<div class="mb-4 flex items-center justify-end gap-2"><label for="profile-post-filter" class="text-xs text-muted">Show</label><select id="profile-post-filter" wire:model.live="postFilter" class="min-h-11 rounded-sm border border-line bg-surface px-3 text-sm"><option value="active">Active posts</option><option value="archived">Archived</option><option value="trash">Trash</option></select></div>@endif
        @if($blockedByViewer)
            <div class="py-14 text-center"><x-coffee-icon name="lock" class="mx-auto size-8 text-brand"/><h2 class="mt-4 font-display text-xl font-semibold">This account is blocked</h2><p class="mt-2 text-sm text-muted">Use the profile menu to unblock this account and restore access to their stories.</p></div>
        @elseif($posts->count())

            <div class="grid grid-cols-3 gap-1" aria-label="Your stories grid">
                @foreach($posts as $post)
                    @php($cover = $post->media->first())
                    <article wire:key="profile-post-{{ $post->id }}" x-data="{ closeStory() { $refs.story.querySelectorAll('video').forEach(video => video.pause()); $refs.story.close(); } }">
                        <button type="button" x-on:click="$refs.story.showModal()" aria-label="Read story: {{ $post->title }}" class="relative block aspect-square w-full overflow-hidden bg-soft text-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                            @if($cover && $cover->kind === 'image')
                                <img src="{{ route('post-media.show', $cover) }}" alt="" loading="lazy" class="size-full object-cover transition duration-200 hover:scale-105 motion-reduce:transition-none">
                            @elseif($cover)
                                <x-video-thumbnail :src="route('post-media.show', $cover)" :title="$post->title"/>
                            @elseif($post->image_path)
                                <img src="{{ asset('images/'.$post->image_path) }}" alt="" loading="lazy" class="size-full object-cover">
                            @else
                                <span class="flex size-full flex-col items-center justify-center gap-2 p-3"><x-coffee-icon name="coffee"/><span class="line-clamp-3 text-xs">{{ $post->title }}</span></span>
                            @endif
                            @if($post->visibility !== 'public')<span class="absolute bottom-1 left-1 rounded-sm bg-surface px-2 py-1 text-[10px] text-ink">{{ $post->visibility === 'followers' ? 'Followers only' : 'Only me' }}</span>@endif
                            @if($post->media->count() > 1)<span class="absolute top-1 right-1 rounded-sm bg-surface p-1 text-brand" aria-label="Multiple photos or videos"><x-coffee-icon name="image" class="size-4"/></span>@endif
                        </button>
                        <dialog x-ref="story" x-on:cancel.prevent="closeStory()" aria-labelledby="story-title-{{ $post->id }}" class="m-auto max-h-[90dvh] w-[min(94vw,640px)] overflow-y-auto rounded-lg border border-line bg-surface p-0 text-ink backdrop:bg-black/60">
                            <div class="flex items-center justify-between gap-4 p-4"><p class="text-sm font-semibold">{{ $user->name }}</p><div class="flex items-center gap-1">@if($isOwner)<x-post-owner-menu :post="$post"/>@endif<button type="button" x-on:click="closeStory()" aria-label="Close story" title="Close story" class="group flex min-h-11 items-center justify-center rounded-sm border border-transparent px-2 text-brand transition-all duration-200 hover:border-line hover:bg-soft hover:text-ink focus-visible:border-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand motion-reduce:transition-none"><x-coffee-icon name="close" class="size-5 transition-transform duration-200 group-hover:rotate-90 motion-reduce:transition-none"/><span class="max-w-0 overflow-hidden whitespace-nowrap text-sm font-medium opacity-0 transition-all duration-200 group-hover:ml-2 group-hover:max-w-16 group-hover:opacity-100 group-focus-visible:ml-2 group-focus-visible:max-w-16 group-focus-visible:opacity-100 motion-reduce:transition-none">Close</span></button></div></div>
                            @if($post->media->count())
                                <x-post-gallery :media="$post->media->map(fn ($item) => ['kind' => $item->kind, 'mime_type' => $item->mime_type, 'url' => route('post-media.show', $item)])->all()" :title="$post->title"/>
                            @elseif($post->image_path)
                                <img src="{{ asset('images/'.$post->image_path) }}" alt="{{ $post->image_alt ?: $post->title }}" loading="lazy" class="w-full">
                            @endif
                            <div class="p-5"><h2 id="story-title-{{ $post->id }}" class="break-words font-display text-xl font-semibold">{{ $post->title }}</h2><p class="mt-2 text-sm text-muted">{{ $post->cafe_name }} &middot; {{ $post->city }}</p><p class="mt-4 whitespace-pre-line break-words text-sm leading-7">{{ $post->body }}</p><x-post-amenities :amenities="$post->amenities ?? []" :amenity-media="$post->amenityMedia->map(fn ($item) => ['amenity_key' => $item->amenity_key, 'url' => route('post-media.show', $item)])->all()"/><p class="mt-4 text-xs text-muted">{{ $post->created_at->format('M j, Y') }} &middot; {{ match ($post->visibility) { 'private' => 'Only me', 'followers' => 'Followers only', default => 'Public' } }}</p></div>
                            @if(!$post->trashed())<div class="flex items-center justify-between border-t border-line px-4 py-2"><button type="button" x-on:click="closeStory(); openProfileShare(@js($post->postUrl()), @js($post->title), 'post')" class="flex min-h-11 items-center gap-2 px-2 text-sm text-brand"><x-coffee-icon name="share"/>Share post</button><a href="{{ $post->postUrl() }}" wire:navigate class="inline-flex min-h-11 items-center gap-2 px-2 py-3 text-sm text-muted hover:text-brand">Go to post<x-coffee-icon name="arrow" class="size-4"/></a></div>@endif
                        </dialog>
                    </article>
                @endforeach
            </div>
            <div class="mt-6">{{ $posts->links() }}</div>
        @else
            <div class="py-14 text-center"><x-coffee-icon name="camera" class="mx-auto size-8 text-brand"/><h2 class="mt-4 font-display text-xl font-semibold">{{ $isOwner ? 'Your coffee story starts here' : 'No public stories yet' }}</h2><p class="mt-2 text-sm text-muted">Your photos and videos will appear in a three-column grid.</p>@if($isOwner)<a href="{{ route('coffee.post') }}" wire:navigate class="mt-5 inline-flex min-h-11 items-center rounded-sm bg-brand px-5 text-sm font-medium text-on-brand">Share your first story</a>@endif</div>
        @endif
    </section>
    <section id="profile-panel-collections" role="tabpanel" aria-labelledby="profile-tab-collections" x-cloak x-show="tab === 'collections'" class="py-14 text-center"><x-coffee-icon name="image" class="mx-auto size-8 text-brand"/><h2 class="mt-4 font-display text-xl font-semibold">Your coffee collections</h2><p class="mt-2 text-sm text-muted">Collections are coming soon.</p></section>
    <section id="profile-panel-saved" role="tabpanel" aria-labelledby="profile-tab-saved" x-cloak x-show="tab === 'saved' && $wire.tab === 'saved'" class="mt-4">
        @if($isOwner && $posts->count())
            <div class="grid grid-cols-3 gap-1" aria-label="Your saved stories">
                @foreach($posts as $post)
                    @php($cover = $post->media->first())
                    <a wire:key="saved-post-{{ $post->id }}" href="{{ $post->postUrl() }}" wire:navigate aria-label="Open saved story: {{ $post->title }}" x-data="{ coverFailed: false }" class="relative flex aspect-square items-center justify-center overflow-hidden bg-soft text-brand">
                        @if($cover && $cover->kind === 'image')
                            <img src="{{ route('post-media.show', $cover) }}" alt="" loading="lazy" x-show="!coverFailed" x-on:error="coverFailed = true" class="size-full object-cover">
                            <span x-cloak x-show="coverFailed" class="flex size-full flex-col items-center justify-center gap-2 p-3 text-center"><x-coffee-icon name="image" class="size-7"/><span class="line-clamp-3 text-xs">{{ $post->title }}</span></span>
                        @elseif($cover)
                            <x-video-thumbnail :src="route('post-media.show', $cover)" :title="$post->title"/>
                        @elseif(!$cover && $post->image_path)
                            <img src="{{ asset('images/'.$post->image_path) }}" alt="" loading="lazy" x-show="!coverFailed" x-on:error="coverFailed = true" class="size-full object-cover">
                            <span x-cloak x-show="coverFailed" class="flex size-full flex-col items-center justify-center gap-2 p-3 text-center"><x-coffee-icon name="image" class="size-7"/><span class="line-clamp-3 text-xs">{{ $post->title }}</span></span>
                        @else
                            <span class="p-3 text-center text-sm">{{ $post->title }}</span>
                        @endif
                        <span class="absolute right-1 bottom-1 rounded-sm bg-surface p-1"><x-coffee-icon name="bookmark" class="size-4 fill-current"/></span>
                    </a>
                @endforeach
            </div>
            <div class="mt-6">{{ $posts->links() }}</div>
        @else
            <div class="py-14 text-center"><x-coffee-icon name="bookmark" class="mx-auto size-8 text-brand"/><h2 class="mt-4 font-display text-xl font-semibold">A place for your next visit</h2><p class="mt-2 text-sm text-muted">Save a story from Discover to keep it here.</p><a href="{{ route('home') }}" wire:navigate class="mt-5 inline-flex min-h-11 items-center rounded-sm bg-brand px-5 text-sm font-medium text-on-brand">Discover stories</a></div>
        @endif
    </section>


    @if($isOwner)
                        <dialog x-ref="editPost" wire:ignore.self x-on:cancel.prevent="if (!postMediaUploading) $wire.cancelEditPost()" aria-labelledby="edit-post-title" class="m-auto max-h-[calc(100dvh-1.5rem)] w-[calc(100vw-1.5rem)] max-w-[640px] overflow-x-hidden overflow-y-auto overscroll-contain rounded-md border border-line bg-surface p-0 text-ink backdrop:bg-black/60">
            <form wire:submit="savePostEdits" class="w-full min-w-0 p-4 sm:p-6">
                <div class="flex min-w-0 items-start justify-between gap-3"><div class="min-w-0"><h2 id="edit-post-title" class="break-words font-display text-xl font-semibold leading-tight sm:text-2xl">Edit post</h2><p class="mt-1 text-sm text-muted">Update your café story and add photos or videos.</p></div><button type="button" wire:click="cancelEditPost" x-bind:disabled="postMediaUploading" aria-label="Close edit post" title="Close edit post" class="group flex min-h-11 shrink-0 items-center justify-center rounded-sm border border-transparent px-2 text-brand transition-all duration-200 hover:border-line hover:bg-soft hover:text-ink focus-visible:border-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand disabled:opacity-50 motion-reduce:transition-none"><x-coffee-icon name="close" class="size-5 transition-transform duration-200 group-hover:rotate-90 motion-reduce:transition-none"/><span class="max-w-0 overflow-hidden whitespace-nowrap text-sm font-medium opacity-0 transition-all duration-200 group-hover:ml-2 group-hover:max-w-16 group-hover:opacity-100 group-focus-visible:ml-2 group-focus-visible:max-w-16 group-focus-visible:opacity-100 motion-reduce:transition-none">Close</span></button></div>
                @if($editingPost)
                    <div class="mt-5 grid min-w-0 grid-cols-1 gap-4 lg:grid-cols-2">
                        <div class="min-w-0 lg:col-span-2"><label for="edit-post-title-input" class="text-sm font-medium">Post title</label><input id="edit-post-title-input" wire:model="editTitle" maxlength="160" class="mt-1 min-h-11 w-full min-w-0 max-w-full rounded-sm border border-line bg-page px-3 text-sm">@error('editTitle')<p class="mt-1 break-words text-xs text-red-700">{{ $message }}</p>@enderror</div>
                        <div class="min-w-0"><label for="edit-post-cafe" class="text-sm font-medium">Café</label><input id="edit-post-cafe" wire:model="editCafeName" maxlength="255" class="mt-1 min-h-11 w-full min-w-0 max-w-full rounded-sm border border-line bg-page px-3 text-sm">@error('editCafeName')<p class="mt-1 break-words text-xs text-red-700">{{ $message }}</p>@enderror</div>
                        <div class="min-w-0"><label for="edit-post-city" class="text-sm font-medium">City or area</label><input id="edit-post-city" wire:model="editCity" maxlength="255" class="mt-1 min-h-11 w-full min-w-0 max-w-full rounded-sm border border-line bg-page px-3 text-sm">@error('editCity')<p class="mt-1 break-words text-xs text-red-700">{{ $message }}</p>@enderror</div>
                        <div class="min-w-0 lg:col-span-2"><label for="edit-post-body" class="text-sm font-medium">Story</label><textarea id="edit-post-body" wire:model="editBody" rows="4" maxlength="3000" class="mt-1 min-h-28 w-full min-w-0 max-w-full resize-y rounded-sm border border-line bg-page p-3 text-sm leading-6"></textarea>@error('editBody')<p class="mt-1 break-words text-xs text-red-700">{{ $message }}</p>@enderror</div>
                        <div class="min-w-0 lg:col-span-2"><label for="edit-post-visibility" class="text-sm font-medium">Who can see this?</label><select id="edit-post-visibility" wire:model="editVisibility" class="mt-1 min-h-11 w-full min-w-0 max-w-full rounded-sm border border-line bg-page px-3 text-sm"><option value="public">Public</option><option value="followers">Followers only</option><option value="private">Only me</option></select>@error('editVisibility')<p class="mt-1 break-words text-xs text-red-700">{{ $message }}</p>@enderror</div>
                        <div class="min-w-0 lg:col-span-2"><p class="text-sm font-medium">Current photos and videos</p><div class="mt-2 flex gap-2 overflow-x-auto">@forelse($editingPost->media as $media)<div @class(['relative flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-sm bg-soft', 'opacity-50' => in_array($media->id, $removedMediaIds, true)])>@if($media->kind === 'image')<img src="{{ route('post-media.show', $media) }}" alt="Current post photo" class="size-full object-cover">@else<x-coffee-icon name="video" class="size-7 text-brand"/>@endif @if(in_array($media->id, $removedMediaIds, true))<span class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-1 bg-ink/90 px-1 py-1 text-[9px] leading-tight text-surface"><x-lucide-trash-2 class="size-3" aria-hidden="true"/><span>Marked</span></span>@endif<button type="button" wire:click="toggleRemoveExistingMedia({{ $media->id }})" aria-label="{{ in_array($media->id, $removedMediaIds, true) ? 'Undo removal of' : 'Remove' }} existing media {{ $loop->iteration }}" title="{{ in_array($media->id, $removedMediaIds, true) ? 'Undo remove' : 'Remove photo' }}" @class(['absolute right-1 top-1 flex size-7 items-center justify-center rounded-full shadow-sm transition-transform hover:scale-110 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand', 'bg-surface text-brand' => in_array($media->id, $removedMediaIds, true), 'bg-red-700 text-white' => ! in_array($media->id, $removedMediaIds, true)])>@if(in_array($media->id, $removedMediaIds, true))<x-lucide-undo-2 class="size-4" aria-hidden="true"/>@else<x-lucide-x class="size-4" aria-hidden="true"/>@endif</button></div>@empty<p class="text-xs text-muted">No media attached.</p>@endforelse</div></div>
                        <div class="min-w-0 lg:col-span-2"><label for="edit-post-uploads" class="text-sm font-medium">Add photos or videos</label><input id="edit-post-uploads" type="file" x-on:change="uploadPostMedia($event)" x-bind:disabled="postMediaUploading" multiple accept=".jpg,.jpeg,.png,.mp4,.mov,image/jpeg,image/png,video/mp4,video/quicktime" class="mt-2 block min-h-11 w-full min-w-0 max-w-full text-sm text-muted file:mr-3 file:min-h-11 file:rounded-sm file:border-0 file:bg-soft file:px-4 file:text-sm file:font-medium file:text-brand">@error('editUploads')<p class="mt-1 break-words text-xs text-red-700">{{ $message }}</p>@enderror @error('editUploads.*')<p class="mt-1 break-words text-xs text-red-700">{{ $message }}</p>@enderror<p class="mt-1 text-xs leading-5 text-muted">JPEG or PNG up to 10 MB; MP4 or MOV up to 25 MB, 60 seconds.</p><div x-cloak x-show="postMediaUploading" role="status" aria-live="polite" class="mt-3 flex items-center gap-2 text-xs text-muted"><span class="size-4 animate-spin rounded-full border-2 border-brand/25 border-t-brand motion-reduce:animate-none" aria-hidden="true"></span><span>Uploading media... <span x-text="Math.round(postMediaProgress) + '%'">0%</span></span></div><div x-cloak x-show="editMediaPreviews.length" class="mt-4"><p class="text-xs font-medium text-muted">Ready to add</p><div class="mt-2 flex gap-2 overflow-x-auto"><template x-for="(preview, index) in editMediaPreviews" :key="preview.url"><div class="relative flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-sm bg-soft"><img x-show="preview.kind === 'image'" x-bind:src="preview.url" alt="New photo preview" class="size-full object-cover"><video x-show="preview.kind === 'video'" x-bind:src="preview.url" muted playsinline preload="metadata" class="size-full object-cover"></video><span class="absolute inset-x-0 bottom-0 bg-ink/80 px-1 py-0.5 text-center text-[9px] text-surface" x-text="preview.kind === 'image' ? 'Photo' : 'Video'"></span></div></template></div></div></div>
                    </div>
                    <div class="sticky bottom-0 mt-6 flex justify-end gap-2 border-t border-line bg-surface/95 py-3"><button type="button" wire:click="cancelEditPost" x-bind:disabled="postMediaUploading" class="min-h-11 rounded-sm px-4 text-sm text-muted disabled:opacity-50">Cancel</button><button type="submit" wire:loading.attr="disabled" wire:target="savePostEdits" aria-label="Save changes" title="Save changes" x-bind:disabled="postMediaUploading" class="group inline-flex min-h-11 items-center justify-center gap-2 overflow-hidden rounded-sm bg-brand px-3 text-sm font-semibold text-on-brand transition-all duration-200 disabled:opacity-60 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand motion-reduce:transition-none"><x-coffee-icon name="save" class="size-5 shrink-0" aria-hidden="true"/><span class="max-w-0 overflow-hidden whitespace-nowrap opacity-0 transition-all duration-200 group-hover:max-w-28 group-hover:opacity-100 group-focus-visible:max-w-28 group-focus-visible:opacity-100 motion-reduce:transition-none"><span wire:loading.remove wire:target="savePostEdits">Save changes</span><span wire:loading wire:target="savePostEdits">Saving...</span></span></button></div>
                @endif
            </form>
        </dialog>
    @endif

    <x-share-dialog/>


    <x-image-crop-modal/>

    <x-photo-toast/>



</div>
