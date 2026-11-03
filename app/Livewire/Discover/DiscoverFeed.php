<?php

namespace App\Livewire\Discover;

use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class DiscoverFeed extends Component
{
    private const PAGE_SIZE = 2;

    #[Locked]
    public int $visibleCount = self::PAGE_SIZE;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'for-you')]
    public string $filter = 'for-you';

    public function placeholder(): View
    {
        return view('livewire.placeholders.discover');
    }

    public function updatedSearch(): void
    {
        $this->visibleCount = self::PAGE_SIZE;
    }

    public function updatedFilter(): void
    {
        $this->visibleCount = self::PAGE_SIZE;
    }

    public function loadMore(int $expectedCount): void
    {
        // Ignore a repeated request sent from the same scroll marker.
        if ($expectedCount !== $this->visibleCount) {
            return;
        }

        $this->visibleCount = min($this->visibleCount + self::PAGE_SIZE, max(self::PAGE_SIZE, $this->filteredStories()->count()));
    }

    private function filteredStories(): Builder
    {
        $query = mb_strtolower(mb_substr(trim($this->search), 0, 100));

        return Post::publiclyVisible()
            ->when($this->filter === 'following', fn ($stories) => $stories->whereRaw('1 = 0'))
            ->when($this->filter === 'nearby', fn ($stories) => $stories->where('city', 'Meycauayan, Bulacan'))
            ->when($query !== '', fn ($stories) => $stories->where(function (Builder $queryBuilder) use ($query) {
                foreach (['title', 'body', 'author_handle', 'cafe_name', 'city', 'tag'] as $column) {
                    $queryBuilder->orWhereRaw('LOWER('.$column.') LIKE ?', ['%'.$query.'%']);
                }
                $queryBuilder->orWhereHas('user', fn ($users) => $users->whereRaw('LOWER(name) LIKE ?', ['%'.$query.'%']));
            }));
    }

    public function render(): View
    {
        $posts = $this->filteredStories()->with(['user:id,name', 'media'])->withCount('comments')
            ->orderByDesc('id')->limit($this->visibleCount + 1)->get();
        $stories = $posts->take($this->visibleCount)->map(fn (Post $post) => [
            'id' => $post->id, 'name' => $post->user->name, 'handle' => $post->author_handle,
            'initials' => $post->user->initials(), 'city' => $post->city,
            'time' => $post->created_at->diffForHumans(), 'cafe' => $post->cafe_name,
            'title' => $post->title, 'story' => $post->body, 'image' => $post->image_path,
            'alt' => $post->image_alt, 'rating' => $post->rating,
            'price' => $post->price_centavos === null ? null : number_format($post->price_centavos / 100, 2), 'tag' => $post->tag,
            'media' => $post->media->map(fn ($item) => ['kind' => $item->kind, 'mime_type' => $item->mime_type, 'url' => route('post-media.show', $item)])->all(),
            'likes' => 0, 'comments_count' => $post->comments_count,
            'latitude' => $post->latitude, 'longitude' => $post->longitude,
        ]);

        return view('livewire.discover.feed', [
            'stories' => $stories,
            'hasMore' => $posts->count() > $this->visibleCount,
        ]);
    }
}
