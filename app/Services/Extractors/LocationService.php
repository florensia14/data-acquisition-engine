<?php

namespace App\Services\Extractors;

use App\Services\Contracts\ExtractorInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationService implements ExtractorInterface
{
    private const CACHE_TTL = 3600;

    public function extract(string $input): array
    {
        $cacheKey = 'location:' . md5($input);

        if (Cache::has($cacheKey)) {
            Log::info('LocationService: cache hit', ['query' => $input]);
            return Cache::get($cacheKey);
        }

        Log::info('LocationService: fetching from Nominatim', ['query' => $input]);

        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'DataAcquisitionEngine/1.0'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $input,
                    'format' => 'jsonv2',
                    'addressdetails' => 1,
                ]);

            $results = $response->json() ?? [];

            if (empty($results)) {
                throw new \Exception("Location not found for: {$input}");
            }

            $data = $results[0];

            $result = [
                'display_name' => $data['display_name'] ?? null,
                'latitude'     => $data['lat']          ?? null,
                'longitude'    => $data['lon']          ?? null,
                'importance'   => $data['importance']   ?? null,
                'osm_type'     => $data['osm_type']     ?? null,
                'address'      => $data['address']      ?? [],
            ];

            Cache::put($cacheKey, $result, self::CACHE_TTL);
            Log::info('LocationService: extraction success', ['query' => $input]);

            return $result;
        } catch (\Exception $e) {
            Log::error('LocationService: extraction failed', [
                'query' => $input,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}