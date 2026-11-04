<section class="border-t border-line px-5 py-3" aria-label="Post comments">
    <button type="button" wire:click="toggle" aria-expanded="{{ $expanded ? 'true' : 'false' }}" aria-controls="comments-{{ $postId }}" class="flex min-h-11 items-center gap-2 text-sm text-muted hover:text-brand"><x-coffee-icon name="comment"/>Comments <span>({{ $commentCount }})</span></button>
    @if($expanded)
        <div id="comments-{{ $postId }}" class="flex flex-col gap-4 pt-3">
            @auth
                @if(auth()->user()->hasVerifiedEmail())
                <form wire:submit="addComment" class="flex flex-col gap-2">
                    <label for="comment-body-{{ $postId }}" class="sr-only">Your comment</label>
                    <textarea id="comment-body-{{ $postId }}" wire:model="body" maxlength="1000" rows="2" required placeholder="Join the conversation…" class="w-full resize-y rounded-sm border border-line bg-page p-3 text-sm" @error('body') aria-invalid="true" aria-describedby="comment-error-{{ $postId }}" @enderror></textarea>
                    @error('body')<p id="comment-error-{{ $postId }}" role="alert" class="text-xs text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
                    <button type="submit" wire:loading.attr="disabled" wire:target="addComment" class="self-end rounded-sm bg-brand px-4 py-2 text-xs text-on-brand disabled:opacity-50"><span wire:loading.remove wire:target="addComment">Post comment</span><span wire:loading wire:target="addComment">Posting…</span></button>
                </form>
                @else
                    <p class="text-xs text-muted"><a href="{{ route('verification.notice') }}" class="text-brand underline">Verify your email</a> to leave a comment.</p>
                @endif
            @else
                <p class="text-xs text-muted"><a href="{{ route('login') }}" class="text-brand underline">Log in</a> to leave a comment.</p>
            @endauth
            <div aria-live="polite" class="flex flex-col gap-4">
                @forelse($comments as $comment)
                    <div wire:key="comment-{{ $comment->id }}" class="flex gap-3">
                        <x-user-avatar :user="$comment->user"/>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2"><span class="text-xs font-semibold">{{ $comment->user->name }}</span><time datetime="{{ $comment->created_at->toIso8601String() }}" class="text-xs text-muted">{{ $comment->created_at->diffForHumans() }}</time></div>
                            <p class="mt-1 whitespace-pre-wrap text-sm leading-6 wrap-anywhere">{{ $comment->body }}</p>

                            @auth
                                @if(auth()->user()->hasVerifiedEmail())
                                    <button type="button" wire:click="startReply({{ $comment->id }})" class="mt-2 min-h-11 text-xs font-medium text-brand underline underline-offset-4">Reply</button>
                                @else
                                    <a href="{{ route('verification.notice') }}" class="mt-2 inline-block min-h-11 py-3 text-xs text-brand underline underline-offset-4">Verify email to reply</a>
                                @endif
                            @else
                                <a href="{{ route('login') }}" class="mt-2 inline-block min-h-11 py-3 text-xs text-brand underline underline-offset-4">Log in to reply</a>
                            @endauth

                            @if($replyToCommentId === $comment->id)
                                <form wire:submit="reply" class="mt-2 flex flex-col gap-2">
                                    <p class="text-xs text-muted">Replying to {{ $comment->user->name }}</p>
                                    <label for="reply-body-{{ $comment->id }}" class="sr-only">Your reply</label>
                                    <textarea id="reply-body-{{ $comment->id }}" wire:model="replyBody" maxlength="1000" rows="2" required placeholder="Write a reply..." class="w-full resize-y rounded-sm border border-line bg-page p-3 text-sm" @error('replyBody') aria-invalid="true" aria-describedby="reply-error-{{ $comment->id }}" @enderror></textarea>
                                    @error('replyBody')<p id="reply-error-{{ $comment->id }}" role="alert" class="text-xs text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
                                    <div class="flex items-center justify-end gap-3">
                                        <button type="button" wire:click="cancelReply" class="min-h-11 px-3 text-xs text-muted">Cancel</button>
                                        <button type="submit" wire:loading.attr="disabled" wire:target="reply" class="min-h-11 rounded-sm bg-brand px-4 text-xs text-on-brand disabled:opacity-50"><span wire:loading.remove wire:target="reply">Send reply</span><span wire:loading wire:target="reply">Sending...</span></button>
                                    </div>
                                </form>
                            @endif

                            @if($comment->replies->isNotEmpty())
                                <div class="mt-3 flex flex-col gap-4 border-l border-line pl-4">
                                    @foreach($comment->replies as $reply)
                                        <div wire:key="comment-reply-{{ $reply->id }}" class="flex gap-3">
                                            <x-user-avatar :user="$reply->user"/>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2"><span class="text-xs font-semibold">{{ $reply->user->name }}</span><time datetime="{{ $reply->created_at->toIso8601String() }}" class="text-xs text-muted">{{ $reply->created_at->diffForHumans() }}</time></div>
                                                <p class="mt-1 whitespace-pre-wrap text-sm leading-6 wrap-anywhere">{{ $reply->body }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="py-2 text-xs text-muted">No comments yet. Start the conversation.</p>
                @endforelse
            </div>
            @if($rootCommentCount > $comments->count())<button type="button" wire:click="loadMore" wire:loading.attr="disabled" class="min-h-10 self-start text-xs text-brand">Load more comments</button>@endif
        </div>
    @endif
</section>
