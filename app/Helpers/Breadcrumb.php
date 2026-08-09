<?php

namespace App\Helpers;

use App\Models\Front\Catalog\Category;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Catalog\Author;
use App\Models\Front\Catalog\Publisher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class Breadcrumb
{

    /**
     * @var array
     */
    private $schema = [];

    /**
     * @var array
     */
    private $breadcrumbs = [];


    /**
     * Breadcrumb constructor.
     */
    public function __construct()
    {
        $this->setDefault();
    }


    /**
     * @param               $group
     * @param Category|null $cat
     * @param null          $subcat
     *
     * @return $this
     */
    public function category($group, ?Category $cat = null, $subcat = null)
    {
        if (isset($group) && $group) {
            $this->addGroup($group);

            if ($cat) {
                array_push($this->breadcrumbs, [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $cat->title,
                    'item' => LocaleHelper::route('catalog.route', ['group' => $group, 'cat' => $cat])
                ]);
            }

            if ($subcat) {
                array_push($this->breadcrumbs, [
                    '@type' => 'ListItem',
                    'position' => 4,
                    'name' => $subcat->title,
                    'item' => LocaleHelper::route('catalog.route', ['group' => $group, 'cat' => $cat, 'subcat' => $subcat])
                ]);
            }
        }

        return $this;
    }

    public function author(Author $author, ?Category $cat = null, ?Category $subcat = null): self
    {
        $this->breadcrumbs[] = [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => __('front.nav.authors'),
            'item' => LocaleHelper::route('catalog.route.author'),
        ];
        $this->breadcrumbs[] = [
            '@type' => 'ListItem',
            'position' => 3,
            'name' => $author->title,
            'item' => LocaleHelper::route('catalog.route.author', ['author' => $author]),
        ];

        if ($cat) {
            $this->breadcrumbs[] = [
                '@type' => 'ListItem',
                'position' => 4,
                'name' => $cat->title,
                'item' => LocaleHelper::route('catalog.route.author', ['author' => $author, 'cat' => $cat]),
            ];
        }

        if ($subcat) {
            $this->breadcrumbs[] = [
                '@type' => 'ListItem',
                'position' => 5,
                'name' => $subcat->title,
                'item' => LocaleHelper::route('catalog.route.author', [
                    'author' => $author,
                    'cat' => $cat,
                    'subcat' => $subcat,
                ]),
            ];
        }

        return $this;
    }

    public function publisher(Publisher $publisher, ?Category $cat = null, ?Category $subcat = null): self
    {
        $this->breadcrumbs[] = [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => __('front.nav.publishers'),
            'item' => LocaleHelper::route('catalog.route.publisher'),
        ];
        $this->breadcrumbs[] = [
            '@type' => 'ListItem',
            'position' => 3,
            'name' => $publisher->title,
            'item' => LocaleHelper::route('catalog.route.publisher', ['publisher' => $publisher]),
        ];

        if ($cat) {
            $this->breadcrumbs[] = [
                '@type' => 'ListItem',
                'position' => 4,
                'name' => $cat->title,
                'item' => LocaleHelper::route('catalog.route.publisher', ['publisher' => $publisher, 'cat' => $cat]),
            ];
        }

        if ($subcat) {
            $this->breadcrumbs[] = [
                '@type' => 'ListItem',
                'position' => 5,
                'name' => $subcat->title,
                'item' => LocaleHelper::route('catalog.route.publisher', [
                    'publisher' => $publisher,
                    'cat' => $cat,
                    'subcat' => $subcat,
                ]),
            ];
        }

        return $this;
    }


    /**
     * @param               $group
     * @param Category|null $cat
     * @param null          $subcat
     * @param Product|null  $prod
     *
     * @return $this
     */
    public function product($group, ?Category $cat = null, $subcat = null, ?Product $prod = null)
    {
        $this->category($group, $cat, $subcat);

        if ($prod) {
            $count = count($this->breadcrumbs) + 1;

            array_push($this->breadcrumbs, [
                '@type' => 'ListItem',
                'position' => $count,
                'name' => $prod->name,
                'item' => url($prod->url)
            ]);
        }

        return $this;
    }


    /**
     * @param Product|null $prod
     *
     * @return array
     */
    public function productBookSchema(?Product $prod = null, ?Collection $reviews = null, array $reviewStats = [])
    {
        if (! $prod) {
            return [];
        }

        $url = url($prod->url);
        $description = trim(strip_tags((string) $prod->description));
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => ['Product', 'Book'],
            '@id' => $url . '#product',
            'name' => $prod->name,
            'url' => $url,
            'description' => $description ?: $prod->name,
            'sku' => (string) $prod->sku,
            'inLanguage' => app()->getLocale(),
            'mainEntityOfPage' => [
                '@id' => $url . '#webpage',
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => $url,
                'priceCurrency' => 'EUR',
                'price' => number_format((float) $prod->special(), 2, '.', ''),
                'availability' => $prod->quantity > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/UsedCondition',
                'seller' => [
                    '@id' => rtrim((string) config('app.url'), '/') . '/#organization',
                ],
            ],
        ];

        if ($prod->image) {
            $schema['image'] = [$prod->image];
        }

        if ($prod->isbn) {
            $schema['isbn'] = (string) $prod->isbn;

            $isbnDigits = preg_replace('/\D+/', '', (string) $prod->isbn);
            if (strlen($isbnDigits) === 13) {
                $schema['gtin13'] = $isbnDigits;
            }
        }

        if ($prod->author && $this->hasMeaningfulEntityName($prod->author->title)) {
            $schema['author'] = [
                '@type' => 'Person',
                'name' => $prod->author->title,
                'url' => LocaleHelper::route('catalog.route.author', ['author' => $prod->author]),
            ];
        }

        if ($prod->publisher && $this->hasMeaningfulEntityName($prod->publisher->title)) {
            $schema['publisher'] = [
                '@type' => 'Organization',
                'name' => $prod->publisher->title,
                'url' => LocaleHelper::route('catalog.route.publisher', ['publisher' => $prod->publisher]),
            ];
        }

        if (preg_match('/^\d{4}$/', (string) $prod->year)) {
            $schema['datePublished'] = (string) $prod->year;
        }

        if (preg_match('/\d+/', (string) $prod->pages, $matches)) {
            $schema['numberOfPages'] = (int) $matches[0];
        }

        $reviewCount = (int) ($reviewStats['count'] ?? 0);
        if ($reviewCount > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => round((float) ($reviewStats['average'] ?? 0), 2),
                'reviewCount' => $reviewCount,
                'bestRating' => 5,
                'worstRating' => 1,
            ];

            $schema['review'] = ($reviews ?: collect())->map(function ($review) {
                return array_filter([
                    '@type' => 'Review',
                    'name' => $review->title ?: null,
                    'reviewBody' => $review->body,
                    'datePublished' => optional($review->approved_at ?: $review->created_at)->format('Y-m-d'),
                    'author' => [
                        '@type' => 'Person',
                        'name' => $review->reviewer_name,
                    ],
                    'reviewRating' => [
                        '@type' => 'Rating',
                        'ratingValue' => (int) $review->rating,
                        'bestRating' => 5,
                        'worstRating' => 1,
                    ],
                ], fn ($value) => $value !== null && $value !== '');
            })->values()->all();
        }

        return $schema;
    }

    private function hasMeaningfulEntityName($name): bool
    {
        return (bool) preg_match('/[\pL\pN]/u', trim((string) $name));
    }


    /**
     * @return array
     */
    public function resolve()
    {
        $this->schema['itemListElement'] = $this->breadcrumbs;

        return $this->schema;
    }


    /**
     *
     */
    private function setDefault()
    {
        $this->schema = [
            '@context' => 'https://schema.org/',
            '@type' => 'BreadcrumbList'
        ];

        array_push($this->breadcrumbs, [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => LocaleHelper::isEnglish() ? 'Home' : 'Naslovnica',
            'item' => LocaleHelper::route('index')
        ]);
    }


    /**
     * @param $group
     */
    public function addGroup($group)
    {
        array_push($this->breadcrumbs, [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => LocaleHelper::groupTitle($group),
            'item' => LocaleHelper::route('catalog.route', ['group' => $group])
        ]);
    }
}
