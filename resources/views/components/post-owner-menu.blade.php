@props(['post'])
<div x-data="{ postMenu: false }" class="relative" x-on:click.outside="postMenu = false" x-on:keydown.escape.stop="postMenu = false; $refs.postMenuButton.focus()">
    <button type="button" x-ref="postMenuButton" x-on:click="postMenu = !postMenu" x-bind:aria-expanded="postMenu" aria-label="Post settings" class="flex size-11 items-center justify-center rounded-sm text-brand hover:bg-soft"><x-coffee-icon name="more"/></button>
    <div x-cloak x-show="postMenu" class="absolute top-full right-0 z-20 w-60 rounded-sm border border-line bg-surface p-2">
        @if(!$post->trashed())
            <p class="px-3 py-2 text-xs font-semibold text-muted">Who can see this post?</p>
            @foreach(['public' => ['Public', 'globe'], 'followers' => ['Followers only', 'users'], 'private' => ['Only me', 'lock']] as $audience => [$label, $icon])
                <button type="button" wire:click="updatePostAudience({{ $post->id }}, '{{ $audience }}')" wire:loading.attr="disabled" aria-pressed="{{ $post->visibility === $audience ? 'true' : 'false' }}" class="flex min-h-11 w-full items-center gap-2 rounded-sm px-3 text-left text-sm text-ink hover:bg-soft disabled:opacity-50"><x-coffee-icon :name="$icon" class="size-4"/>{{ $label }}@if($post->visibility === $audience)<x-coffee-icon name="check" class="ml-auto size-4 text-brand"/>@endif</button>
            @endforeach
            <div class="my-1 border-t border-line"></div>
            @if($post->archived_at)
                <button type="button" wire:click="restorePost({{ $post->id }})" class="flex min-h-11 w-full items-center gap-2 px-3 text-left text-sm text-brand"><x-coffee-icon name="restore" class="size-4"/>Restore to profile</button>
            @else
                <button type="button" wire:click="archivePost({{ $post->id }})" class="flex min-h-11 w-full items-center gap-2 px-3 text-left text-sm text-brand"><x-coffee-icon name="archive" class="size-4"/>Archive post</button>
            @endif
            <button type="button" wire:click="deletePost({{ $post->id }})" wire:confirm="Move this post to Trash? You can restore it later. Its files will be preserved." class="flex min-h-11 w-full items-center gap-2 px-3 text-left text-sm text-red-700 dark:text-red-300"><x-coffee-icon name="trash" class="size-4"/>Delete post</button>
        @else
            <button type="button" wire:click="restorePost({{ $post->id }})" class="flex min-h-11 w-full items-center gap-2 px-3 text-left text-sm text-brand"><x-coffee-icon name="restore" class="size-4"/>Restore post</button>
        @endif
    </div>
</div>
