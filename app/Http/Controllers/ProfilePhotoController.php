<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CloudinaryMediaClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ProfilePhotoController extends Controller
{
    public function __invoke(Request $request, CloudinaryMediaClient $cloudinary, string $kind = 'profile'): Response|BinaryFileResponse
    {
        return $this->serve($request->user(), $cloudinary, $kind);
    }

    public function publicPhoto(Request $request, User $user, CloudinaryMediaClient $cloudinary, string $kind = 'profile'): Response|BinaryFileResponse
    {
        if ($request->user() && ! $request->user()->is($user)) {
            abort_if($request->user()->hasBlocked($user) || $user->hasBlocked($request->user()), 404);
        }

        return $this->serve($user, $cloudinary, $kind);
    }

    private function serve(User $profile, CloudinaryMediaClient $cloudinary, string $kind): Response|BinaryFileResponse
    {
        abort_unless(in_array($kind, ['profile', 'cover'], true), 404);
        $asset = $kind === 'cover' ? $profile->cover_photo : $profile->profile_photo;
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
