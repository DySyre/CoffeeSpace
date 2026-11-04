<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

class PlaceSearch
{
    public function search(string $query): array
    {
        $query = trim($query);

        return Cache::remember('place-search:'.hash('sha256', mb_strtolower($query)), 600, function () use ($query) {
            if (! RateLimiter::attempt('place-search:provider', 1, fn () => true, 1)) {
                throw new RuntimeException('Please wait a moment before searching again.');
            }
            $response = Http::acceptJson()->withUserAgent('CoffeeSpace/1.0 ('.config('app.url').')')
                ->connectTimeout(3)->timeout(8)
                ->get(config('services.places.endpoint'), ['q' => $query, 'limit' => 5, 'lang' => 'en']);
            $response->throw();
            $features = $response->json('features');
            if (! is_array($features)) {
                throw new RuntimeException('Place search returned an invalid response.');
            }

            return collect($features)->take(5)->map(function ($feature) {
                $coordinates = data_get($feature, 'geometry.coordinates');
                if (! is_array($coordinates) || count($coordinates) < 2 || ! is_numeric($coordinates[0]) || ! is_numeric($coordinates[1]) || abs($coordinates[0]) > 180 || abs($coordinates[1]) > 90) {
                    return null;
                }
                $details = data_get($feature, 'properties', []);
                $name = mb_substr((string) ($details['name'] ?? $details['street'] ?? 'Selected place'), 0, 255);
                $area = mb_substr((string) ($details['city'] ?? $details['district'] ?? $details['county'] ?? $details['state'] ?? ''), 0, 255);
                $address = implode(', ', array_unique(array_filter([$details['street'] ?? '', $area, $details['state'] ?? '', $details['country'] ?? ''])));

                return ['name' => $name, 'area' => $area, 'address' => mb_substr($address, 0, 500), 'latitude' => (float) $coordinates[1], 'longitude' => (float) $coordinates[0]];
            })->filter()->values()->all();
        });
    }
}
