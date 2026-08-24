<?php

namespace App\Models;

use App\Helpers\Helper;
use App\Models\Back\Orders\Order;
use App\Models\Front\Blog;
use App\Models\Front\Catalog\Product;
use Darryldecode\Cart\CartCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use function Livewire\str;

/**
 * Class Sitemap
 * @package App\Models
 */
class TagManager
{

    /**
     * @param Order $order
     *
     * @return array
     */
    public static function getGoogleSuccessDataLayer(Order $order)
    {
        $products = [];
        $shipping = 0;
        $tax      = 0;

        foreach ($order->products as $product) {
            if ($product->real) {
                $products[] = static::getGoogleProductDataLayer($product->real);
            }
        }

        if (Schema::hasTable('gift_vouchers')) {
            foreach ($order->giftVouchers as $voucher) {
                $products[] = [
                    'item_id' => 'POKLON-BON-' . number_format((float) $voucher->initial_amount, 2, '.', ''),
                    'item_name' => $voucher->locale === 'en' ? 'Gift voucher' : 'Poklon bon',
                    'price' => (float) $voucher->initial_amount,
                    'currency' => 'EUR',
                    'discount' => 0.0,
                    'item_category' => $voucher->locale === 'en' ? 'Gift voucher' : 'Poklon bon',
                    'item_category2' => $voucher->locale === 'en' ? 'Digital gift' : 'Digitalni poklon',
                    'quantity' => 1,
                ];
            }
        }

        foreach ($order->totals()->get() as $total) {
            if ($total->code == 'subtotal') {
                $tax += $total->value - ($total->value / 1.05);
            }
            if ($total->code == 'shipping') {
                $tax      += $total->value - ($total->value / 1.25);
                $shipping = $total->value;
            }
        }

        $data = [
            'event'     => 'purchase',
            'ecommerce' => [
                'transaction_id' => (string) $order->id,
                'affiliation'    => 'Antikvarijat Biblos webshop',
                'value'          => (float) $order->total,
                'tax'            => (float) number_format($tax, 2),
                'shipping'       => (float) number_format($shipping, 2),
                'currency'       => 'EUR',
                'items'          => $products
            ],
        ];

        return $data;
    }


    /**
     * @param Product $product
     *
     * @return array
     */
    public static function getGoogleProductDataLayer(Product $product): array
    {
        $discount = 0;

        if ($product->main_price > $product->main_special) {
            $discount = Helper::calculateDiscount($product->main_price, $product->main_special);
        }

        $item = [
            'item_id'        => $product->sku,
            'item_name'      => $product->name,
            'price'          => (float) str_replace(',', '.', $product->main_price),
            'currency'       => 'EUR',
            'discount'       => (float) number_format($discount, 2),
            'item_category'  => $product->category() ? $product->category()->title : '',
            'item_category2' => $product->subcategory() ? $product->subcategory()->title : '',
            'quantity'       => 1,
        ];

        return $item;
    }


    /**
     * @param CartCollection $cart_collection
     *
     * @return array
     */
    public static function getGoogleCartDataLayer(array $cart_collection): array
    {
        $items = [];

        foreach ($cart_collection['items'] as $item) {
            $items[] = $item->associatedModel->dataLayer;
        }

        return $items;
    }


    /**
     * @param Blog $blog
     *
     * @return array
     */
    public static function getGoogleBlogDataLayer(Blog $blog): array
    {
        $published = $blog->publish_date;

        if ( ! $published) {
            $published = $blog->created_at;
        }

        $item = [
            '@context'      => 'https://schema.org',
            '@type'         => 'BlogPosting',
            'headline'      => $blog->title,
            'image'         => $blog->image,
            'description'   => $blog->meta_description,
            'author'        => static::getAuthorObject(),
            'publisher'     => static::getAuthorObject(),
            'datePublished' => Carbon::make($published)->format('Y-m-d'),
            'dateCreated'   => Carbon::make($blog->created_at)->format('Y-m-d')
        ];

        return $item;
    }

    /*******************************************************************************
    *                                Copyright : AGmedia                           *
    *                              email: filip@agmedia.hr                         *
    *******************************************************************************/

    /**
     * @return string[]
     */
    private static function getAuthorObject(): array
    {
        return [
            "@type" => "Organization",
            "name"  => "Antikvarijat Biblos",
            "url"   => "https://www.antikvarijat-biblos.hr/"
        ];
    }

}
