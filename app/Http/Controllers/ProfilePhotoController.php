<?php

namespace App\Http\Controllers;

use App\Services\CloudinaryMediaClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ProfilePhotoController extends Controller
{
    public function __invoke(Request $request, CloudinaryMediaClient $cloudinary): Response|BinaryFileResponse
    {
        $asset = $request->user()->profile_photo;
        abort_unless($asset, 404);
        $headers = ['Content-Type' => $asset['mime_type'], 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];
        if ($asset['disk'] === 'cloudinary') {
            try {
                $response = $cloudinary->fetch($asset);
            } catch (Throwable) {
                abort(502, 'Photo is temporarily unavailable.');
            }
            abort_unless($response->successful(), 502, 'Photo is temporarily unavailable.');

            return response($response->body(), 200, $headers);
        }
        $disk = Storage::disk('local');
        abort_unless($disk->exists($asset['path']), 404);

        return response()->file($disk->path($asset['path']), $headers);
    }
}
