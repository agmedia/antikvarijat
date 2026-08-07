<?php

namespace App\Models;

use App\Helpers\LocaleHelper;
use App\Models\Front\Catalog\Author;
use App\Models\Front\Catalog\Category;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Catalog\Publisher;
use App\Models\Front\Page;
use Illuminate\Support\Carbon;

/**
 * Class Sitemap
 * @package App\Models
 */
class Sitemap
{

    /**
     * @var string|null
     */
    private $sitemap;

    /**
     * @var array
     */
    private $response = [];


    /**
     * Sitemap constructor.
     *
     * @param string|null $sitemap
     */
    public function __construct(?string $sitemap = null)
    {
        $this->sitemap = $this->setSitemap($sitemap);
    }


    /**
     * @return string|null
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
     * @param string $sitemap
     *
     * @return array
     */
    private function setSitemap(?string $sitemap)
    {
        if ( ! $sitemap) {
            return $sitemap;
        }

        if ($sitemap == 'pages' || $sitemap == 'pages.xml') {
            return $this->getPages();
        }

        if ($sitemap == 'categories' || $sitemap == 'categories.xml') {
            return $this->getCategories();
        }

        if ($sitemap == 'products' || $sitemap == 'products.xml') {
            return $this->getProducts();
        }

        if ($sitemap == 'authors' || $sitemap == 'authors.xml') {
            return $this->getAuthors();
        }

        if ($sitemap == 'publishers' || $sitemap == 'publishers.xml') {
            return $this->getPublishers();
        }

        if ($sitemap == 'images' || $sitemap == 'img') {
            return $this->getImages();
        }
    }


    /**
     * @return array
     */
    private function getImages(): array
    {
        $products = Product::query()->active()->hasStock()->select('url', 'id', 'image')->with('images');

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

        foreach (LocaleHelper::locales() as $locale) {
            $this->addUrl(LocaleHelper::route('index', [], true, $locale), Carbon::now()->startOfMonth());
            $this->addUrl(LocaleHelper::route('kontakt', [], true, $locale), Carbon::now()->startOfYear());
            $this->addUrl(LocaleHelper::route('faq', [], true, $locale), Carbon::now()->startOfYear());
            $this->addUrl(LocaleHelper::route('contract-withdrawal.create', [], true, $locale), Carbon::now()->startOfYear());
            $this->addUrl(LocaleHelper::route('otkup.knjiga', [], true, $locale), Carbon::now()->startOfYear());
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

        return $this->response;
    }


    /**
     * @return array
     */
    private function getCategories()
    {
        $categories = Category::query()->active()->topList()->with('subcategories')->get();

        foreach ($categories as $category) {
            foreach (LocaleHelper::locales() as $locale) {
                $this->addUrl(
                    LocaleHelper::route('catalog.route', ['group' => $category->getRawOriginal('group'), 'cat' => $category], true, $locale),
                    $category->updated_at
                );
            }

            foreach ($category->subcategories()->get() as $subcategory) {
                foreach (LocaleHelper::locales() as $locale) {
                    $this->addUrl(
                        LocaleHelper::route('catalog.route', ['group' => $category->getRawOriginal('group'), 'cat' => $category, 'subcat' => $subcategory], true, $locale),
                        $subcategory->updated_at
                    );
                }
            }
        }

        return $this->response;
    }


    /**
     * @return array
     */
    private function getProducts()
    {
        $products = Product::query()
            ->active()
            ->hasStock()
            ->select('id', 'url', 'url_en', 'slug', 'slug_en', 'updated_at')
            ->with('categories')
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
        $authors = Author::query()->active()->select('id', 'slug', 'slug_en', 'url', 'updated_at')->get();

        foreach (LocaleHelper::locales() as $locale) {
            $this->addUrl(LocaleHelper::route('catalog.route.author', [], true, $locale), Carbon::now()->startOfMonth());
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
        $publishers = Publisher::query()->active()->select('id', 'slug', 'slug_en', 'url', 'updated_at')->get();

        foreach (LocaleHelper::locales() as $locale) {
            $this->addUrl(LocaleHelper::route('catalog.route.publisher', [], true, $locale), Carbon::now()->startOfMonth());
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
}
