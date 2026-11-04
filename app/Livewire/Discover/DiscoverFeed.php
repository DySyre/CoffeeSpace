<?php

namespace App\Livewire\Discover;

use App\Models\Post;
use App\Models\User;
use App\Notifications\ActivityEmailNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class DiscoverFeed extends Component
{
    private const PAGE_SIZE = 2;

    private const NEARBY_RADIUS_KM = 3.0;

    #[Locked]
    public int $visibleCount = self::PAGE_SIZE;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'for-you')]
    public string $filter = 'for-you';

    #[Locked]
    public ?float $nearbyLatitude = null;

    #[Locked]
    public ?float $nearbyLongitude = null;

    public string $nearbyLocationError = '';

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

    public function setNearbyLocation(float $latitude, float $longitude): void
    {
        abort_unless(is_finite($latitude) && $latitude >= -90 && $latitude <= 90, 422);
        abort_unless(is_finite($longitude) && $longitude >= -180 && $longitude <= 180, 422);

        $this->nearbyLatitude = $latitude;
        $this->nearbyLongitude = $longitude;
        $this->nearbyLocationError = '';
        $this->filter = 'nearby';
        $this->visibleCount = self::PAGE_SIZE;
    }

    public function nearbyLocationUnavailable(): void
    {
        $this->nearbyLocationError = 'Allow location access to see coffee stories near you.';
        $this->filter = 'nearby';
        $this->visibleCount = self::PAGE_SIZE;
    }

    public function loadMore(int $expectedCount): void
    {
        // Ignore a repeated request sent from the same scroll marker.
        if ($expectedCount !== $this->visibleCount) {
            return;
        }
        $hasNextPage = $this->filteredStories()
            ->limit($this->visibleCount + 1)
            ->pluck('posts.id')
            ->count() > $this->visibleCount;

        if ($hasNextPage) {
            $this->visibleCount += self::PAGE_SIZE;
        }
    }

    public function toggleLike(int $postId): void
    {
        abort_unless(auth()->check(), 401);
        $post = Post::visibleTo(auth()->user())->findOrFail($postId);
        $likes = auth()->user()->likedPosts();
        $liked = ! $likes->whereKey($post->id)->exists();
        if (! $liked) {
            $likes->detach($post->id);
        } else {
            $likes->syncWithoutDetaching([$post->id]);
        }

    }

    public function toggleSave(int $postId): void
    {
        abort_unless(auth()->check(), 401);
        $user = auth()->user();
        $post = Post::visibleTo($user)->findOrFail($postId);

        $saved = $user->toggleSavedPost($post);
        if ($saved) {
            $postOwner = $post->user;
            if ($postOwner->isNot($user) && $postOwner->hasVerifiedEmail() && $postOwner->notify_saves_by_email) {
                $postOwner->notify((new ActivityEmailNotification(
                    'SAVED',
                    'Someone saved your post',
                    $user->name,
                    $post->title,
                    'View post',
                    $post->postUrl(),
                ))->afterCommit());
            }
        }
    }

    private function filteredStories(): Builder
    {
        $query = mb_substr(trim($this->search), 0, 100);

        return Post::visibleTo(auth()->user())->where('visibility', '!=', 'private')->whereNull('archived_at')
            ->when($this->filter === 'following', fn ($stories) => auth()->check() ? $stories->whereIn('user_id', auth()->user()->following()->select('users.id')) : $stories->whereRaw('1 = 0'))
            ->when($this->filter === 'nearby', fn (Builder $stories) => $this->nearbyStories($stories))
            ->when($query !== '', fn (Builder $stories) => $this->searchStories($stories, $query));
    }

    private function searchStories(Builder $stories, string $query): Builder
    {
        if (mb_strlen($query) < 3) {
            return $stories->whereRaw('1 = 0');
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            $namePrefix = addcslashes($query, '\\%_').'%';
            $matchingUsers = User::query()
                ->whereFullText('name', $query)
                ->orWhere('name', 'like', $namePrefix)
                ->select('id');

            return $stories->where(fn (Builder $matches) => $matches
                ->whereFullText(['title', 'body', 'author_handle', 'cafe_name', 'city', 'tag'], $query)
                ->orWhereIn('posts.user_id', $matchingUsers));
        }

        $query = mb_strtolower($query);

        return $stories->where(function (Builder $matches) use ($query) {
            foreach (['title', 'body', 'author_handle', 'cafe_name', 'city', 'tag'] as $column) {
                $matches->orWhereRaw('LOWER('.$column.') LIKE ?', ['%'.$query.'%']);
            }

            $matches->orWhereHas('user', fn ($users) => $users->whereRaw('LOWER(name) LIKE ?', ['%'.$query.'%']));
        });
    }

    private function nearbyStories(Builder $stories): Builder
    {
        if ($this->nearbyLatitude === null || $this->nearbyLongitude === null) {
            return $stories->whereRaw('1 = 0');
        }

        $radiusKm = self::NEARBY_RADIUS_KM;
        $latitude = $this->nearbyLatitude;
        $longitude = $this->nearbyLongitude;
        $latitudeDelta = $radiusKm / 111.045;
        $longitudeDelta = min(180, $radiusKm / (111.045 * max(abs(cos(deg2rad($latitude))), 0.01)));

        $stories->whereBetween('posts.latitude', [max(-90, $latitude - $latitudeDelta), min(90, $latitude + $latitudeDelta)]);

        $minLongitude = $longitude - $longitudeDelta;
        $maxLongitude = $longitude + $longitudeDelta;
        if ($minLongitude < -180) {
            $stories->where(fn (Builder $coordinates) => $coordinates
                ->whereBetween('posts.longitude', [$minLongitude + 360, 180])
                ->orWhereBetween('posts.longitude', [-180, $maxLongitude]));
        } elseif ($maxLongitude > 180) {
            $stories->where(fn (Builder $coordinates) => $coordinates
                ->whereBetween('posts.longitude', [$minLongitude, 180])
                ->orWhereBetween('posts.longitude', [-180, $maxLongitude - 360]));
        } else {
            $stories->whereBetween('posts.longitude', [$minLongitude, $maxLongitude]);
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            $distanceSql = '6371 * 2 * ASIN(SQRT(POWER(SIN(RADIANS(posts.latitude - ?) / 2), 2) + COS(RADIANS(?)) * COS(RADIANS(posts.latitude)) * POWER(SIN(RADIANS(posts.longitude - ?) / 2), 2)))';
            $stories->whereRaw($distanceSql.' <= ?', [$latitude, $latitude, $longitude, $radiusKm]);
        }

        return $stories;
    }

    public function render(): View
    {
        $posts = $this->filteredStories()->with(['user:id,name,profile_uuid', 'media', 'amenityMedia'])->withCount(['comments', 'likes'])
            ->orderByDesc('id')->limit($this->visibleCount + 1)->get();
        $stories = $posts->take($this->visibleCount)->map(fn (Post $post) => [
            'post_url' => $post->postUrl(), 'amenities' => $post->amenities ?? [], 'amenity_media' => $post->amenityMedia->map(fn ($item) => ['amenity_key' => $item->amenity_key, 'url' => route('post-media.show', $item)])->all(), 'visibility' => $post->visibility, 'profile_url' => $post->user->profileUrl(), 'id' => $post->id, 'name' => $post->user->name, 'handle' => $post->author_handle,
            'initials' => $post->user->initials(), 'city' => $post->city,
            'time' => $post->created_at->diffForHumans(), 'cafe' => $post->cafe_name,
            'title' => $post->title, 'story' => $post->body, 'image' => $post->image_path,
            'alt' => $post->image_alt, 'rating' => $post->rating,
            'price' => $post->price_centavos === null ? null : number_format($post->price_centavos / 100, 2), 'tag' => $post->tag,
            'media' => $post->media->map(fn ($item) => ['kind' => $item->kind, 'mime_type' => $item->mime_type, 'url' => route('post-media.show', $item)])->all(),
            'likes' => $post->likes_count, 'liked' => auth()->check() && $post->likes()->whereKey(auth()->id())->exists(),
            'saved' => auth()->check() && auth()->user()->hasSavedPost($post), 'comments_count' => $post->comments_count,
            'latitude' => $post->latitude, 'longitude' => $post->longitude,
        ]);

        return view('livewire.discover.feed', [
            'stories' => $stories,
            'hasMore' => $posts->count() > $this->visibleCount,
            'nearbyRadiusKm' => self::NEARBY_RADIUS_KM,
        ]);
    }
}
