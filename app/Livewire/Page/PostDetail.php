<?php

namespace App\Livewire\Page;

use App\Models\Post;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PostDetail extends Component
{
    #[Locked]
    public int $postId;

    public function mount(Post $post): void
    {
        $this->postId = Post::visibleTo(auth()->user())->findOrFail($post->id)->id;
    }

    public function render(): View
    {
        $post = Post::visibleTo(auth()->user())->with(['user', 'media', 'amenityMedia'])->withCount('comments')->findOrFail($this->postId);

        return view('livewire.page.PostDetail', ['post' => $post])
            ->layout('components.layouts.coffee', ['title' => $post->title.' - CoffeeSpace', 'section' => 'discover']);
    }
}
