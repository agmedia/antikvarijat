<?php

namespace App\Models;

use App\Helpers\Metatags;
use App\Models\Front\Catalog\Author;
use App\Models\Front\Catalog\Category;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Catalog\Publisher;
use Illuminate\Http\Request;

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
        return [
            'title'       => $product->name . ' knjige ' . (isset($product->author->title) ? $product->author->title : ''),
            'description' => 'Knjiga ' . $product->name . ' izdavača ' . (isset($product->author->title) ? $product->author->title : '') . ' godine izdanja ' . ($product->year ?: '') . ' i mjesta izdavanja ' . ($product->origin ?: '') . ' u Antikvarijatu Biblos.'
        ];
    }


    /**
     * @return array
     */
    public static function getAuthorData(Author $author, Category $cat = null, Category $subcat = null): array
    {
        $title = $author->title . ' knjige - Antikvarijat Biblos';
        $description = 'Knjige autora ' . $author->title . ' danas su jako popularne u svijetu. Bogati izbor knjiga autora ' . $author->title . ' uz brzu dostavu i sigurnu kupovinu.';

        // Check if there is meta title or description and set vars.
        if ($cat || $subcat) {
            return Metatags::noFollow();
        }

        return [
            'title'       => $title,
            'description' => $description
        ];
    }


    /**
     * @return array
     */
    public static function getPublisherData(Publisher $publisher, Category $cat = null, Category $subcat = null): array
    {
        $title = $publisher->title . ' knjige - Antikvarijat Biblos';
        $description = 'Ponuda knjiga nakladnika ' . $publisher->title . '. Knjige iz antikvarijata, naklade ' . $publisher->title . ' mogu biti u vašem domu uz brzu dostavu.';

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


    public static function getMetaTags(Request $request, $target = 'product')
    {
        $response = [];
        $data = $request->toArray();

        if ($target == 'filter') {
            if (array_key_exists('start', $data) || array_key_exists('end', $data) || array_key_exists('autor', $data) || array_key_exists('nakladnik', $data)) {
                array_push($response, Metatags::noFollow());
            }
        }

        if ($target == 'ap_filter') {
            if (array_key_exists('letter', $data)) {
                array_push($response, Metatags::noFollow());
            }
        }

        return $response;
    }


}
