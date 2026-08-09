<?php

namespace Tests\Unit;

use App\Helpers\StructuredData;
use App\Models\Front\Blog;
use App\Models\Front\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class StructuredDataTest extends TestCase
{
    public function testSiteGraphConnectsStoreWebsiteAndCurrentPage(): void
    {
        config(['app.url' => 'https://www.antikvarijat-biblos.hr/']);

        $schema = StructuredData::siteGraph(
            'https://www.antikvarijat-biblos.hr/knjige/primjer',
            'Primjer knjige',
            'Opis knjige',
            'hr'
        );

        [$store, $website, $page] = $schema['@graph'];

        $this->assertSame('BookStore', $store['@type']);
        $this->assertSame('https://www.antikvarijat-biblos.hr/#organization', $store['@id']);
        $this->assertSame('https://www.antikvarijat-biblos.hr/#organization', $website['publisher']['@id']);
        $this->assertSame('https://www.antikvarijat-biblos.hr/#website', $page['isPartOf']['@id']);
        $this->assertSame('https://www.antikvarijat-biblos.hr/knjige/primjer#webpage', $page['@id']);
        $this->assertSame('09:00', $store['openingHoursSpecification'][0]['opens']);
    }

    public function testImageMimeTypeUsesTheActualFileExtension(): void
    {
        $this->assertSame('image/webp', StructuredData::imageMimeType('https://example.test/book.webp?v=2'));
        $this->assertSame('image/jpeg', StructuredData::imageMimeType('https://example.test/book.JPG'));
        $this->assertNull(StructuredData::imageMimeType('https://example.test/image'));
    }

    public function testSiteGraphCanDescribeACollectionPage(): void
    {
        $schema = StructuredData::siteGraph(
            'https://www.antikvarijat-biblos.hr/knjige',
            'Knjige',
            'Dostupne knjige',
            'hr',
            'CollectionPage'
        );

        $this->assertSame('CollectionPage', $schema['@graph'][2]['@type']);
    }

    public function testMerchantReturnPolicyUsesTheConfiguredReturnFeeResponsibility(): void
    {
        $consumerPolicy = StructuredData::merchantReturnPolicy(
            ['return_cost_policy' => 'consumer'],
            'https://www.antikvarijat-biblos.hr/forma-za-povrat-i-reklamacije'
        );
        $merchantPolicy = StructuredData::merchantReturnPolicy(
            ['return_cost_policy' => 'merchant'],
            'https://www.antikvarijat-biblos.hr/forma-za-povrat-i-reklamacije'
        );

        $this->assertSame(14, $consumerPolicy['merchantReturnDays']);
        $this->assertSame('HR', $consumerPolicy['applicableCountry']);
        $this->assertSame('https://schema.org/ReturnShippingFees', $consumerPolicy['returnFees']);
        $this->assertSame('https://schema.org/FreeReturn', $merchantPolicy['returnFees']);
    }

    public function testSiteGraphIncludesTheMerchantReturnPolicy(): void
    {
        $policy = StructuredData::merchantReturnPolicy(
            ['return_cost_policy' => 'consumer'],
            'https://www.antikvarijat-biblos.hr/forma-za-povrat-i-reklamacije'
        );
        $schema = StructuredData::siteGraph(
            'https://www.antikvarijat-biblos.hr/',
            'Antikvarijat Biblos',
            'Antikvarijat i online knjižara',
            'hr',
            'WebPage',
            $policy
        );

        $this->assertSame($policy, $schema['@graph'][0]['hasMerchantReturnPolicy']);
    }

    public function testOfferShippingDetailsUseOnlyPricedDeliveryMethodsWithKnownCountries(): void
    {
        config(['settings.free_shipping' => 70]);

        $croatia = (object) [
            'id' => 1,
            'title' => 'Hrvatska',
            'state' => (object) ['2' => 'Croatia'],
        ];
        $world = (object) [
            'id' => 3,
            'title' => 'World',
            'state' => (object) [],
        ];
        $methods = [
            $this->shippingMethod('gls', 1, 5, '1-2 radna dana'),
            $this->shippingMethod('gls_eu', 1, 3, '1-2 radna dana'),
            $this->shippingMethod('pickup', 1, 0, null),
            $this->shippingMethod('gls_world', 3, 0, '3-21 radna dana'),
        ];

        $details = StructuredData::offerShippingDetails($methods, [$croatia, $world], 20);

        $this->assertCount(2, $details);
        $this->assertSame('5.00', $details[0]['shippingRate']['value']);
        $this->assertSame('HR', $details[0]['shippingDestination'][0]['addressCountry']);
        $this->assertSame(1, $details[0]['deliveryTime']['transitTime']['minValue']);
        $this->assertSame(2, $details[0]['deliveryTime']['transitTime']['maxValue']);
        $this->assertSame('3.00', $details[1]['shippingRate']['value']);

        $freeDetails = StructuredData::offerShippingDetails($methods, [$croatia, $world], 70.01);
        $this->assertSame('0.00', $freeDetails[0]['shippingRate']['value']);
        $this->assertSame('0.00', $freeDetails[1]['shippingRate']['value']);
    }

    public function testItemListUsesTotalCountAndContinuousPaginatedPositions(): void
    {
        $paginator = new LengthAwarePaginator([
            [
                'name' => 'Treća knjiga',
                'url' => 'https://www.antikvarijat-biblos.hr/knjige/treca-knjiga',
                'thumb' => 'https://www.antikvarijat-biblos.hr/image/treca-thumb.webp',
            ],
            [
                'name' => 'Četvrta knjiga',
                'url' => 'https://www.antikvarijat-biblos.hr/knjige/cetvrta-knjiga',
            ],
        ], 7, 2, 2, [
            'path' => 'https://www.antikvarijat-biblos.hr/knjige',
        ]);

        $schema = StructuredData::itemList(
            'https://www.antikvarijat-biblos.hr/knjige?page=2',
            'Knjige',
            $paginator
        );

        $this->assertSame('ItemList', $schema['@type']);
        $this->assertSame(7, $schema['numberOfItems']);
        $this->assertSame(3, $schema['itemListElement'][0]['position']);
        $this->assertSame(4, $schema['itemListElement'][1]['position']);
        $this->assertSame(
            'https://www.antikvarijat-biblos.hr/knjige/treca-knjiga#product',
            $schema['itemListElement'][0]['item']['@id']
        );
        $this->assertArrayNotHasKey('@type', $schema['itemListElement'][0]['item']);
    }

    public function testInlineJsonCannotCloseTheScriptElement(): void
    {
        $json = StructuredData::toJson([
            'name' => '</script><script>alert("x")</script>',
        ]);

        $this->assertStringNotContainsString('</script>', $json);
        $this->assertStringContainsString('\\u003C', $json);
        $this->assertSame(
            '</script><script>alert("x")</script>',
            json_decode($json, true, 512, JSON_THROW_ON_ERROR)['name']
        );
    }

    public function testBlogPostingConnectsTheArticleToTheCanonicalPageAndSiteEntities(): void
    {
        config(['app.url' => 'https://www.antikvarijat-biblos.hr/']);
        $blog = new Blog();
        $blog->setRawAttributes([
            'title' => 'Naslov članka',
            'meta_description' => 'Kratak opis članka.',
            'description' => '<p>Tekst članka s više riječi.</p>',
            'image' => 'media/img/blog/clanak.jpg',
            'publish_date' => '2026-08-01 10:00:00',
            'created_at' => '2026-08-01 10:00:00',
            'updated_at' => '2026-08-09 12:00:00',
        ], true);
        $url = 'https://www.antikvarijat-biblos.hr/blog/naslov-clanka';

        $schema = StructuredData::blogPosting($blog, $url, 'hr');

        $this->assertSame('BlogPosting', $schema['@type']);
        $this->assertSame($url . '#article', $schema['@id']);
        $this->assertSame($url . '#webpage', $schema['mainEntityOfPage']['@id']);
        $this->assertSame('https://www.antikvarijat-biblos.hr/#organization', $schema['publisher']['@id']);
        $this->assertSame('hr', $schema['inLanguage']);
        $this->assertArrayHasKey('dateModified', $schema);
        $this->assertSame('Tekst članka s više riječi.', html_entity_decode(strip_tags($blog->description)));
    }

    public function testFaqPageContainsOnlyVisibleQuestionsWithAnswers(): void
    {
        $schema = StructuredData::faqPage(
            'https://www.antikvarijat-biblos.hr/faq',
            [
                ['title' => 'Kako naručiti?', 'description' => '<p>Dodajte artikl u <strong>košaricu</strong>.</p><ul><li>Potvrdite narudžbu.</li></ul>'],
                ['title' => 'Prazno pitanje', 'description' => ''],
            ],
            'hr'
        );

        $this->assertSame('FAQPage', $schema['@type']);
        $this->assertSame('https://www.antikvarijat-biblos.hr/faq#webpage', $schema['@id']);
        $this->assertCount(1, $schema['mainEntity']);
        $this->assertSame('Kako naručiti?', $schema['mainEntity'][0]['name']);
        $this->assertSame('Dodajte artikl u košaricu. Potvrdite narudžbu.', $schema['mainEntity'][0]['acceptedAnswer']['text']);
    }

    public function testContentPageTypeRecognizesAboutPageInEitherLocale(): void
    {
        $about = new Page();
        $about->setRawAttributes(['slug' => 'o-nama', 'slug_en' => 'about-us'], true);

        $terms = new Page();
        $terms->setRawAttributes(['slug' => 'opci-uvjeti-kupnje', 'slug_en' => 'terms'], true);

        $this->assertSame('AboutPage', StructuredData::contentPageType($about));
        $this->assertSame('WebPage', StructuredData::contentPageType($terms));
    }

    private function shippingMethod(string $code, int $geoZone, float $price, ?string $time): object
    {
        return (object) [
            'code' => $code,
            'geo_zone' => $geoZone,
            'status' => true,
            'data' => (object) [
                'price' => $price,
                'time' => $time,
            ],
        ];
    }
}
