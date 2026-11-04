<?php

namespace App\Livewire\Posts;

use App\Models\Post;
use App\Models\User;
use App\Notifications\ActivityEmailNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PostComments extends Component
{
    #[Locked]
    public int $postId;

    #[Locked]
    public int $commentCount = 0;

    #[Locked]
    public int $rootCommentCount = 0;

    #[Locked]
    public int $limit = 5;

    public bool $expanded = false;

    public string $body = '';

    public string $replyBody = '';

    public ?int $replyToCommentId = null;

    public function toggle(): void
    {
        $this->viewablePost();
        $this->expanded = ! $this->expanded;
    }

    public function loadMore(): void
    {
        $post = $this->viewablePost();
        $rootCommentCount = $post->comments()->whereNull('parent_comment_id')->count();
        $this->limit = min($this->limit + 5, max(5, $rootCommentCount));
    }

    public function addComment(): void
    {
        abort_unless(auth()->check(), 401);
        abort_unless(auth()->user()->hasVerifiedEmail(), 403, 'Verify your email address before commenting.');
        $post = $this->viewablePost();
        $this->body = trim($this->body);
        $this->validate(['body' => ['required', 'string', 'max:1000']]);
        $key = 'post-comments:'.auth()->id();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('body', 'Please wait a minute before adding another comment.');

            return;
        }

        $comment = $post->comments()->make(['body' => $this->body]);
        $comment->user()->associate(auth()->user());
        $comment->save();
        $this->notifyPostOwner($post, auth()->user());
        RateLimiter::hit($key, 60);
        $this->reset('body');
        $this->expanded = true;
    }

    public function startReply(int $commentId): void
    {
        abort_unless(auth()->check(), 401);
        abort_unless(auth()->user()->hasVerifiedEmail(), 403, 'Verify your email address before replying.');

        $this->viewablePost()->comments()->whereNull('parent_comment_id')->findOrFail($commentId);
        $this->replyToCommentId = $commentId;
        $this->reset('replyBody');
    }

    public function cancelReply(): void
    {
        $this->reset('replyBody', 'replyToCommentId');
    }

    public function reply(): void
    {
        abort_unless(auth()->check(), 401);
        abort_unless(auth()->user()->hasVerifiedEmail(), 403, 'Verify your email address before replying.');
        abort_unless($this->replyToCommentId !== null, 404);

        $post = $this->viewablePost();
        $parent = $post->comments()->whereNull('parent_comment_id')->findOrFail($this->replyToCommentId);
        $this->replyBody = trim($this->replyBody);
        $this->validate(['replyBody' => ['required', 'string', 'max:1000']]);

        $key = 'post-comments:'.auth()->id();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('replyBody', 'Please wait a minute before adding another comment.');

            return;
        }

        $reply = $parent->replies()->make(['body' => $this->replyBody]);
        $reply->user()->associate(auth()->user());
        $reply->save();
        $this->notifyPostOwner($post, auth()->user());
        RateLimiter::hit($key, 60);
        $this->reset('replyBody', 'replyToCommentId');
        $this->expanded = true;
    }

    private function notifyPostOwner(Post $post, User $actor): void
    {
        $postOwner = $post->user;

        if ($postOwner->isNot($actor) && $postOwner->hasVerifiedEmail() && $postOwner->notify_comments_by_email) {
            $postOwner->notify((new ActivityEmailNotification(
                'COMMENT',
                'A new comment on your post',
                $actor->name,
                $post->title,
                'View post',
                $post->postUrl(),
            ))->afterCommit());
        }
    }

    private function viewablePost(): Post
    {
        return Post::visibleTo(auth()->user())->where('visibility', '!=', 'private')->findOrFail($this->postId);
    }

    public function render(): View
    {
        $comments = collect();
        if ($this->expanded) {
            $post = $this->viewablePost();
            $this->commentCount = $post->comments()->count();
            $this->rootCommentCount = $post->comments()->whereNull('parent_comment_id')->count();
            $comments = $post->comments()
                ->whereNull('parent_comment_id')
                ->with(['user:id,name,profile_uuid,profile_photo', 'replies.user:id,name,profile_uuid,profile_photo'])
                ->latest('id')
                ->limit($this->limit)
                ->get();
        }

        return view('livewire.posts.post-comments', ['comments' => $comments]);
    }
}
