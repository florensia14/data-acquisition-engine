<?php

namespace App\Services\Extractors;

use App\Services\Contracts\ExtractorInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DomainService implements ExtractorInterface
{
    private const CACHE_TTL = 3600; // 1 jam


    public function extract(string $input): array
    {
        $cacheKey = 'domain:' . md5($input);

        if (Cache::has($cacheKey)) {
            Log::info('DomainService: cache hit', ['domain' => $input]);
            return Cache::get($cacheKey);
        }

        Log::info('DomainService: fetching from RDAP', ['domain' => $input]);

        try {
            $response = Http::timeout(10)->get("https://rdap.org/domain/{$input}");
            $data = $response->json() ?? [];

            if (empty($data)) {
                throw new \Exception("Domain not found or invalid: {$input}");
            }

            $result = [
                'domain'         => $data['ldhName'] ?? $input,
                'registrar'      => $this->getRegistrar($data),
                'registered_at'  => $this->getEventDate($data, 'registration'),
                'expired_at'     => $this->getEventDate($data, 'expiration'),
                'last_updated'   => $this->getEventDate($data, 'last changed'),
                'status'         => $data['status'] ?? [],
                'nameservers'    => $this->getNameservers($data),
            ];

            Cache::put($cacheKey, $result, self::CACHE_TTL);
            Log::info('DomainService: extraction success', ['domain' => $input]);

            return $result;
        } catch (\Exception $e) {
            Log::error('DomainService: extraction failed', [
                'domain' => $input,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function getRegistrar(array $data): ?string
    {
        $entities = $data['entities'] ?? [];

        foreach ($entities as $entity) {
            if (in_array('registrar', $entity['roles'] ?? [])) {
                $vcard = $entity['vcardArray'][1] ?? [];
                foreach ($vcard as $field) {
                    if ($field[0] === 'fn') {
                        return $field[3];
                    }
                }
            }
        }

        return null;
    }

    private function getEventDate(array $data, string $action): ?string
    {
        $events = $data['events'] ?? [];

        foreach ($events as $event) {
            if ($event['eventAction'] === $action) {
                return $event['eventDate'];
            }
        }

        return null;
    }

    private function getNameservers(array $data): array
    {
        $nameservers = $data['nameservers'] ?? [];

        return array_map(fn($ns) => $ns['ldhName'] ?? '', $nameservers);
    }
}