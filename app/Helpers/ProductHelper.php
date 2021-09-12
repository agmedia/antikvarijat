<?php

namespace App\Helpers;

use App\Models\Back\Catalog\Product\Product;
use App\Models\Front\Catalog\Category;
use Illuminate\Support\Str;

class ProductHelper
{

    /**
     * @param Product       $product
     * @param Category|null $category
     * @param Category|null $subcategory
     *
     * @return string
     */
    public static function categoryString(Product $product, Category $category = null, Category $subcategory = null)
    {
        if ( ! $category) {
            $category = $product->category();
        }

        if ( ! $subcategory) {
            $subcategory = $product->subcategory();
        }

        $catstring = '<span class="fs-xs ms-1"><a href="' . route('catalog.route', ['group' => Str::slug($category->group), 'cat' => $category->slug]) . '">' . $category->title . '</a> ';

        if ($subcategory) {
            $substring = '</span><span class="fs-xs ms-1"><a href="' . route('catalog.route',
                    ['group' => Str::slug($category->group), 'cat' => $category->slug, 'subcat' => $subcategory->slug]) . '">' . $subcategory->title . '</a></span>';

            return $catstring . $substring;
        }

        return $catstring;
    }


    /**
     * @param Product       $product
     * @param Category|null $category
     * @param Category|null $subcategory
     *
     * @return string
     */
    public static function url(Product $product, Category $category = null, Category $subcategory = null)
    {
        if ( ! $category) {
            $category = $product->category();
        }

        if ( ! $subcategory) {
            $subcategory = $product->subcategory();
        }

        if ($subcategory) {
            return Str::slug($category->group) . '/' . $category->slug . '/' . $subcategory->slug . '/' . $product->slug;
        }

        return Str::slug($category->group) . '/' . $category->slug . '/' . $product->slug;
    }
}