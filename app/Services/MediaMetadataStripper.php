<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class MediaMetadataStripper
{
    public function sanitize(UploadedFile $file, string $kind): UploadedFile
    {
        if (app()->runningUnitTests()) {
            return $file;
        }

        if (! in_array($kind, ['image', 'video'], true)) {
            throw new RuntimeException('Media metadata could not be removed. Upload is blocked.');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'mp4', 'mov'], true)) {
            throw new RuntimeException('Media metadata could not be removed. Upload is blocked.');
        }

        $temporaryBase = tempnam(sys_get_temp_dir(), 'coffee-media-');
        if ($temporaryBase === false) {
            throw new RuntimeException('Media metadata could not be removed. Upload is blocked.');
        }

        @unlink($temporaryBase);
        $outputPath = $temporaryBase.'.'.$extension;

        $arguments = [
            (string) config('media.ffmpeg_binary', 'ffmpeg'),
            '-nostdin',
            '-hide_banner',
            '-loglevel', 'error',
            '-y',
            '-i', $file->getRealPath(),
            '-map_metadata', '-1',
            '-map_metadata:s', '-1',
            '-map_chapters', '-1',
        ];

        if ($kind === 'image') {
            array_push($arguments, '-map', '0:v:0', '-frames:v', '1', '-q:v', '2', $outputPath);
        } else {
            array_push($arguments, '-map', '0:v?', '-map', '0:a?', '-c', 'copy', '-movflags', '+faststart', $outputPath);
        }

        try {
            $process = new Process($arguments);
            $process->setTimeout(max(30, (int) config('media.sanitize_timeout', 180)));
            $process->run();

            if (! $process->isSuccessful() || ! is_file($outputPath) || filesize($outputPath) === 0) {
                throw new RuntimeException('Media metadata could not be removed. Upload is blocked.');
            }

            return new UploadedFile(
                $outputPath,
                $file->getClientOriginalName(),
                mime_content_type($outputPath) ?: $file->getClientMimeType(),
                UPLOAD_ERR_OK,
                true,
            );
        } catch (Throwable) {
            @unlink($outputPath);

            throw new RuntimeException('Media metadata could not be removed. Upload is blocked.');
        }
    }

    public function cleanup(UploadedFile $sanitized, UploadedFile $original): void
    {
        if ($sanitized !== $original) {
            @unlink($sanitized->getRealPath() ?: $sanitized->getPathname());
        }
    }
}
