<?php

namespace App\Helpers;

final class StructuredData
{
    public static function siteGraph(string $canonicalUrl, string $title, string $description, string $locale): array
    {
        $siteUrl = rtrim((string) config('app.url'), '/');
        $organizationId = $siteUrl . '/#organization';
        $websiteId = $siteUrl . '/#website';
        $logoId = $siteUrl . '/#logo';
        $pageId = rtrim($canonicalUrl, '/') . '#webpage';

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'BookStore',
                    '@id' => $organizationId,
                    'name' => 'Antikvarijat Biblos',
                    'url' => $siteUrl,
                    'logo' => [
                        '@type' => 'ImageObject',
                        '@id' => $logoId,
                        'url' => $siteUrl . '/apple-touch-icon.png',
                        'contentUrl' => $siteUrl . '/apple-touch-icon.png',
                        'width' => 180,
                        'height' => 180,
                        'caption' => 'Antikvarijat Biblos',
                    ],
                    'image' => ['@id' => $logoId],
                    'email' => 'info@antikvarijat-biblos.hr',
                    'telephone' => '+38514816574',
                    'priceRange' => '€€',
                    'currenciesAccepted' => 'EUR',
                    'sameAs' => [
                        'https://www.facebook.com/AntikvarijatBiblos/',
                        'https://www.instagram.com/antikvarijat_biblos/',
                    ],
                    'address' => [
                        '@type' => 'PostalAddress',
                        'streetAddress' => 'Palmotićeva 28',
                        'addressLocality' => 'Zagreb',
                        'postalCode' => '10000',
                        'addressCountry' => 'HR',
                    ],
                    'geo' => [
                        '@type' => 'GeoCoordinates',
                        'latitude' => 45.8106184,
                        'longitude' => 15.9795119,
                    ],
                    'openingHoursSpecification' => [
                        [
                            '@type' => 'OpeningHoursSpecification',
                            'dayOfWeek' => [
                                'https://schema.org/Monday',
                                'https://schema.org/Tuesday',
                                'https://schema.org/Wednesday',
                                'https://schema.org/Thursday',
                                'https://schema.org/Friday',
                            ],
                            'opens' => '09:00',
                            'closes' => '20:00',
                        ],
                        [
                            '@type' => 'OpeningHoursSpecification',
                            'dayOfWeek' => 'https://schema.org/Saturday',
                            'opens' => '09:00',
                            'closes' => '14:00',
                        ],
                    ],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $websiteId,
                    'name' => 'Antikvarijat Biblos',
                    'url' => $siteUrl,
                    'publisher' => ['@id' => $organizationId],
                    'inLanguage' => ['hr', 'en'],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => [
                            '@type' => 'EntryPoint',
                            'urlTemplate' => LocaleHelper::route('pretrazi') . '?' . config('settings.search_keyword') . '={search_term_string}',
                        ],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
                [
                    '@type' => 'WebPage',
                    '@id' => $pageId,
                    'url' => $canonicalUrl,
                    'name' => $title,
                    'description' => $description,
                    'isPartOf' => ['@id' => $websiteId],
                    'about' => ['@id' => $organizationId],
                    'inLanguage' => $locale,
                ],
            ],
        ];
    }

    public static function imageMimeType(?string $url): ?string
    {
        $path = parse_url((string) $url, PHP_URL_PATH);
        $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

        return [
            'avif' => 'image/avif',
            'gif' => 'image/gif',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        ][$extension] ?? null;
    }
}
