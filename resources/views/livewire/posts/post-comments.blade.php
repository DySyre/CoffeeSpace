<section class="border-t border-line px-5 py-3" aria-label="Post comments">
    <button type="button" wire:click="toggle" aria-expanded="{{ $expanded ? 'true' : 'false' }}" aria-controls="comments-{{ $postId }}" class="flex min-h-11 items-center gap-2 text-sm text-muted hover:text-brand"><x-coffee-icon name="comment"/>Comments <span>({{ $commentCount }})</span></button>
    @if($expanded)
        <div id="comments-{{ $postId }}" class="flex flex-col gap-4 pt-3">
            @auth
                <form wire:submit="addComment" class="flex flex-col gap-2">
                    <label for="comment-body-{{ $postId }}" class="sr-only">Your comment</label>
                    <textarea id="comment-body-{{ $postId }}" wire:model="body" maxlength="1000" rows="2" required placeholder="Join the conversation…" class="w-full resize-y rounded-sm border border-line bg-page p-3 text-sm" @error('body') aria-invalid="true" aria-describedby="comment-error-{{ $postId }}" @enderror></textarea>
                    @error('body')<p id="comment-error-{{ $postId }}" role="alert" class="text-xs text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
                    <button type="submit" wire:loading.attr="disabled" wire:target="addComment" class="self-end rounded-sm bg-brand px-4 py-2 text-xs text-on-brand disabled:opacity-50"><span wire:loading.remove wire:target="addComment">Post comment</span><span wire:loading wire:target="addComment">Posting…</span></button>
                </form>
            @else
                <p class="text-xs text-muted"><a href="{{ route('login') }}" class="text-brand underline">Log in</a> to leave a comment.</p>
            @endauth
            <div aria-live="polite" class="flex flex-col gap-4">
                @forelse($comments as $comment)
                    <div wire:key="comment-{{ $comment->id }}" class="flex gap-3"><span class="avatar size-8 text-xs">{{ $comment->user->initials() }}</span><div class="min-w-0 flex-1"><div class="flex flex-wrap items-center gap-2"><span class="text-xs font-semibold">{{ $comment->user->name }}</span><time datetime="{{ $comment->created_at->toIso8601String() }}" class="text-xs text-muted">{{ $comment->created_at->diffForHumans() }}</time></div><p class="mt-1 whitespace-pre-wrap text-sm leading-6 wrap-anywhere">{{ $comment->body }}</p></div></div>
                @empty
                    <p class="py-2 text-xs text-muted">No comments yet. Start the conversation.</p>
                @endforelse
            </div>
            @if($commentCount > $comments->count())<button type="button" wire:click="loadMore" wire:loading.attr="disabled" class="min-h-10 self-start text-xs text-brand">Load more comments</button>@endif
        </div>
    @endif
</section>
