<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use App\Services\EmailDeliveryLimiter;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    public const DEFAULT_BIO = 'Collecting memories, one café at a time.';

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->profile_uuid = (string) Str::uuid();
        });
    }

    public function profileUrl(): string
    {
        return route('profile.show', ['user' => $this->profile_uuid]);
    }

    public function getAuthIdentifierForBroadcasting(): string
    {
        return hash_hmac('sha256', (string) $this->getAuthIdentifier(), (string) config('app.key'));
    }

    public function sendEmailVerificationNotification(): void
    {
        app(EmailDeliveryLimiter::class)->sendVerification(
            $this,
            $this->getEmailForVerification(),
            new VerifyEmailNotification,
        );
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'user_follows', 'follower_id', 'followed_id')->withTimestamps();
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'user_follows', 'followed_id', 'follower_id')->withTimestamps();
    }

    public function blockedUsers(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'user_blocks', 'blocker_id', 'blocked_id')->withTimestamps();
    }

    public function blockedBy(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'user_blocks', 'blocked_id', 'blocker_id')->withTimestamps();
    }

    public function hasBlocked(User $user): bool
    {
        return $this->blockedUsers()->whereKey($user->id)->exists();
    }

    public function isOnline(): bool
    {
        return (bool) $this->show_activity
            && $this->last_seen_at !== null
            && $this->last_seen_at->gte(now()->subMinutes(2));
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function activityLabel(): string
    {
        return $this->isOnline() ? 'Online now' : $this->offlineActivityLabel();
    }

    public function offlineActivityLabel(): string
    {
        if (! $this->show_activity) {
            return 'Activity status hidden';
        }

        if (! $this->last_seen_at) {
            return 'Offline';
        }

        return 'Last seen '.$this->last_seen_at->diffForHumans();
    }

    public function coverPhotoUrl(): ?string
    {
        return $this->cover_photo ? route('profile.public-cover', ['user' => $this->profile_uuid, 'v' => substr(hash('sha256', $this->cover_photo['path']), 0, 12)]) : null;
    }

    public function profilePhotoUrl(): ?string
    {
        return $this->profile_photo
            ? route('profile.public-photo', ['user' => $this->profile_uuid, 'v' => substr(hash('sha256', $this->profile_photo['path']), 0, 12)])
            : null;
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function notificationSettings(): HasOne
    {
        return $this->hasOne(UserNotificationSetting::class)->withDefault([
            'notify_messages_by_email' => false,
            'notify_comments_by_email' => false,
            'notify_saves_by_email' => false,
        ]);
    }

    public function likedPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_likes')->withTimestamps();
    }

    public function savedPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'saved_posts')->withTimestamps();
    }

    public function hiddenPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'hidden_posts')->withTimestamps();
    }

    public function savedPlaces(): HasMany
    {
        return $this->hasMany(SavedPlace::class);
    }

    public function hasSavedPost(Post $post): bool
    {
        return $this->savedPosts()->whereKey($post->getKey())->exists();
    }

    public function toggleSavedPost(Post $post): bool
    {
        $savedPosts = $this->savedPosts();

        if ($savedPosts->whereKey($post->getKey())->exists()) {
            $savedPosts->detach($post->getKey());

            return false;
        }

        $savedPosts->syncWithoutDetaching([$post->getKey()]);

        return true;
    }

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'show_activity' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'profile_photo' => 'array',
            'cover_photo' => 'array',
            'last_seen_at' => 'datetime',
            'birthdate' => 'date:Y-m-d',
            'show_activity' => 'boolean',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }
}
