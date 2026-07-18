<?php

namespace App\Services\Extractors;

use App\Services\Contracts\ExtractorInterface;
use Illuminate\Support\Facades\Http;

class LocationService implements ExtractorInterface
{
    public function extract(string $input): array
    {
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

        return [
            'display_name' => $data['display_name'] ?? null,
            'latitude'     => $data['lat']          ?? null,
            'longitude'    => $data['lon']          ?? null,
            'importance'   => $data['importance']   ?? null,
            'osm_type'     => $data['osm_type']     ?? null,
            'address'      => $data['address']      ?? [],
        ];
    }
}
