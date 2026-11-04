<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Post extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::creating(fn (Post $post) => $post->post_uuid = (string) Str::uuid());
    }

    public function postUrl(): string
    {
        return route('post.show', ['post' => $this->post_uuid]);
    }

    public const AMENITIES = [
        'wifi' => ['label' => 'Wi-Fi', 'icon' => 'wifi', 'hint' => 'Speed and reliability, e.g. 50 Mbps, stable for calls'],
        'outlet' => ['label' => 'Outlet', 'icon' => 'plug', 'hint' => 'Availability, e.g. 4 outlets beside the window seats'],
        'pricing' => ['label' => 'Pricing', 'icon' => 'budget', 'hint' => 'What to budget, e.g. coffee 150-220 PHP per cup'],
        'menu' => ['label' => 'Menu', 'icon' => 'menu', 'hint' => 'What is available, e.g. espresso, pastries and pasta'],
        'hours' => ['label' => 'Opening / closing', 'icon' => 'clock', 'hint' => 'Days and hours, e.g. Mon-Sat 8 AM-10 PM; closed Sunday'],
    ];

    protected $fillable = ['demo_key', 'author_handle', 'title', 'body', 'cafe_name', 'city', 'image_path', 'image_alt', 'rating', 'price_centavos', 'tag', 'visibility', 'latitude', 'longitude', 'amenities'];

    protected function casts(): array
    {
        return ['archived_at' => 'datetime', 'amenities' => 'array', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_likes')->withTimestamps();
    }

    public function savedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_posts')->withTimestamps();
    }

    public function media(): HasMany
    {
        return $this->hasMany(PostMedia::class)->whereNull('amenity_key')->orderBy('position')->orderBy('id');
    }

    public function amenityMedia(): HasMany
    {
        return $this->hasMany(PostMedia::class)->whereNotNull('amenity_key')->orderBy('position')->orderBy('id');
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->whereNull($query->qualifyColumn('archived_at'))
            ->where($query->qualifyColumn('visibility'), 'public');
    }

    public function scopeVisibleTo(Builder $query, ?User $viewer): Builder
    {
        $userId = $query->qualifyColumn('user_id');
        $visibility = $query->qualifyColumn('visibility');
        $archivedAt = $query->qualifyColumn('archived_at');

        return $query->where(function (Builder $available) use ($viewer, $userId, $archivedAt) {
            $available->whereNull($archivedAt);
            if ($viewer) {
                $available->orWhere($userId, $viewer->id);
            }
        })->where(function (Builder $access) use ($viewer, $userId, $visibility) {
            $access->where($visibility, 'public');
            if ($viewer) {
                $access->orWhere($userId, $viewer->id)
                    ->orWhere(function (Builder $followers) use ($viewer) {
                        $followers->where('posts.visibility', 'followers')->whereExists(function ($relation) use ($viewer) {
                            $relation->selectRaw('1')->from('user_follows')
                                ->whereColumn('user_follows.followed_id', 'posts.user_id')
                                ->where('user_follows.follower_id', $viewer->id);
                        });
                    });
            }
        })->when($viewer, fn (Builder $visible) => $visible->whereNotExists(function ($blocks) use ($viewer) {
            $blocks->selectRaw('1')->from('user_blocks')->where(function ($pair) use ($viewer) {
                $pair->where(function ($outgoing) use ($viewer) {
                    $outgoing->where('user_blocks.blocker_id', $viewer->id)
                        ->whereColumn('user_blocks.blocked_id', 'posts.user_id');
                })->orWhere(function ($incoming) use ($viewer) {
                    $incoming->whereColumn('user_blocks.blocker_id', 'posts.user_id')
                        ->where('user_blocks.blocked_id', $viewer->id);
                });
            });
        }));
    }
}
