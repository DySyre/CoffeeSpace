<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class RedisPhotoCache
{
    private const TTL_SECONDS = 86400;

    private const MAX_CACHEABLE_BYTES = 10 * 1024 * 1024;

    public function getOrFetch(array $asset, callable $fetch): string
    {
        $key = $this->cacheKey($asset);
        $store = null;

        try {
            $store = Cache::store('redis');
            $cached = $store->get($key);
            if (is_string($cached)) {
                return $cached;
            }
        } catch (Throwable) {
            // Redis is an optimization; continue serving from Cloudinary if it is unavailable.
            $store = null;
        }

        $bytes = $fetch();
        if (! is_string($bytes)) {
            throw new RuntimeException('The photo source did not return image bytes.');
        }

        if ($store && strlen($bytes) <= self::MAX_CACHEABLE_BYTES) {
            try {
                $store->put($key, $bytes, self::TTL_SECONDS);
            } catch (Throwable) {
                // A cache write failure must not prevent the photo from being served.
            }
        }

        return $bytes;
    }

    private function cacheKey(array $asset): string
    {
        $identity = implode('|', [
            (string) ($asset['path'] ?? ''),
            (string) ($asset['resource_type'] ?? 'image'),
            (string) ($asset['format'] ?? ''),
            (string) ($asset['asset_id'] ?? ''),
        ]);

        return 'cloudinary-photo:'.hash('sha256', $identity);
    }
}
