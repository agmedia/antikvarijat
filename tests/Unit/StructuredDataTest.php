<?php

namespace Tests\Unit;

use App\Helpers\StructuredData;
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
}
