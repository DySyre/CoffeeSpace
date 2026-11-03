<?php

namespace App\Http\Controllers;

use App\Models\PostMedia;
use App\Services\CloudinaryMediaClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostMediaController extends Controller
{
    public function show(Request $request, PostMedia $media, CloudinaryMediaClient $cloudinary): BinaryFileResponse|StreamedResponse
    {
        $post = $media->post;
        abort_unless($post->visibility === 'public' || $request->user()?->id === $post->user_id, 404);
        if ($media->disk === 'cloudinary') {
            try {
                $response = $cloudinary->fetch($media->getAttributes(), $request->header('Range'));
            } catch (ConnectionException|RuntimeException) {
                abort(502, 'Media is temporarily unavailable.');
            }
            abort_unless(in_array($response->status(), [200, 206], true), 502, 'Media is temporarily unavailable.');
            $body = $response->toPsrResponse()->getBody();
            $headers = ['Content-Type' => $media->mime_type, 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];
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

        return response()->file($disk->path($media->path), [
            'Content-Type' => $media->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
