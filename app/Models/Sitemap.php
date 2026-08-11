<?php

namespace App\Models;

use App\Helpers\LocaleHelper;
use App\Models\Front\Catalog\Author;
use App\Models\Front\Catalog\Category;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Catalog\Publisher;
use App\Models\Front\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Class Sitemap
 * @package App\Models
 */
class Sitemap
{
    private const TYPES = [
        'pages',
        'categories',
        'products',
        'authors',
        'publishers',
        'images',
    ];

    /**
     * @var string|null
     */
    private $sitemap;

    /**
     * @var array
     */
    private $response = [];

    /**
     * @var int
     */
    private $shard;


    /**
     * Sitemap constructor.
     *
     * @param string|null $sitemap
     * @param int         $shard
     */
    public function __construct(?string $sitemap = null, int $shard = 1)
    {
        $this->shard = max($shard, 1);
        $this->sitemap = $this->setSitemap(static::normalizeType($sitemap));
    }


    /**
     * @return array|null
     */
    public function getSitemap()
    {
        return $this->sitemap;
    }


    /**
     * @return array
     */
    public function getResponse(): array
    {
        return $this->response;
    }


    /**
     * @param iterable<int, string> $sitemaps
     *
     * @return array<int, array{loc: string, lastmod: string}>
     */
    public static function indexItems(iterable $sitemaps): array
    {
        $response = [];

        foreach ($sitemaps as $sitemap) {
            $type = static::normalizeType((string) $sitemap);

            if (! $type || $type === 'images') {
                continue;
            }

            $shards = static::shardCount($type);
            $lastmod = static::lastModifiedFor($type)->tz('UTC')->toAtomString();

            for ($shard = 1; $shard <= $shards; $shard++) {
                $name = $shards > 1 ? $type . '-' . $shard . '.xml' : $type . '.xml';
                $response[] = [
                    'loc' => route('sitemap', ['sitemap' => $name]),
                    'lastmod' => $lastmod,
                ];
            }
        }

        return $response;
    }


    /**
     * @return array<int, array{loc: string, lastmod: string}>
     */
    public static function imageIndexItems(): array
    {
        $response = [];
        $lastmod = static::lastModifiedFor('images')->tz('UTC')->toAtomString();

        for ($shard = 1; $shard <= static::shardCount('images'); $shard++) {
            $response[] = [
                'loc' => route('image-sitemap', ['shard' => $shard . '.xml']),
                'lastmod' => $lastmod,
            ];
        }

        return $response;
    }


    /**
     * Canonical public entry pages that should be discoverable even when they
     * do not belong to a database-backed category or content-page sitemap.
     *
     * @return array<string, string>
     */
    public static function corePageUrls(string $locale): array
    {
        return [
            'home' => LocaleHelper::route('index', [], true, $locale),
            'contact' => LocaleHelper::route('kontakt', [], true, $locale),
            'faq' => LocaleHelper::route('faq', [], true, $locale),
            'returns' => LocaleHelper::route('contract-withdrawal.create', [], true, $locale),
            'book_purchase' => LocaleHelper::route('otkup.knjiga', [], true, $locale),
            'blog' => LocaleHelper::route('catalog.route.blog', [], true, $locale),
            'reviews' => LocaleHelper::route('reviews.index', [], true, $locale),
            'books' => LocaleHelper::route('catalog.route', ['group' => 'knjige'], true, $locale),
            'maps' => LocaleHelper::route('catalog.route', ['group' => 'zemljovidi-i-vedute'], true, $locale),
            'sale' => LocaleHelper::route('catalog.route.actions', [], true, $locale),
        ];
    }


    /**
     * @return array{type: string, shard: int|null}|null
     */
    public static function parseName(?string $sitemap): ?array
    {
        if (! $sitemap || ! preg_match('/^(pages|categories|products|authors|publishers)(?:-(\d+))?(?:\.xml)?$/', $sitemap, $matches)) {
            return null;
        }

        return [
            'type' => $matches[1],
            'shard' => isset($matches[2]) ? (int) $matches[2] : null,
        ];
    }


