<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

class NearbyCafeSearch
{
    public function search(float $latitude, float $longitude, int $radiusMeters, array $categories): array
    {
        $categories = array_values(array_unique($categories));
        sort($categories);
        $cacheKey = 'nearby-cafes:'.hash('sha256', sprintf('%.4f:%.4f:%d:%s', $latitude, $longitude, $radiusMeters, implode(',', $categories)));

        $elements = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($latitude, $longitude, $radiusMeters, $categories) {
            if (! RateLimiter::attempt('openstreetmap-overpass', 1, fn () => true, 1)) {
                throw new RuntimeException('The map search is busy. Please try again shortly.');
            }

            $around = sprintf('(around:%d,%.7F,%.7F)', $radiusMeters, $latitude, $longitude);
            $queries = [];
            foreach ($categories as $category) {
                $queries = [...$queries, ...$this->queriesFor($category, $around)];
            }
            if ($queries === []) {
                throw new RuntimeException('Choose at least one nearby place type.');
            }
            $query = '[out:json][timeout:15];('.implode(';', $queries).');out center;';

            $response = Http::asForm()
                ->acceptJson()
                ->withUserAgent('CoffeeSpace/1.0 ('.config('app.url').')')
                ->connectTimeout(3)
                ->timeout(18)
                ->post(config('services.overpass.endpoint'), ['data' => $query])
                ->throw();

            $elements = $response->json('elements');
            if (! is_array($elements)) {
                throw new RuntimeException('OpenStreetMap returned an invalid café list.');
            }

            return $elements;
        });

        return collect($elements)
            ->map(function (array $element) use ($latitude, $longitude) {
                $tags = $element['tags'] ?? [];
                $coordinates = $element['center'] ?? $element;
                $cafeLatitude = $coordinates['lat'] ?? null;
                $cafeLongitude = $coordinates['lon'] ?? null;
                $type = $element['type'] ?? null;
                $id = $element['id'] ?? null;

                if (! is_numeric($cafeLatitude) || ! is_numeric($cafeLongitude)
                    || ! in_array($type, ['node', 'way', 'relation'], true) || ! is_numeric($id)) {
                    return null;
                }

                $street = trim(implode(' ', array_filter([
                    $tags['addr:housenumber'] ?? null,
                    $tags['addr:street'] ?? null,
                ])));
                $address = implode(', ', array_filter([
                    $street,
                    $tags['addr:suburb'] ?? null,
                    $tags['addr:city'] ?? null,
                ]));

                return [
                    'name' => mb_substr((string) ($tags['name'] ?? $tags['brand'] ?? 'Unnamed café'), 0, 180),
                    'categories' => $this->labelsFor($tags),
                    'address' => mb_substr($address, 0, 250),
                    'opening_hours' => mb_substr((string) ($tags['opening_hours'] ?? ''), 0, 180),
                    'latitude' => (float) $cafeLatitude,
                    'longitude' => (float) $cafeLongitude,
                    'distance_m' => $this->distanceInMeters($latitude, $longitude, (float) $cafeLatitude, (float) $cafeLongitude),
                    'osm_type' => $type,
                    'osm_id' => (int) $id,
                ];
            })
            ->filter()
            ->filter(fn (array $cafe) => $cafe['distance_m'] <= $radiusMeters)
            ->sortBy('distance_m')
            ->take(20)
            ->values()
            ->all();
    }

    private function queriesFor(string $category, string $around): array
    {
        return match ($category) {
            'cafe' => ['nwr["amenity"="cafe"]'.$around],
            'burger' => ['nwr["amenity"~"^(fast_food|restaurant|cafe)$"]["cuisine"~"(^|[,;])burger([,;]|$)",i]'.$around],
            'matcha' => [
                'nwr["amenity"="cafe"]["cuisine"~"matcha",i]'.$around,
                'nwr["amenity"="cafe"]["name"~"matcha",i]'.$around,
                'nwr["drink:matcha"~"^(yes|only)$",i]'.$around,
            ],
            'fastfood' => ['nwr["amenity"="fast_food"]'.$around],
            default => [],
        };
    }

    private function labelsFor(array $tags): array
    {
        $labels = [];
        $amenity = $tags['amenity'] ?? '';
        $cuisine = (string) ($tags['cuisine'] ?? '');
        $name = (string) ($tags['name'] ?? '');

        if ($amenity === 'cafe') {
            $labels[] = 'Café';
        }
        if ($amenity === 'fast_food') {
            $labels[] = 'Fast food';
        }
        if (preg_match('/(^|[,;])\s*burger\s*([,;]|$)/i', $cuisine)) {
            $labels[] = 'Burger';
        }
        if (preg_match('/matcha/i', $cuisine.' '.$name.' '.($tags['drink:matcha'] ?? ''))) {
            $labels[] = 'Matcha';
        }

        return $labels;
    }

    private function distanceInMeters(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): int
    {
        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);
        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($fromLatitude)) * cos(deg2rad($toLatitude)) * sin($longitudeDelta / 2) ** 2;

        return (int) round(6_371_000 * 2 * asin(min(1, sqrt($a))));
    }
}
