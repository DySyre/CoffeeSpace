<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostMedia extends Model
{
    protected $table = 'post_media';

    protected $fillable = ['disk', 'path', 'mime_type', 'kind', 'size_bytes', 'duration_seconds', 'position', 'amenity_key', 'asset_id', 'resource_type', 'delivery_type', 'format', 'asset_folder'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
