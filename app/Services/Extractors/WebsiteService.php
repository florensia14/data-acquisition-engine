<?php

namespace App\Services\Extractors;

use App\Services\Contracts\ExtractorInterface;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class WebsiteService implements ExtractorInterface
{
    public function extract(string $input): array
    {
        $response = Http::timeout(10)->get($input);

        $html = $response->body();
        $crawler = new Crawler($html);

        return [
            'url' => $input,
            'title' => $this->getTitle($crawler),
            'description' => $this->getMetaContent($crawler, 'description'),
            'canonical' => $this->getCanonical($crawler),
            'favicon' => $this->getFavicon($crawler, $input),
            'emails' => $this->findEmails($html),
            'phones' => $this->findPhones($html),
            'social_media' => $this->findSocialMedia($crawler),
            'open_graph' => [
                'title' => $this->getMetaContent($crawler, 'og:title', 'property'),
                'description' => $this->getMetaContent($crawler, 'og:description', 'property'),
                'image' => $this->getMetaContent($crawler, 'og:image', 'property'),
            ],
        ];
    }

    private function getTitle(Crawler $crawler): ?string
    {
        $node = $crawler->filter('title');
        return $node->count() > 0 ? trim($node->text()) : null;
    }

    private function getMetaContent(Crawler $crawler, string $name, string $attribute = 'name'): ?string
    {
        $node = $crawler->filter("meta[{$attribute}=\"{$name}\"]");
        return $node->count() > 0 ? $node->attr('content') : null;
    }

    private function getCanonical(Crawler $crawler): ?string
    {
        $node = $crawler->filter('link[rel="canonical"]');
        return $node->count() > 0 ? $node->attr('href') : null;
    }

    private function getFavicon(Crawler $crawler, string $baseUrl): ?string
    {
        $node = $crawler->filter('link[rel="icon"], link[rel="shortcut icon"]');
        if ($node->count() === 0) {
            return null;
        }

        $favicon = $node->attr('href');

        if (str_starts_with($favicon, 'http')) {
            return $favicon;
        }

        $parsed = parse_url($baseUrl);
        return $parsed['scheme'] . '://' . $parsed['host'] . '/' . ltrim($favicon, '/');
    }

    private function findEmails(string $html): array
    {
        preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $html, $matches);
        return array_values(array_unique($matches[0]));
    }

    private function findPhones(string $html): array
    {
        preg_match_all('/(\+62|62|0)[\s\-]?8[0-9]{2}[\s\-]?[0-9]{3,4}[\s\-]?[0-9]{3,4}/', $html, $matches);
        return array_values(array_unique($matches[0]));
    }

     private function findSocialMedia(Crawler $crawler): array
    {
        $platforms = ['facebook.com', 'instagram.com', 'twitter.com', 'x.com', 'linkedin.com', 'youtube.com', 'tiktok.com'];
        $found = [];

        $crawler->filter('a')->each(function (Crawler $node) use (&$found, $platforms) {
            $href = $node->attr('href');
            if (!$href) {
                return;
            }
            foreach ($platforms as $platform) {
                if (str_contains($href, $platform) && !in_array($href, $found)) {
                    $found[] = $href;
                }
            }
        });

        return $found;
    }
}