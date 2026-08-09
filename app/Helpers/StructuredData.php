<?php

namespace App\Helpers;

use App\Models\Front\Blog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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
        string $pageType = 'WebPage',
        array $merchantReturnPolicy = []
    ): array
    {
        $siteUrl = rtrim((string) config('app.url'), '/');
        $organizationId = $siteUrl . '/#organization';
        $websiteId = $siteUrl . '/#website';
        $logoId = $siteUrl . '/#logo';
        $pageId = rtrim($canonicalUrl, '/') . '#webpage';

        $organization = [
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
        ];

        if ($merchantReturnPolicy) {
            $organization['hasMerchantReturnPolicy'] = $merchantReturnPolicy;
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                $organization,
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

    public static function merchantReturnPolicy(array $settings, string $policyUrl): array
    {
        $merchantPaysReturn = ($settings['return_cost_policy'] ?? 'consumer') === 'merchant';

        return [
            '@type' => 'MerchantReturnPolicy',
            'applicableCountry' => 'HR',
            'returnPolicyCountry' => 'HR',
            'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
            'merchantReturnDays' => 14,
            'returnMethod' => 'https://schema.org/ReturnByMail',
            'returnFees' => $merchantPaysReturn
                ? 'https://schema.org/FreeReturn'
                : 'https://schema.org/ReturnShippingFees',
            'merchantReturnLink' => $policyUrl,
        ];
    }

    public static function offerShippingDetails(
        iterable $shippingMethods,
        iterable $geoZones,
        float $offerPrice
    ): array {
        $zones = collect($geoZones)->keyBy(fn ($zone) => (string) data_get($zone, 'id'));
        $freeShippingThreshold = (float) config('settings.free_shipping', 0);

        return collect($shippingMethods)
            ->map(function ($method) use ($zones, $offerPrice, $freeShippingThreshold) {
                $code = strtolower(trim((string) data_get($method, 'code')));

                if (! data_get($method, 'status', true) || in_array($code, ['pickup', 'gls_world'], true)) {
                    return null;
                }

                $price = data_get($method, 'data.price');
                $days = self::shippingDays(
                    (string) (data_get($method, 'data.time') ?: data_get($method, 'data.time_en'))
                );
                $zone = $zones->get((string) data_get($method, 'geo_zone'));
                $countries = self::shippingCountryCodes($zone);

                if (! is_numeric($price) || ! $days || ! $countries) {
                    return null;
                }

                $shippingPrice = (float) $price;
                if ($freeShippingThreshold > 0 && $offerPrice > $freeShippingThreshold) {
                    $shippingPrice = 0;
                }

                return [
                    '@type' => 'OfferShippingDetails',
                    'shippingRate' => [
                        '@type' => 'MonetaryAmount',
                        'value' => number_format($shippingPrice, 2, '.', ''),
                        'currency' => 'EUR',
                    ],
                    'shippingDestination' => collect($countries)
                        ->map(fn (string $country) => [
                            '@type' => 'DefinedRegion',
                            'addressCountry' => $country,
                        ])
                        ->values()
                        ->all(),
                    'deliveryTime' => [
                        '@type' => 'ShippingDeliveryTime',
                        'transitTime' => [
                            '@type' => 'QuantitativeValue',
                            'minValue' => $days[0],
                            'maxValue' => $days[1],
                            'unitCode' => 'DAY',
                        ],
                    ],
                ];
            })
            ->filter()
            ->values()
            ->all();
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

    public static function blogPosting(Blog $blog, string $canonicalUrl, string $locale): array
    {
        $siteUrl = rtrim((string) config('app.url'), '/');
        $published = Carbon::make($blog->publish_date ?: $blog->created_at);
        $modified = Carbon::make($blog->updated_at) ?: $published;
        $description = self::plainText(
            (string) ($blog->meta_description ?: $blog->short_description ?: $blog->description)
        );
        $articleBody = self::plainText((string) $blog->description);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            '@id' => rtrim($canonicalUrl, '/') . '#article',
            'url' => $canonicalUrl,
            'headline' => self::plainText((string) $blog->title),
            'description' => Str::limit($description, 500, ''),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => rtrim($canonicalUrl, '/') . '#webpage',
            ],
            'author' => [
                '@id' => $siteUrl . '/#organization',
            ],
            'publisher' => [
                '@id' => $siteUrl . '/#organization',
            ],
            'isPartOf' => [
                '@id' => $siteUrl . '/#website',
            ],
            'inLanguage' => $locale,
            'isAccessibleForFree' => true,
        ];

        if ($blog->image) {
            $schema['image'] = [
                '@type' => 'ImageObject',
                'url' => $blog->image,
                'contentUrl' => $blog->image,
                'caption' => self::plainText((string) $blog->title),
            ];
        }

        if ($published) {
            $schema['datePublished'] = $published->toAtomString();
        }

        if ($modified) {
            $schema['dateModified'] = $modified->toAtomString();
        }

        if ($articleBody !== '') {
            $schema['wordCount'] = str_word_count($articleBody);
        }

        return $schema;
    }

    public static function faqPage(string $canonicalUrl, iterable $items, string $locale): array
    {
        $questions = collect($items)
            ->map(function ($item) {
                $question = self::plainText((string) data_get($item, 'title'));
                $answer = self::plainText((string) data_get($item, 'description'));

                if ($question === '' || $answer === '') {
                    return null;
                }

                return [
                    '@type' => 'Question',
                    'name' => $question,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $answer,
                    ],
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            '@id' => rtrim($canonicalUrl, '/') . '#webpage',
            'url' => $canonicalUrl,
            'inLanguage' => $locale,
            'mainEntity' => $questions,
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

    private static function plainText(string $value): string
    {
        $value = (string) preg_replace(
            '/<(?:br\s*\/?|\/\s*(?:p|div|li|ul|ol|h[1-6]|blockquote))\s*>/iu',
            ' ',
            $value
        );
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    private static function shippingDays(string $deliveryTime): ?array
    {
        if (! preg_match('/(\d+)\s*(?:[-–]\s*(\d+))?/u', $deliveryTime, $matches)) {
            return null;
        }

        $minimum = (int) $matches[1];
        $maximum = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : $minimum;

        return [$minimum, max($minimum, $maximum)];
    }

    private static function shippingCountryCodes($zone): array
    {
        if (! $zone) {
            return [];
        }

        $countryNames = collect(data_get($zone, 'state', []))
            ->values()
            ->push(data_get($zone, 'title'));
        $countryCodes = [
            'croatia' => 'HR',
            'hrvatska' => 'HR',
        ];

        return $countryNames
            ->map(fn ($country) => $countryCodes[strtolower(trim((string) $country))] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