    public static function parseImageShard(?string $shard): ?int
    {
        if ($shard === null || $shard === '') {
            return null;
        }

        return preg_match('/^(\d+)(?:\.xml)?$/', $shard, $matches)
            ? (int) $matches[1]
            : 0;
    }


    public static function shardCount(string $type): int
    {
        return static::shardCountForUrlCount(static::urlCount($type));
    }


    public static function shardCountForUrlCount(int $urlCount): int
    {
        return max(1, (int) ceil(max(0, $urlCount) / static::maxUrlsPerSitemap()));
    }


    public static function maxUrlsPerSitemap(): int
    {
        return max(1, min(50000, (int) config('settings.sitemap_max_urls', 20000)));
    }


    public static function lastModifiedFor(string $type): Carbon
    {
        $type = static::normalizeType($type);

        if ($type === 'pages') {
            return static::dateFromMaxUpdatedAt(
                Page::query()
                    ->whereIn('group', ['page', 'blog'])
                    ->where('status', 1)
                    ->max('updated_at'),
                Carbon::now()->startOfMonth()
            );
        }

        if ($type === 'categories') {
            return static::dateFromMaxUpdatedAt(
                Category::query()->active()->max('updated_at'),
                Carbon::now()->startOfMonth()
            );
        }

        if ($type === 'products' || $type === 'images') {
            return static::dateFromMaxUpdatedAt(
                Product::query()->active()->hasStock()->max('updated_at'),
                Carbon::now()->startOfDay()
            );
        }

        if ($type === 'authors') {
            return static::dateFromMaxUpdatedAt(
                Author::query()->active()->max('updated_at'),
                Carbon::now()->startOfMonth()
            );
        }

        if ($type === 'publishers') {
            return static::dateFromMaxUpdatedAt(
                Publisher::query()->active()->max('updated_at'),
                Carbon::now()->startOfMonth()
            );
        }

        return Carbon::now()->startOfDay();
    }


    /**
     * @return array|null
     */
    private function setSitemap(?string $sitemap)
    {
        if ( ! $sitemap) {
            return $sitemap;
        }

        if ($sitemap === 'pages') {
            return $this->getPages();
        }

        if ($sitemap === 'categories') {
            return $this->getCategories();
        }

        if ($sitemap === 'products') {
            return $this->getProducts();
        }

        if ($sitemap === 'authors') {
            return $this->getAuthors();
        }

        if ($sitemap === 'publishers') {
            return $this->getPublishers();
        }

        if ($sitemap === 'images') {
            return $this->getImages();
        }

        return null;
    }


    /**
     * @return array
     */
    private function getImages(): array
    {
        $products = Product::query()
            ->active()
            ->hasStock()
            ->select('url', 'id', 'image')
            ->with('images')
            ->orderBy('id')
            ->forPage($this->shard, static::maxUrlsPerSitemap());

        foreach ($products->get() as $product) {
            $this->response[$product->id] = [
                'loc' => url($product->url)
            ];

            $this->response[$product->id]['images'][] = [
                'loc' => $product->image
            ];

            foreach ($product->images as $image) {
                $this->response[$product->id]['images'][] = [
                    'loc' => config('settings.images_domain') . $image->image
                ];
            }
        }

        return $this->response;
    }


    /**
     * @return array
     */
    private function getPages()
    {
        $pages = Page::query()->where('group', 'page')->where('slug', '!=', 'homepage')->where('status', '=', 1)->select('id', 'slug', 'slug_en', 'status', 'updated_at')->get();
        $blogs = Page::query()->where('group', 'blog')->where('status', '=', 1)->select('id', 'slug', 'slug_en', 'status', 'updated_at')->get();
        $categoryLastmod = static::lastModifiedFor('categories');
        $productLastmod = static::lastModifiedFor('products');

        foreach (LocaleHelper::locales() as $locale) {
            foreach (static::corePageUrls($locale) as $key => $url) {
                $lastmod = in_array($key, ['books', 'maps'], true)
                    ? $categoryLastmod
                    : ($key === 'sale' ? $productLastmod : Carbon::now()->startOfMonth());

                $this->addUrl($url, $lastmod);
            }
        }

        foreach ($pages as $page) {
            foreach (LocaleHelper::locales() as $locale) {
                $this->addUrl(LocaleHelper::route('catalog.route.page', ['page' => $page], true, $locale), $page->updated_at);
            }
        }

        foreach ($blogs as $blog) {
            foreach (LocaleHelper::locales() as $locale) {
                $this->addUrl(LocaleHelper::route('catalog.route.blog', ['blog' => $blog], true, $locale), $blog->updated_at);
            }
        }

        //dd($coll);

        return $this->sliceResponse();
    }


