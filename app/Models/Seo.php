<?php

namespace App\Models;

use App\Helpers\LocaleHelper;
use App\Models\Front\Catalog\Author;
use App\Models\Front\Catalog\Category;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Catalog\Publisher;

/**
 * Class Sitemap
 * @package App\Models
 */
class Seo
{


    /**
     * @return array
     */
    public static function getProductData(Product $product): array
    {
        if (LocaleHelper::isEnglish()) {
            return [
                'title' => $product->meta_title ?: $product->name . ' book ' . (isset($product->author->title) ? $product->author->title : ''),
                'description' => $product->meta_description ?: 'Book ' . $product->name . ' by ' . (isset($product->author->title) ? $product->author->title : 'unknown author') . ' from ' . ($product->year ?: '') . ' in Antikvarijat Biblos.'
            ];
        }

        return [
            'title'       => $product->meta_title ?: $product->name . ' knjige ' . (isset($product->author->title) ? $product->author->title : ''),
            'description' => $product->meta_description ?: 'Knjiga ' . $product->name . ' izdavača ' . (isset($product->author->title) ? $product->author->title : '') . ' godine izdanja ' . ($product->year ?: '') . ' i mjesta izdavanja ' . ($product->origin ?: '') . ' u Antikvarijatu Biblos.'
        ];
    }


    /**
     * @return array
     */
    public static function getAuthorData(Author $author, ?Category $cat = null, ?Category $subcat = null): array
    {
        $authorName = static::naturalAuthorName((string) $author->title);
        $metaTitle = trim((string) $author->meta_title);
        $metaDescription = trim((string) $author->meta_description);

        if (LocaleHelper::isEnglish()) {
            $title = $metaTitle !== '' ? $metaTitle : $authorName . ' – used and rare books | Biblos';
            $description = $metaDescription !== '' ? $metaDescription : $authorName . ': browse available used and rare books at Antikvarijat Biblos and order securely online.';
        } else {
            $title = $metaTitle !== '' ? $metaTitle : $authorName . ' – knjige i rabljena izdanja | Biblos';
            $description = $metaDescription !== '' ? $metaDescription : $authorName . ': pronađite knjige u ponudi Antikvarijata Biblos. Pregledajte dostupna rabljena i rijetka izdanja te jednostavno naručite online.';
        }

        return [
            'title'       => $title,
            'description' => $description
        ];
    }


    /**
     * Author records are stored as "Surname Given name(s)". SEO copy reads more
     * naturally when the leading surname is moved behind the given name(s).
     */
    private static function naturalAuthorName(string $name): string
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', $name));
        $parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY);

        if (! $parts || count($parts) < 2) {
            return $name;
        }

        $surname = array_shift($parts);
        $parts[] = $surname;

        return implode(' ', $parts);
    }


    /**
     * @return array
     */
    public static function getPublisherData(Publisher $publisher, ?Category $cat = null, ?Category $subcat = null): array
    {
        if (LocaleHelper::isEnglish()) {
            $title = $publisher->meta_title ?: $publisher->title . ' books - Antikvarijat Biblos';
            $description = $publisher->meta_description ?: 'Browse books from publisher ' . $publisher->title . ' in Antikvarijat Biblos with secure ordering and delivery.';
        } else {
            $title = $publisher->meta_title ?: $publisher->title . ' knjige - Antikvarijat Biblos';
            $description = $publisher->meta_description ?: 'Pregledajte dostupne knjige nakladnika ' . $publisher->title . ' u ponudi Antikvarijata Biblos. Sigurna kupnja i dostava.';
        }

        // Check if there is meta title or description and set vars.
        if ($cat) {
            if ($cat->meta_title) { $title = $cat->meta_title; }
            //if ($cat->meta_description) { $description = $cat->meta_description; }
        }

        if ($subcat) {
            if ($subcat->meta_title) { $title = $subcat->meta_title; }
            //if ($subcat->meta_description) { $description = $subcat->meta_description; }
        }

        return [
            'title'       => $title,
            'description' => $description
        ];
    }
}
