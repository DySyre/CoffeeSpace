<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CloudinaryMediaClient;
use App\Services\RedisPhotoCache;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ProfilePhotoController extends Controller
{
    public function __invoke(Request $request, CloudinaryMediaClient $cloudinary, RedisPhotoCache $photoCache, string $kind = 'profile'): Response|BinaryFileResponse
    {
        return $this->serve($request->user(), $cloudinary, $photoCache, $kind);
    }

    public function publicPhoto(Request $request, User $user, CloudinaryMediaClient $cloudinary, RedisPhotoCache $photoCache, string $kind = 'profile'): Response|BinaryFileResponse
    {
        if ($request->user() && ! $request->user()->is($user)) {
            abort_if($request->user()->hasBlocked($user) || $user->hasBlocked($request->user()), 404);
        }

        return $this->serve($user, $cloudinary, $photoCache, $kind);
    }

    private function serve(User $profile, CloudinaryMediaClient $cloudinary, RedisPhotoCache $photoCache, string $kind): Response|BinaryFileResponse
    {
        abort_unless(in_array($kind, ['profile', 'cover'], true), 404);
        $asset = $kind === 'cover' ? $profile->cover_photo : $profile->profile_photo;
        abort_unless($asset, 404);
        $headers = ['Content-Type' => $asset['mime_type'], 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];
        if ($asset['disk'] === 'cloudinary') {
            try {
                $body = $photoCache->getOrFetch($asset, function () use ($cloudinary, $asset) {
                    $response = $cloudinary->fetch($asset);
                    if (! $response->successful()) {
                        throw new RuntimeException('Cloudinary photo fetch failed.');
                    }

                    return $response->body();
                });
            } catch (Throwable) {
                abort(502, 'Photo is temporarily unavailable.');
            }
            $headers['Cache-Control'] = 'private, max-age=300';

            return response($body, 200, $headers);
        }
        $disk = Storage::disk('local');
        abort_unless($disk->exists($asset['path']), 404);

        return response()->file($disk->path($asset['path']), $headers);
    }
}
