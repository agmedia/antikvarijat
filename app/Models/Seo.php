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
    private const MAX_META_TITLE_LENGTH = 65;

    private const MAX_META_DESCRIPTION_LENGTH = 160;


    /**
     * @return array
     */
    public static function getProductData(Product $product): array
    {
        $isEnglish = LocaleHelper::isEnglish();
        $manualTitle = trim((string) LocaleHelper::localizedField($product, 'meta_title', false));
        $manualDescription = trim((string) LocaleHelper::localizedField($product, 'meta_description', false));
        $productName = trim((string) $product->name);
        $authorName = trim((string) optional($product->author)->title);
        $year = trim((string) $product->year);

        $generatedTitle = static::productTitle($productName, $authorName, $year, $isEnglish);
        $generatedDescription = static::productDescription($productName, $authorName, $year, $isEnglish);

        return [
            'title' => $manualTitle !== '' ? $manualTitle : $generatedTitle,
            'description' => $manualDescription !== '' ? $manualDescription : $generatedDescription,
        ];
    }


    private static function productTitle(string $productName, string $authorName, string $year, bool $isEnglish): string
    {
        $primary = $productName !== '' ? $productName : ($isEnglish ? 'Used book' : 'Rabljena knjiga');
        $author = static::limitAtWord(static::naturalAuthorName($authorName), 26);
        $year = static::limitAtWord($year, 10);
        $details = trim($author . ($year !== '' ? ($author !== '' ? ', ' : '') . $year : ''));
        $suffix = $details !== ''
            ? ' – ' . $details . ' | Biblos'
            : ($isEnglish ? ' – used book | Biblos' : ' – rabljena knjiga | Biblos');
        $primaryMaximum = max(12, self::MAX_META_TITLE_LENGTH - mb_strlen($suffix));

        return static::limitAtWord($primary, $primaryMaximum) . $suffix;
    }


    private static function productDescription(string $productName, string $authorName, string $year, bool $isEnglish): string
    {
        $author = $authorName !== '' ? static::naturalAuthorName($authorName) : '';
        $yearText = $year !== '' ? ($isEnglish ? ', published ' . $year : ', izdanje ' . $year) : '';

        if ($isEnglish) {
            $description = 'Buy ' . ($productName ?: 'this used book')
                . ($author !== '' ? ' by ' . $author : '')
                . $yearText
                . ' at Antikvarijat Biblos. Secure online ordering and delivery.';
        } else {
            $description = 'Knjiga ' . ($productName ?: 'iz ponude Antikvarijata Biblos')
                . ($author !== '' ? ', autor ' . $author : '')
                . $yearText
                . '. Dostupna u Antikvarijatu Biblos uz sigurnu online kupnju i dostavu.';
        }

        return static::limitAtWord($description, self::MAX_META_DESCRIPTION_LENGTH);
    }


    /**
     * @return array
     */
    public static function getAuthorData(Author $author, ?Category $cat = null, ?Category $subcat = null): array
    {
        $storedAuthorName = trim((string) $author->title);
        $authorName = static::naturalAuthorName($storedAuthorName);
        $metaTitle = trim((string) LocaleHelper::localizedField($author, 'meta_title', false));
        $metaDescription = trim((string) LocaleHelper::localizedField($author, 'meta_description', false));

        if (static::isLegacyAuthorMetaTitle($metaTitle, $storedAuthorName)) {
            $metaTitle = '';
        }

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
     * Older author records may contain an automatically copied author name in
     * meta_title. These values are defaults, not manually written SEO titles.
     */
    private static function isLegacyAuthorMetaTitle(string $metaTitle, string $authorName): bool
    {
        if ($metaTitle === '') {
            return false;
        }

        return in_array($metaTitle, [
            $authorName,
            $authorName . ' knjige - Antikvarijat Biblos',
            $authorName . ' books - Antikvarijat Biblos',
        ], true);
    }


    /**
     * @return array
     */
    public static function getPublisherData(Publisher $publisher, ?Category $cat = null, ?Category $subcat = null): array
    {
        $metaTitle = trim((string) LocaleHelper::localizedField($publisher, 'meta_title', false));
        $metaDescription = trim((string) LocaleHelper::localizedField($publisher, 'meta_description', false));

        if (LocaleHelper::isEnglish()) {
            $title = $metaTitle ?: $publisher->title . ' books - Antikvarijat Biblos';
            $description = $metaDescription ?: 'Browse books from publisher ' . $publisher->title . ' in Antikvarijat Biblos with secure ordering and delivery.';
        } else {
            $title = $metaTitle ?: $publisher->title . ' knjige - Antikvarijat Biblos';
            $description = $metaDescription ?: 'Pregledajte dostupne knjige nakladnika ' . $publisher->title . ' u ponudi Antikvarijata Biblos. Sigurna kupnja i dostava.';
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


    public static function shouldIndexCatalogEntity($entity, int $productCount, ?string $locale = null): bool
    {
        return $productCount > 0;
    }


    private static function limitAtWord(string $value, int $maximum): string
    {
        $value = static::plainText($value);

        if (mb_strlen($value) <= $maximum) {
            return $value;
        }

        $shortened = mb_substr($value, 0, $maximum + 1);
        $lastSpace = mb_strrpos($shortened, ' ');

        if ($lastSpace !== false && $lastSpace >= (int) floor($maximum * 0.7)) {
            $shortened = mb_substr($shortened, 0, $lastSpace);
        } else {
            $shortened = mb_substr($shortened, 0, $maximum);
        }

        return rtrim($shortened, " \t\n\r\0\x0B,;:-–|");
    }


    private static function plainText(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', strip_tags($value)));
    }
}
