<?php

namespace Tests\Unit;

use App\Helpers\CatalogEntityStructuredData;
use App\Models\Front\Catalog\Author;
use App\Models\Front\Catalog\Publisher;
use Tests\TestCase;

class CatalogEntityStructuredDataTest extends TestCase
{
    public function testAuthorSchemaIdentifiesThePersonAndCanonicalCollectionPage(): void
    {
        $author = new Author();
        $author->setRawAttributes([
            'title' => 'Ivo Andrić',
            'description' => '<p>Dobitnik <strong>Nobelove nagrade</strong>.</p>',
        ], true);
        $url = 'https://www.antikvarijat-biblos.hr/autor/andric-ivo';

        $schema = CatalogEntityStructuredData::author($author, $url, 'hr');

        $this->assertSame('Person', $schema['@type']);
        $this->assertSame($url . '#person', $schema['@id']);
        $this->assertSame($url . '#webpage', $schema['mainEntityOfPage']['@id']);
        $this->assertSame('Dobitnik Nobelove nagrade.', $schema['description']);
        $this->assertSame('hr', $schema['inLanguage']);
    }

    public function testPublisherSchemaUsesSeoDescriptionAsFallback(): void
    {
        $publisher = new Publisher();
        $publisher->setRawAttributes([
            'title' => 'Matica hrvatska',
            'description' => '',
        ], true);
        $url = 'https://www.antikvarijat-biblos.hr/nakladnik/matica-hrvatska';

        $schema = CatalogEntityStructuredData::publisher(
            $publisher,
            $url,
            'hr',
            'Dostupne knjige nakladnika Matica hrvatska.'
        );

        $this->assertSame('Organization', $schema['@type']);
        $this->assertSame($url . '#publisher', $schema['@id']);
        $this->assertSame('Dostupne knjige nakladnika Matica hrvatska.', $schema['description']);
    }
}
