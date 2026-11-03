<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class MediaInspector
{
    public const MAX_BYTES = 10 * 1024 * 1024;

    public function inspect(UploadedFile $file): array
    {
        if (! $file->isValid() || $file->getSize() > self::MAX_BYTES) {
            throw new InvalidArgumentException('Each file must be no larger than 10 MB.');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
        $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'mp4' => 'video/mp4', 'mov' => 'video/quicktime'];
        if (! isset($allowed[$extension]) || $mime !== $allowed[$extension]) {
            throw new InvalidArgumentException('Upload a JPEG, PNG, MP4, or MOV file with matching contents.');
        }

        $metadata = ['mime_type' => $mime, 'kind' => str_starts_with($mime, 'image/') ? 'image' : 'video', 'size_bytes' => $file->getSize(), 'duration_seconds' => null];
        if ($metadata['kind'] === 'image') {
            if (@getimagesize($file->getRealPath()) === false) {
                throw new InvalidArgumentException('This image could not be read. Choose another JPEG or PNG.');
            }

            return $metadata;
        }

        try {
            $reader = new \getID3;
            $analysis = $reader->analyze($file->getRealPath());
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('This video could not be verified. Choose another MP4 or MOV.', previous: $exception);
        }
        $duration = $analysis['playtime_seconds'] ?? null;
        if (! empty($analysis['error']) || ! is_numeric($duration) || ! is_finite((float) $duration) || (float) $duration <= 0 || empty($analysis['video']['resolution_x']) || empty($analysis['video']['resolution_y'])) {
            throw new InvalidArgumentException('This video could not be verified. Choose a valid MP4 or MOV with a readable video track.');
        }
        if ((float) $duration > 60) {
            throw new InvalidArgumentException('Videos must be 60 seconds or shorter.');
        }

        $metadata['duration_seconds'] = (float) $duration;

        return $metadata;
    }
}
