<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationUserSetting extends Model
{
    protected $fillable = ['conversation_id', 'user_id', 'muted_at', 'last_read_at', 'deleted_at'];

    protected function casts(): array
    {
        return ['muted_at' => 'datetime', 'last_read_at' => 'datetime', 'deleted_at' => 'datetime'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
