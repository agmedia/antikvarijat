<?php

namespace Tests\Unit;

use App\Helpers\Breadcrumb;
use App\Models\Front\Catalog\Product;
use App\Models\ProductReview;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ProductSchemaTest extends TestCase
{
    public function test_product_schema_omits_rating_without_approved_reviews(): void
    {
        $schema = (new Breadcrumb())->productBookSchema($this->product(), collect(), [
            'count' => 0,
            'average' => 0,
        ]);

        $this->assertSame(['Product', 'Book'], $schema['@type']);
        $this->assertSame('9780306406157', $schema['isbn']);
        $this->assertSame('9780306406157', $schema['gtin13']);
        $this->assertSame('http://antlaravel.test/#organization', $schema['offers']['seller']['@id']);
        $this->assertSame($schema['url'] . '#webpage', $schema['mainEntityOfPage']['@id']);
        $this->assertArrayNotHasKey('aggregateRating', $schema);
        $this->assertArrayNotHasKey('review', $schema);
    }

    public function test_product_schema_contains_only_real_review_values(): void
    {
        $review = new ProductReview([
            'reviewer_name' => 'Ana Horvat',
            'rating' => 5,
            'title' => 'Odličan primjerak',
            'body' => 'Knjiga je stigla u točno opisanom stanju.',
            'status' => ProductReview::STATUS_APPROVED,
            'approved_at' => Carbon::parse('2026-08-09 10:00:00'),
        ]);

        $schema = (new Breadcrumb())->productBookSchema(
            $this->product(),
            new Collection([$review]),
            ['count' => 1, 'average' => 5]
        );

        $this->assertSame(1, $schema['aggregateRating']['reviewCount']);
        $this->assertSame(5.0, (float) $schema['aggregateRating']['ratingValue']);
        $this->assertSame('Ana Horvat', $schema['review'][0]['author']['name']);
        $this->assertSame('Knjiga je stigla u točno opisanom stanju.', $schema['review'][0]['reviewBody']);
    }

    public function test_product_schema_contains_real_croatian_shipping_details(): void
    {
        config(['settings.free_shipping' => 70]);

        $shippingMethod = (object) [
            'code' => 'gls',
            'geo_zone' => 1,
            'status' => true,
            'data' => (object) [
                'price' => 5,
                'time' => '1-2 radna dana',
            ],
        ];
        $croatia = (object) [
            'id' => 1,
            'title' => 'Hrvatska',
            'state' => (object) ['2' => 'Croatia'],
        ];

        $schema = (new Breadcrumb())->productBookSchema(
            $this->product(),
            collect(),
            [],
            [$shippingMethod],
            [$croatia]
        );

        $this->assertSame('5.00', $schema['offers']['shippingDetails'][0]['shippingRate']['value']);
        $this->assertSame(
            'HR',
            $schema['offers']['shippingDetails'][0]['shippingDestination'][0]['addressCountry']
        );
    }

    private function product(): Product
    {
        $product = new Product([
            'name' => 'Primjer knjige',
            'description' => '<p>Stvarni opis knjige.</p>',
            'slug' => 'primjer-knjige',
            'url' => 'knjige/primjer-knjige',
            'sku' => 'SKU-1',
            'isbn' => '9780306406157',
            'price' => 15.5,
            'special' => 0,
            'special_from' => null,
            'special_to' => null,
            'quantity' => 1,
            'year' => '2020',
            'pages' => '240',
        ]);
        $product->setRelation('action', null);
        $product->setRelation('author', null);
        $product->setRelation('publisher', null);
        $product->setRelation('categories', collect());

        return $product;
    }
}
