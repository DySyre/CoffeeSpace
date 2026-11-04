<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Conversation extends Model
{
    protected $fillable = ['user_one_id', 'user_two_id'];

    protected static function booted(): void
    {
        static::creating(fn (Conversation $conversation) => $conversation->conversation_uuid ??= (string) Str::uuid());
    }

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function userSettings(): HasMany
    {
        return $this->hasMany(ConversationUserSetting::class);
    }

    public function settingFor(User $user): ?ConversationUserSetting
    {
        return $this->relationLoaded('userSettings')
            ? $this->userSettings->firstWhere('user_id', $user->id)
            : $this->userSettings()->where('user_id', $user->id)->first();
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where(fn (Builder $participants) => $participants
            ->where('user_one_id', $user->id)
            ->orWhere('user_two_id', $user->id));
    }

    public function otherUser(User $user): User
    {
        return $this->user_one_id === $user->id ? $this->userTwo : $this->userOne;
    }
}