    /**
     * @return array
     */
    private function getCategories()
    {
        $categories = Category::query()
            ->active()
            ->topList()
            ->with(['subcategories' => fn ($query) => $query->active()->orderBy('id')])
            ->orderBy('id')
            ->get();

        foreach ($categories as $category) {
            foreach (LocaleHelper::locales() as $locale) {
                $this->addUrl(
                    LocaleHelper::route('catalog.route', ['group' => $category->getRawOriginal('group'), 'cat' => $category], true, $locale),
                    $category->updated_at
                );
            }

            foreach ($category->subcategories as $subcategory) {
                foreach (LocaleHelper::locales() as $locale) {
                    $this->addUrl(
                        LocaleHelper::route('catalog.route', ['group' => $category->getRawOriginal('group'), 'cat' => $category, 'subcat' => $subcategory], true, $locale),
                        $subcategory->updated_at
                    );
                }
            }
        }

        return $this->sliceResponse();
    }


    /**
     * @return array
     */
    private function getProducts()
    {
        $perPage = $this->recordsPerShard();
        $products = Product::query()
            ->active()
            ->hasStock()
            ->select('id', 'url', 'url_en', 'slug', 'slug_en', 'updated_at')
            ->with(['categories' => fn ($query) => $query->select(
                'categories.id',
                'categories.parent_id',
                'categories.group',
                'categories.slug',
                'categories.slug_en'
            )])
            ->orderBy('id')
            ->forPage($this->shard, $perPage)
            ->get();

        foreach ($products as $product) {
            foreach (LocaleHelper::locales() as $locale) {
                $this->addUrl(url(LocaleHelper::productPath($product, $product->getRawOriginal('url'), $locale)), $product->updated_at);
            }
        }

        return $this->response;
    }


    /**
     * @return array
     */
    private function getAuthors()
    {
        $locales = max(count(LocaleHelper::locales()), 1);
        $perPage = $this->recordsPerShard($locales);
        $authors = Author::query()
            ->active()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->whereIn('id', function ($query) {
                $query->from('authors')
                    ->selectRaw('MIN(id)')
                    ->where('status', 1)
                    ->whereNotNull('slug')
                    ->where('slug', '!=', '')
                    ->groupBy('slug');
            })
            ->select('id', 'slug', 'slug_en', 'url', 'updated_at')
            ->orderBy('id')
            ->forPage($this->shard, $perPage)
            ->get();

        if ($this->shard === 1) {
            foreach (LocaleHelper::locales() as $locale) {
                $this->addUrl(LocaleHelper::route('catalog.route.author', [], true, $locale), Carbon::now()->startOfMonth());
            }
        }

        foreach ($authors as $author) {
            foreach (LocaleHelper::locales() as $locale) {
                $this->addUrl(LocaleHelper::route('catalog.route.author', ['author' => $author], true, $locale), $author->updated_at);
            }

            /*$cats = Category::query()->topList()->whereHas('products', function ($query) use ($author) {
                $query->where('author_id', $author->id);
            })->with('subcategories')->get();

            if ($cats) {
                foreach ($cats as $category) {
                    $this->response[] = [
                        'url' => route('catalog.route.author', ['author' => $author->slug, 'cat' => $category->slug]),
                        'lastmod' => $author->updated_at->tz('UTC')->toAtomString()
                    ];

                    foreach ($category->subcategories()->get() as $subcategory) {
                        $this->response[] = [
                            'url' => route('catalog.route.author', ['author' => $author->slug, 'cat' => $category->slug, 'subcat' => $subcategory->slug]),
                            'lastmod' => $author->updated_at->tz('UTC')->toAtomString()
                        ];
                    }
                }
            }*/
        }

        return $this->response;
    }


