<div class="mx-auto max-w-[800px]" x-data="{ tab: 'stories', editingBio: false, photoUploading: false }" x-on:bio-saved.window="editingBio = false" x-on:profile-photo-saved.window="photoUploading = false">
    <div class="mb-6 flex items-center justify-between gap-4">
        <h1 class="font-display text-2xl font-semibold">My Profile</h1>
        <a href="{{ route('settings.profile') }}" wire:navigate aria-label="Profile settings" class="flex size-11 items-center justify-center rounded-sm border border-line text-brand hover:bg-soft"><x-coffee-icon name="settings"/></a>
    </div>

    <section aria-label="Your coffee profile" class="rounded-md border border-line bg-surface p-5 sm:p-7">
        <div class="flex items-center gap-4">
            <div class="shrink-0">
                <button type="button" x-on:click="$refs.profilePhoto.click()" x-bind:disabled="photoUploading" class="group relative flex size-16 items-center justify-center overflow-hidden rounded-full bg-soft font-display text-xl font-semibold text-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand disabled:opacity-50" aria-label="{{ $user->profile_photo ? 'Change profile photo' : 'Add profile photo' }}">
                    @if($user->profile_photo)
                        <img src="{{ route('profile.photo', ['v' => substr(hash('sha256', $user->profile_photo['path']), 0, 12)]) }}" alt="Your profile photo" class="size-full object-cover">
                    @else
                        <span aria-hidden="true">{{ mb_substr($user->initials(), 0, 2) }}</span>
                    @endif
                    <span class="absolute inset-0 hidden items-center justify-center bg-brand text-on-brand group-hover:flex group-focus-visible:flex"><x-coffee-icon name="camera"/></span>
                    <span class="absolute right-0 bottom-0 rounded-full bg-brand p-1 text-on-brand group-hover:hidden"><x-coffee-icon name="camera" class="size-3"/></span>
                </button>
                <input x-ref="profilePhoto" wire:key="profile-photo-{{ $user->profile_photo['path'] ?? 'new' }}" type="file" wire:model="photo" accept=".jpg,.jpeg,.png,image/jpeg,image/png" hidden aria-label="Upload profile photo" x-on:livewire-upload-start="photoUploading = true" x-on:livewire-upload-finish="photoUploading = false" x-on:livewire-upload-error="photoUploading = false" x-on:livewire-upload-cancel="photoUploading = false">
            </div>
            <div class="min-w-0">
                <h2 class="break-words font-display text-xl font-semibold">{{ $user->name }}</h2>
                <button type="button" x-show="!editingBio" x-on:click="editingBio = true; $nextTick(() => $refs.bioInput.focus())" class="mt-2 block text-left text-sm leading-6 text-muted hover:text-brand" aria-label="Edit profile bio">
                    <span class="break-words">{{ $user->bio ?? \App\Models\User::DEFAULT_BIO }}</span>
                    <span class="ml-1 inline-flex text-xs text-brand underline underline-offset-4">Edit</span>
                </button>
            </div>
        </div>
        <p x-cloak x-show="photoUploading" role="status" class="mt-3 text-xs text-brand">Uploading profile photo...</p>
        @error('photo')<p role="alert" class="mt-3 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
        <p class="mt-3 text-xs text-muted">Tap your photo to change it. JPEG or PNG, up to 10 MB.</p>
        <form wire:submit="saveBio" x-cloak x-show="editingBio" class="mt-4">
            <label for="profile-bio" class="block text-sm font-medium">Your profile bio</label>
            <textarea id="profile-bio" x-ref="bioInput" wire:model="bio" maxlength="160" rows="3" class="mt-2 w-full rounded-sm border border-line bg-surface p-3 text-sm text-ink focus:border-brand focus:outline-2 focus:outline-brand" aria-describedby="bio-help"></textarea>
            <p id="bio-help" class="mt-1 text-xs text-muted">A little about your coffee journey. Up to 160 characters.</p>
            @error('bio')<p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
            <div class="mt-3 flex gap-3"><button type="submit" wire:loading.attr="disabled" wire:target="saveBio" class="min-h-11 rounded-sm bg-brand px-4 text-sm text-on-brand disabled:opacity-50">Save bio</button><button type="button" x-on:click="editingBio = false; $wire.set('bio', @js($user->bio ?? \App\Models\User::DEFAULT_BIO), false)" class="min-h-11 px-3 text-sm text-muted">Cancel</button></div>
        </form>
        <dl class="mt-6 grid max-w-sm grid-cols-3 gap-5">
            <div><dd class="font-semibold tabular-nums">{{ number_format($storyCount) }}</dd><dt class="mt-1 text-xs text-muted">Stories</dt></div>
            <div><dd class="font-semibold tabular-nums">{{ number_format($placeCount) }}</dd><dt class="mt-1 text-xs text-muted">Places</dt></div>
            <div><dd class="font-semibold" aria-label="Following is not available yet">&mdash;</dd><dt class="mt-1 text-xs text-muted">Following</dt></div>
        </dl>
    </section>

    <div role="tablist" aria-label="Profile content" class="mt-6 flex border-b border-line"
        x-on:keydown.right.prevent="tab = ['stories', 'collections', 'saved'][(['stories', 'collections', 'saved'].indexOf(tab) + 1) % 3]; $nextTick(() => $el.querySelector('[aria-selected=true]').focus())"
        x-on:keydown.left.prevent="tab = ['stories', 'collections', 'saved'][(['stories', 'collections', 'saved'].indexOf(tab) + 2) % 3]; $nextTick(() => $el.querySelector('[aria-selected=true]').focus())">
        @foreach(['stories' => 'Stories', 'collections' => 'Collections', 'saved' => 'Saved'] as $key => $label)
            <button id="profile-tab-{{ $key }}" type="button" role="tab" aria-controls="profile-panel-{{ $key }}" x-bind:aria-selected="tab === '{{ $key }}'" x-bind:tabindex="tab === '{{ $key }}' ? 0 : -1" x-on:click="tab = '{{ $key }}'" x-bind:class="tab === '{{ $key }}' ? 'border-brand text-brand' : 'border-transparent text-muted'" class="min-h-12 flex-1 border-b-2 px-3 text-sm font-medium">{{ $label }}</button>
        @endforeach
    </div>

    <section id="profile-panel-stories" role="tabpanel" aria-labelledby="profile-tab-stories" x-show="tab === 'stories'" class="mt-4">
        @if($posts->count())
            <div class="grid grid-cols-3 gap-1" aria-label="Your stories grid">
                @foreach($posts as $post)
                    @php($cover = $post->media->first())
                    <article wire:key="profile-post-{{ $post->id }}" x-data="{ closeStory() { $refs.story.querySelectorAll('video').forEach(video => video.pause()); $refs.story.close(); } }">
                        <button type="button" x-on:click="$refs.story.showModal()" aria-label="Read story: {{ $post->title }}" class="relative block aspect-square w-full overflow-hidden bg-soft text-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                            @if($cover && $cover->kind === 'image')
                                <img src="{{ route('post-media.show', $cover) }}" alt="" loading="lazy" class="size-full object-cover transition duration-200 hover:scale-105 motion-reduce:transition-none">
                            @elseif($cover)
                                <span class="flex size-full flex-col items-center justify-center gap-2 p-2"><x-coffee-icon name="video" class="size-7"/><span class="line-clamp-2 text-xs">{{ $post->title }}</span></span>
                            @elseif($post->image_path)
                                <img src="{{ asset('images/'.$post->image_path) }}" alt="" loading="lazy" class="size-full object-cover">
                            @else
                                <span class="flex size-full flex-col items-center justify-center gap-2 p-3"><x-coffee-icon name="coffee"/><span class="line-clamp-3 text-xs">{{ $post->title }}</span></span>
                            @endif
                            @if($post->visibility === 'private')<span class="absolute bottom-1 left-1 rounded-sm bg-surface px-2 py-1 text-[10px] text-ink">Only me</span>@endif
                            @if($post->media->count() > 1)<span class="absolute top-1 right-1 rounded-sm bg-surface p-1 text-brand" aria-label="Multiple photos or videos"><x-coffee-icon name="image" class="size-4"/></span>@endif
                        </button>
                        <dialog x-ref="story" x-on:cancel.prevent="closeStory()" aria-labelledby="story-title-{{ $post->id }}" class="m-auto max-h-[90dvh] w-[min(94vw,640px)] overflow-y-auto rounded-md border border-line bg-surface p-0 text-ink backdrop:bg-black/60">
                            <div class="flex items-center justify-between gap-4 p-4"><p class="text-sm font-semibold">{{ $user->name }}</p><button type="button" x-on:click="closeStory()" class="min-h-11 px-2 text-sm text-brand">Close story</button></div>
                            @if($post->media->count())
                                <x-post-gallery :media="$post->media->map(fn ($item) => ['kind' => $item->kind, 'mime_type' => $item->mime_type, 'url' => route('post-media.show', $item)])->all()" :title="$post->title"/>
                            @elseif($post->image_path)
                                <img src="{{ asset('images/'.$post->image_path) }}" alt="{{ $post->image_alt ?: $post->title }}" loading="lazy" class="w-full">
                            @endif
                            <div class="p-5"><h2 id="story-title-{{ $post->id }}" class="break-words font-display text-xl font-semibold">{{ $post->title }}</h2><p class="mt-2 text-sm text-muted">{{ $post->cafe_name }} &middot; {{ $post->city }}</p><p class="mt-4 whitespace-pre-line break-words text-sm leading-7">{{ $post->body }}</p><p class="mt-4 text-xs text-muted">{{ $post->created_at->format('M j, Y') }} &middot; {{ $post->visibility === 'private' ? 'Only me' : 'Public' }}</p></div>
                        </dialog>
                    </article>
                @endforeach
            </div>
            <div class="mt-6">{{ $posts->links() }}</div>
        @else
            <div class="py-14 text-center"><x-coffee-icon name="camera" class="mx-auto size-8 text-brand"/><h2 class="mt-4 font-display text-xl font-semibold">Your coffee story starts here</h2><p class="mt-2 text-sm text-muted">Your photos and videos will appear in a three-column grid.</p><a href="{{ route('coffee.post') }}" wire:navigate class="mt-5 inline-flex min-h-11 items-center rounded-sm bg-brand px-5 text-sm font-medium text-on-brand">Share your first story</a></div>
        @endif
    </section>
    <section id="profile-panel-collections" role="tabpanel" aria-labelledby="profile-tab-collections" x-cloak x-show="tab === 'collections'" class="py-14 text-center"><x-coffee-icon name="image" class="mx-auto size-8 text-brand"/><h2 class="mt-4 font-display text-xl font-semibold">Your coffee collections</h2><p class="mt-2 text-sm text-muted">Organizing stories into collections is coming soon.</p></section>
    <section id="profile-panel-saved" role="tabpanel" aria-labelledby="profile-tab-saved" x-cloak x-show="tab === 'saved'" class="py-14 text-center"><x-coffee-icon name="bookmark" class="mx-auto size-8 text-brand"/><h2 class="mt-4 font-display text-xl font-semibold">A place for your next visit</h2><p class="mt-2 text-sm text-muted">Saving friends' recommendations is coming soon.</p></section>
</div>
