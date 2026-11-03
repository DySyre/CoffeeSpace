<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class PostMediaStorage
{
    public function __construct(private CloudinaryMediaClient $cloudinary) {}

    public function store(UploadedFile $file, Post $post, array $metadata): array
    {
        if (config('post-media.disk') === 'cloudinary') {
            $folder = config('services.cloudinary.folders.post').'/'.$post->id.'/'.($metadata['kind'] === 'video' ? 'vid' : 'img');

            return $this->cloudinary->upload($file, $folder, $metadata['kind'] === 'video' ? 'video' : 'image');
        }
        if (config('post-media.disk') !== 'local') {
            throw new RuntimeException('Unsupported post media disk.');
        }
        $path = $file->store('post-media/'.$post->user_id, 'local');
        if (! $path) {
            throw new RuntimeException('Unable to store post media.');
        }

        return ['disk' => 'local', 'path' => $path];
    }

    public function remove(array $assets): void
    {
        foreach ($assets as $asset) {
            try {
                if ($asset['disk'] === 'cloudinary') {
                    $this->cloudinary->destroy($asset);
                } else {
                    Storage::disk($asset['disk'])->delete($asset['path']);
                }
            } catch (Throwable) {
                Log::warning('Post media cleanup requires retry.', ['disk' => $asset['disk'], 'path' => $asset['path']]);
            }
        }
    }
}
