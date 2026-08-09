<?php

namespace App\Helpers;

final class LandingPageStructuredData
{
    public static function bookPurchaseService(
        string $canonicalUrl,
        string $name,
        string $description,
        string $serviceType,
        string $locale
    ): array {
        $canonicalUrl = rtrim($canonicalUrl, '/');
        $siteUrl = rtrim((string) config('app.url'), '/');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            '@id' => $canonicalUrl . '#service',
            'name' => trim($name),
            'description' => trim($description),
            'serviceType' => trim($serviceType),
            'url' => $canonicalUrl,
            'provider' => [
                '@id' => $siteUrl . '/#organization',
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $canonicalUrl . '#webpage',
            ],
            'inLanguage' => $locale,
        ];
    }
}
