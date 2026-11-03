<?php

namespace App\Livewire\Posts;

use App\Models\Post;
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
    public int $limit = 5;

    public bool $expanded = false;

    public string $body = '';

    public function toggle(): void
    {
        $this->publicPost();
        $this->expanded = ! $this->expanded;
    }

    public function loadMore(): void
    {
        $post = $this->publicPost();
        $this->limit = min($this->limit + 5, max(5, $post->comments()->count()));
    }

    public function addComment(): void
    {
        abort_unless(auth()->check(), 401);
        $post = $this->publicPost();
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
        RateLimiter::hit($key, 60);
        $this->reset('body');
        $this->expanded = true;
    }

    private function publicPost(): Post
    {
        return Post::publiclyVisible()->findOrFail($this->postId);
    }

    public function render(): View
    {
        $comments = collect();
        if ($this->expanded) {
            $post = $this->publicPost();
            $this->commentCount = $post->comments()->count();
            $comments = $post->comments()->with('user:id,name')->latest('id')->limit($this->limit)->get();
        }

        return view('livewire.posts.post-comments', ['comments' => $comments]);
    }
}
