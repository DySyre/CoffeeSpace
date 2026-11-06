<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostMedia;
use App\Services\CloudinaryMediaClient;
use App\Services\RedisPhotoCache;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class PostMediaController extends Controller
{
    public function show(Request $request, PostMedia $media, CloudinaryMediaClient $cloudinary, RedisPhotoCache $photoCache): Response
    {
        $post = $media->post;
        abort_unless($post, 404);
        abort_unless(Post::visibleTo($request->user())->whereKey($post->id)->exists(), 404);
        $etag = '"'.hash('sha256', $media->getKey().':'.$media->path.':'.($media->updated_at?->getTimestamp() ?? '')).'"';
        $lastModified = $media->updated_at ?? $media->created_at;
        $headers = [
            'Cache-Control' => 'private, max-age=300, must-revalidate',
            'ETag' => $etag,
            'X-Content-Type-Options' => 'nosniff',
        ];
        if ($lastModified) {
            $headers['Last-Modified'] = $lastModified->toRfc7231String();
        }
        $conditionalResponse = response('', 200, $headers);
        if ($conditionalResponse->isNotModified($request)) {
            return $conditionalResponse;
        }

        if ($media->disk === 'cloudinary' && $media->kind === 'image' && ! $request->headers->has('Range')) {
            try {
                $bytes = $photoCache->getOrFetch($media->getAttributes(), fn () =>
                    $cloudinary->fetch($media->getAttributes())->throw()->body()
                );
            } catch (ConnectionException|RequestException|RuntimeException) {
                abort(502, 'Media is temporarily unavailable.');
            }

            $headers['Content-Type'] = $media->mime_type;
            $headers['Content-Length'] = (string) strlen($bytes);

            return response($bytes, 200, $headers);
        }

        if ($media->disk === 'cloudinary') {
            try {
                $response = $cloudinary->fetch($media->getAttributes(), $request->header('Range'));
            } catch (ConnectionException|RuntimeException) {
                abort(502, 'Media is temporarily unavailable.');
            }
            abort_unless(in_array($response->status(), [200, 206], true), 502, 'Media is temporarily unavailable.');
            $body = $response->toPsrResponse()->getBody();
            $headers['Content-Type'] = $media->mime_type;
            foreach (['Content-Length', 'Content-Range', 'Accept-Ranges'] as $name) {
                if ($response->header($name) !== '') {
                    $headers[$name] = $response->header($name);
                }
            }

            return response()->stream(function () use ($body) {
                try {
                    while (! $body->eof()) {
                        echo $body->read(65536);
                    }
                } finally {
                    $body->close();
                }
            }, $response->status(), $headers);
        }
        $disk = Storage::disk($media->disk);
        abort_unless($disk->exists($media->path), 404);

        $headers['Content-Type'] = $media->mime_type;

        return response()->file($disk->path($media->path), $headers);
    }
}
