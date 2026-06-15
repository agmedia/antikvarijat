<?php

namespace App\Helpers;

use App\Models\Front\Catalog\Category;
use App\Models\Front\Catalog\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
    public function category($group, Category $cat = null, $subcat = null)
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


    /**
     * @param               $group
     * @param Category|null $cat
     * @param null          $subcat
     * @param Product|null  $prod
     *
     * @return $this
     */
    public function product($group, Category $cat = null, $subcat = null, Product $prod = null)
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
    public function productBookSchema(Product $prod = null)
    {
        if ($prod) {
            $unknownAuthor = LocaleHelper::isEnglish() ? 'Author' : 'Autor';
            $publisherLabel = LocaleHelper::isEnglish() ? 'Publisher' : 'Izdavačka kuća';
            $description = LocaleHelper::isEnglish()
                ? $prod->name . ' book by ' . (($prod->author) ? $prod->author->title : $unknownAuthor) . ', published in ' . ($prod->year ?: '...')
                : $prod->name . ' knjiga autora ' . (($prod->author) ? $prod->author->title : $unknownAuthor) . ' godine izdanja ' . ($prod->year ?: '...') . '. izdavača ' . (($prod->publisher) ? $prod->publisher->title : $publisherLabel);

            return [
                '@context' => 'https://schema.org/',
                '@type' => 'Book',
                'datePublished' => $prod->year ?: '...',
                'description' => $description,
                'image' => asset($prod->image),
                'name' => $prod->name,
                'url' => url($prod->url),
                'publisher' => [
                    '@type' => 'Organization', 
                    'name' => ($prod->publisher) ? $prod->publisher->title : $publisherLabel,
                ],
                'author' => ($prod->author) ? $prod->author->title : $unknownAuthor,
                'offers' => [
                    '@type' => 'Offer',
                    'priceCurrency' => 'EUR',
                    'price' => ($prod->special()) ? $prod->main_special : $prod->main_price,
                    'sku' => $prod->sku,
                    'availability' => ($prod->quantity) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'
                ],
            ];
        }
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
