<?php

namespace Tests\Unit;

use App\Helpers\LandingPageStructuredData;
use Tests\TestCase;

class LandingPageStructuredDataTest extends TestCase
{
    public function testBookPurchaseServiceConnectsTheProviderAndCanonicalPage(): void
    {
        config(['app.url' => 'https://www.antikvarijat-biblos.hr/']);
        $url = 'https://www.antikvarijat-biblos.hr/otkup-knjiga';

        $schema = LandingPageStructuredData::bookPurchaseService(
            $url,
            'Otkup knjiga',
            'Pošaljite prijavu za otkup knjiga i časopisa.',
            'Otkup knjiga i časopisa',
            'hr'
        );

        $this->assertSame('Service', $schema['@type']);
        $this->assertSame($url . '#service', $schema['@id']);
        $this->assertSame($url . '#webpage', $schema['mainEntityOfPage']['@id']);
        $this->assertSame('https://www.antikvarijat-biblos.hr/#organization', $schema['provider']['@id']);
        $this->assertSame('Otkup knjiga i časopisa', $schema['serviceType']);
        $this->assertSame('hr', $schema['inLanguage']);
    }
}
