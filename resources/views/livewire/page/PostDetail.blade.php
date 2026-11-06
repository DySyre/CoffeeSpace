<div class="mx-auto max-w-[760px]" x-data="profileImageCropper($wire)" x-on:comment-report-result.window="showToast('success', $event.detail.message, $event.detail.title)">
    <article class="overflow-hidden rounded-lg border border-line bg-surface">
        <header class="flex items-center justify-between gap-4 p-4"><a href="{{ $post->user->profileUrl() }}" wire:navigate class="flex items-center gap-3"><x-user-avatar :user="$post->user"/><span class="text-sm font-semibold">{{ $post->user->name }}</span></a><button type="button" x-on:click="openProfileShare(@js($post->postUrl()), @js($post->title), 'post')" class="flex min-h-11 items-center gap-2 px-3 text-sm text-brand"><x-coffee-icon name="share"/>Share post</button></header>
        @if($post->media->count())
            <x-post-gallery :media="$post->media->map(fn ($item) => ['kind' => $item->kind, 'mime_type' => $item->mime_type, 'url' => route('post-media.show', $item)])->all()" :title="$post->title"/>
        @elseif($post->image_path)
            <img src="{{ asset('images/'.$post->image_path) }}" alt="{{ $post->image_alt ?: $post->title }}" class="h-auto w-full">
        @endif
        <div class="p-5">
            @if($post->archived_at)<p class="mb-3 text-xs font-medium text-brand">Archived - visible only to you.</p>@endif
            <h1 class="font-display text-2xl font-semibold">{{ $post->title }}</h1>
            <p class="mt-2 text-sm text-muted">{{ $post->cafe_name }} &middot; {{ $post->city }}</p>
            <p class="mt-4 whitespace-pre-line break-words text-sm leading-7">{{ $post->body }}</p>
            <x-post-amenities :amenities="$post->amenities ?? []" :amenity-media="$post->amenityMedia->map(fn ($item) => ['amenity_key' => $item->amenity_key, 'url' => route('post-media.show', $item)])->all()"/>
            <p class="mt-4 text-xs text-muted">{{ $post->created_at->format('M j, Y') }} &middot; {{ match ($post->visibility) { 'private' => 'Only me', 'followers' => 'Followers only', default => 'Public' } }}</p>
        </div>
        @if($post->visibility !== 'private')<livewire:posts.post-comments :post-id="$post->id" :comment-count="$post->comments_count"/>@endif
    </article>
    <x-share-dialog/>
    <x-photo-toast/>
</div>
