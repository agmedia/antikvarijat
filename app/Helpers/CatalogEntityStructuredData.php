<?php

namespace App\Helpers;

use App\Models\Front\Catalog\Author;
use App\Models\Front\Catalog\Publisher;
use Illuminate\Support\Str;

final class CatalogEntityStructuredData
{
    public static function author(Author $author, string $canonicalUrl, string $locale, string $fallbackDescription = ''): array
    {
        return self::entity(
            'Person',
            'person',
            (string) $author->title,
            (string) ($author->description ?: $fallbackDescription),
            $canonicalUrl,
            $locale
        );
    }

    public static function publisher(Publisher $publisher, string $canonicalUrl, string $locale, string $fallbackDescription = ''): array
    {
        return self::entity(
            'Organization',
            'publisher',
            (string) $publisher->title,
            (string) ($publisher->description ?: $fallbackDescription),
            $canonicalUrl,
            $locale
        );
    }

    private static function entity(
        string $type,
        string $fragment,
        string $name,
        string $description,
        string $canonicalUrl,
        string $locale
    ): array {
        $canonicalUrl = rtrim($canonicalUrl, '/');
        $description = self::plainText($description);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $type,
            '@id' => $canonicalUrl . '#' . $fragment,
            'name' => self::plainText($name),
            'url' => $canonicalUrl,
            'mainEntityOfPage' => [
                '@type' => 'CollectionPage',
                '@id' => $canonicalUrl . '#webpage',
            ],
            'inLanguage' => $locale,
        ];

        if ($description !== '') {
            $schema['description'] = Str::limit($description, 500, '');
        }

        return $schema;
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
}