    /**
     * @return array
     */
    private function getPublishers()
    {
        $locales = max(count(LocaleHelper::locales()), 1);
        $perPage = $this->recordsPerShard($locales);
        $publishers = Publisher::query()
            ->active()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->whereIn('id', function ($query) {
                $query->from('publishers')
                    ->selectRaw('MIN(id)')
                    ->where('status', 1)
                    ->whereNotNull('slug')
                    ->where('slug', '!=', '')
                    ->groupBy('slug');
            })
            ->select('id', 'slug', 'slug_en', 'url', 'updated_at')
            ->orderBy('id')
            ->forPage($this->shard, $perPage)
            ->get();

        if ($this->shard === 1) {
            foreach (LocaleHelper::locales() as $locale) {
                $this->addUrl(LocaleHelper::route('catalog.route.publisher', [], true, $locale), Carbon::now()->startOfMonth());
            }
        }

        foreach ($publishers as $publisher) {
            foreach (LocaleHelper::locales() as $locale) {
                $this->addUrl(LocaleHelper::route('catalog.route.publisher', ['publisher' => $publisher], true, $locale), $publisher->updated_at);
            }
        }

        return $this->response;
    }

    private function addUrl(string $url, $lastmod): void
    {
        $this->response[] = [
            'url' => $url,
            'lastmod' => Carbon::parse($lastmod)->tz('UTC')->toAtomString(),
        ];
    }


    private function recordsPerShard(int $reservedUrls = 0): int
    {
        $locales = max(count(LocaleHelper::locales()), 1);
        $availableUrls = max(1, static::maxUrlsPerSitemap() - $reservedUrls);

        return max(1, intdiv($availableUrls, $locales));
    }


    private function sliceResponse(): array
    {
        $offset = ($this->shard - 1) * static::maxUrlsPerSitemap();

        return array_slice($this->response, $offset, static::maxUrlsPerSitemap());
    }


    private static function normalizeType(?string $type): ?string
    {
        if (! $type) {
            return null;
        }

        $type = Str::replaceLast('.xml', '', $type);
        $type = $type === 'img' ? 'images' : $type;

        return in_array($type, self::TYPES, true) ? $type : null;
    }


    private static function urlCount(string $type): int
    {
        $type = static::normalizeType($type);
        $locales = max(count(LocaleHelper::locales()), 1);

        if ($type === 'pages') {
            $contentPages = Page::query()
                ->where('group', 'page')
                ->where('slug', '!=', 'homepage')
                ->where('status', 1)
                ->count();
            $blogs = Page::query()->where('group', 'blog')->where('status', 1)->count();

            return (count(static::corePageUrls(LocaleHelper::DEFAULT_LOCALE)) + $contentPages + $blogs) * $locales;
        }

        if ($type === 'categories') {
            $categories = Category::query()
                ->active()
                ->where(function ($query) {
                    $query->where('parent_id', 0)
                        ->orWhereHas('parent', fn ($parent) => $parent->active());
                })
                ->count();

            return $categories * $locales;
        }

        if ($type === 'products' || $type === 'images') {
            $products = Product::query()->active()->hasStock()->count();

            return $type === 'images' ? $products : $products * $locales;
        }

        if ($type === 'authors') {
            return (Author::query()
                    ->active()
                    ->whereNotNull('slug')
                    ->where('slug', '!=', '')
                    ->distinct()
                    ->count('slug') * $locales) + $locales;
        }

        if ($type === 'publishers') {
            return (Publisher::query()
                    ->active()
                    ->whereNotNull('slug')
                    ->where('slug', '!=', '')
                    ->distinct()
                    ->count('slug') * $locales) + $locales;
        }

        return 0;
    }


    private static function dateFromMaxUpdatedAt($value, Carbon $fallback): Carbon
    {
        return Carbon::make($value) ?: $fallback;
    }
}
