<?php

namespace App\Services;

use App\Services\Extractors\WebsiteService;
use App\Services\Extractors\DomainService;
use App\Services\Extractors\LocationService;

class CompanyInformationService
{
    public function __construct(
        private WebsiteService $websiteService,
        private DomainService $domainService,
        private LocationService $locationService,
    ) {
    }

    public function getCompanyInformation(string $domain): array
    {
        $domainData = $this->safeExtract(fn () => $this->domainService->extract($domain));

        $websiteData = $this->safeExtract(fn () => $this->websiteService->extract("https://{$domain}"));

       $locationData = $this->safeExtract(function () use ($domainData, $domain) {
    $registrar = $domainData['data']['registrar'] ?? null;

    if ($registrar) {
        try {
            return $this->locationService->extract($registrar);
        } catch (\Exception $e) {
            // kalau gagal pakai registrar, coba fallback ke domain
        }
    }

    return $this->locationService->extract($domain);
    });

        return [
            'website' => $websiteData,
            'domain' => $domainData,
            'location' => $locationData,
        ];
    }

    private function safeExtract(\Closure $callback): array
    {
        try {
            return [
                'success' => true,
                'data' => $callback(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}