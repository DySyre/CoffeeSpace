<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CloudinaryMediaClient
{
    private function baseUrl(): string
    {
        $cloud = config('services.cloudinary.cloud_name');
        if (! is_string($cloud) || ! preg_match('/^[a-z0-9_-]+$/iD', $cloud) || ! filled(config('services.cloudinary.api_key')) || ! filled(config('services.cloudinary.api_secret'))) {
            throw new RuntimeException('Cloudinary credentials are not configured.');
        }

        return 'https://api.cloudinary.com/v1_1/'.$cloud;
    }

    private function authenticatedRequest(): PendingRequest
    {
        return Http::withBasicAuth(config('services.cloudinary.api_key'), config('services.cloudinary.api_secret'))
            ->connectTimeout(10)->timeout(90);
    }

    public function upload(UploadedFile $file, string $folder, string $resourceType): array
    {
        $base = $this->baseUrl();
        $name = (string) Str::uuid();
        $publicId = $folder.'/'.$name;
        $asset = ['disk' => 'cloudinary', 'path' => $publicId, 'resource_type' => $resourceType, 'delivery_type' => 'authenticated', 'asset_folder' => $folder];
        $options = ['public_id' => $publicId, 'type' => 'authenticated', 'overwrite' => 'false'];
        if (config('services.cloudinary.folder_mode') === 'fixed') {
            $options['folder'] = $folder;
            $options['public_id'] = $name;
        } else {
            $options['asset_folder'] = $folder;
        }

        $stream = fopen($file->getRealPath(), 'rb');
        try {
            $response = $this->authenticatedRequest()
                ->attach('file', $stream, $name.'.'.strtolower($file->getClientOriginalExtension()))
                ->post($base.'/'.$resourceType.'/upload', $options);
            $data = $response->json();
            if (! $response->successful() || ! is_array($data) || ($data['public_id'] ?? null) !== $publicId || ($data['resource_type'] ?? null) !== $resourceType || ($data['type'] ?? null) !== 'authenticated' || empty($data['asset_id']) || ! in_array($data['format'] ?? null, ['jpg', 'jpeg', 'png', 'mp4', 'mov'], true)) {
                throw new RuntimeException('Cloudinary did not confirm the expected asset.');
            }
            if (! isset($data['bytes']) || $data['bytes'] > ($resourceType === 'video' ? MediaInspector::MAX_VIDEO_BYTES : MediaInspector::MAX_BYTES) || ($resourceType === 'video' && (! isset($data['duration']) || ! is_numeric($data['duration']) || $data['duration'] <= 0 || $data['duration'] > 60))) {
                throw new RuntimeException('Cloudinary returned media outside the upload limits.');
            }

            return [...$asset, 'asset_id' => $data['asset_id'], 'format' => $data['format']];
        } catch (Throwable) {
            // Delete by our preallocated ID, including after an ambiguous timeout.
            try {
                $this->destroy($asset);
            } catch (Throwable) {
                Log::warning('Cloudinary upload cleanup requires retry.', ['public_id' => $publicId, 'resource_type' => $resourceType]);
            }
            // Provider responses and credentials never enter user errors or logs.
            throw new RuntimeException('Cloudinary upload failed. Check the connection and configuration, then try again.');
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public function destroy(array $asset): void
    {
        $response = $this->authenticatedRequest()->asForm()->post($this->baseUrl().'/'.$asset['resource_type'].'/destroy', [
            'public_id' => $asset['path'], 'type' => $asset['delivery_type'], 'invalidate' => 'true',
        ]);
        if (! $response->successful() || ! in_array($response->json('result'), ['ok', 'not found'], true)) {
            throw new RuntimeException('Cloudinary could not remove the asset.');
        }
    }

    public function fetch(array $asset, ?string $range = null): Response
    {
        $params = [
            'public_id' => $asset['path'], 'format' => $asset['format'],
            'type' => $asset['delivery_type'], 'expires_at' => now()->timestamp + 120,
            'timestamp' => now()->timestamp,
        ];
        ksort($params);
        // Cloudinary signs the unescaped parameter values, not their URL encoding.
        $signature = sha1(implode('&', array_map(fn ($key, $value) => $key.'='.$value, array_keys($params), array_values($params))).config('services.cloudinary.api_secret'));
        $params['api_key'] = config('services.cloudinary.api_key');
        $params['signature'] = $signature;

        $request = Http::connectTimeout(10)->timeout(60)->withOptions(['stream' => true]);
        if ($range !== null && preg_match('/^bytes=\d*-\d*$/D', $range)) {
            $request = $request->withHeaders(['Range' => $range]);
        }

        return $request->get($this->baseUrl().'/'.$asset['resource_type'].'/download', $params);
    }
}
