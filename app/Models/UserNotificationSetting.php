<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationSetting extends Model
{
    protected $fillable = [
        'notify_messages_by_email',
        'notify_comments_by_email',
        'notify_saves_by_email',
    ];

    protected $attributes = [
        'notify_messages_by_email' => false,
        'notify_comments_by_email' => false,
        'notify_saves_by_email' => false,
    ];

    protected function casts(): array
    {
        return [
            'notify_messages_by_email' => 'boolean',
            'notify_comments_by_email' => 'boolean',
            'notify_saves_by_email' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
