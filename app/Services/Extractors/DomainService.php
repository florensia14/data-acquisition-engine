<?php

namespace App\Services\Extractors;

use App\Services\Contracts\ExtractorInterface;
use Illuminate\Support\Facades\Http;

class DomainService implements ExtractorInterface
{
    public function extract(string $input): array
    {
        $response = Http::timeout(10)->get("https://rdap.org/domain/{$input}");

        $data = $response->json();

        return[
            'domain'         => $data['ldhName'] ?? $input,
            'registrar'      => $this->getRegistrar ($data),
            'registered_at'  => $this->getEventDate ($data, 'registration'),
            'expired_at'     => $this->getEventDate ($data, 'expiration'),
            'last_updated'   => $this->getEventDate ($data, 'last changed'),
            'status'         => $data['status'] ?? [],
            'nameservers'    => $this->getNameservers($data),
        ];
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