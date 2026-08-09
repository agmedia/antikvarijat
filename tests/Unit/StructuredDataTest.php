<?php

namespace Tests\Unit;

use App\Helpers\StructuredData;
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
}
