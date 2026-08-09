<?php

namespace App\Helpers;

use Illuminate\Pagination\LengthAwarePaginator;

final class StructuredData
{
    private const JSON_FLAGS = JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
        | JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
        | JSON_THROW_ON_ERROR;

    public static function siteGraph(
        string $canonicalUrl,
        string $title,
        string $description,
        string $locale,
        string $pageType = 'WebPage'
    ): array
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
                    '@type' => $pageType,
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

    public static function itemList(
        string $canonicalUrl,
        string $name,
        LengthAwarePaginator $paginator
    ): array {
        $pageBase = rtrim($canonicalUrl, '/');
        $firstPosition = $paginator->firstItem() ?: 1;
        $elements = collect($paginator->items())
            ->values()
            ->map(function ($item, int $index) use ($firstPosition) {
                $productName = trim((string) data_get($item, 'name'));
                $itemName = $productName ?: trim((string) data_get($item, 'title'));
                $rawUrl = trim((string) data_get($item, 'url'));

                if ($itemName === '' || $rawUrl === '') {
                    return null;
                }

                $itemUrl = preg_match('#^https?://#i', $rawUrl) ? $rawUrl : url($rawUrl);
                $image = trim((string) (data_get($item, 'thumb') ?: data_get($item, 'image')));
                $referencedItem = [
                    '@id' => $itemUrl . ($productName !== '' ? '#product' : '#webpage'),
                    'name' => $itemName,
                    'url' => $itemUrl,
                ];

                if (preg_match('#^https?://#i', $image)) {
                    $referencedItem['image'] = $image;
                }

                return [
                    '@type' => 'ListItem',
                    'position' => $firstPosition + $index,
                    'item' => $referencedItem,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            '@id' => $pageBase . '#itemlist',
            'name' => $name,
            'url' => $canonicalUrl,
            'mainEntityOfPage' => [
                '@id' => $pageBase . '#webpage',
            ],
            'numberOfItems' => $paginator->total(),
            'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
            'itemListElement' => $elements,
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

    public static function toJson(array $schema): string
    {
        return json_encode($schema, self::JSON_FLAGS);
    }
}
