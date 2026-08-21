<?php

namespace Tests\Unit;

use App\Models\Front\Catalog\Author;
use App\Models\Front\Catalog\Product;
use App\Models\Seo;
use Tests\TestCase;

class SeoTest extends TestCase
{
    protected function tearDown(): void
    {
        app()->setLocale('hr');

        parent::tearDown();
    }

    public function testAuthorFallbackSeoUsesNaturalNameOrder(): void
    {
        app()->setLocale('hr');

        $seo = Seo::getAuthorData($this->author([
            'title' => 'Rudan Vedrana',
        ]));

        $this->assertSame('Vedrana Rudan – knjige i rabljena izdanja | Biblos', $seo['title']);
        $this->assertSame(
            'Vedrana Rudan: pronađite knjige u ponudi Antikvarijata Biblos. Pregledajte dostupna rabljena i rijetka izdanja te jednostavno naručite online.',
            $seo['description']
        );
    }

    public function testAuthorFallbackKeepsMultipleGivenNamesTogether(): void
    {
        app()->setLocale('hr');

        $seo = Seo::getAuthorData($this->author([
            'title' => 'Bošković Ruđer Josip',
        ]));

        $this->assertStringStartsWith('Ruđer Josip Bošković –', $seo['title']);
        $this->assertStringStartsWith('Ruđer Josip Bošković:', $seo['description']);
    }

    public function testAuthorManualSeoIsNotChanged(): void
    {
        app()->setLocale('hr');

        $seo = Seo::getAuthorData($this->author([
            'title' => 'Rudan Vedrana',
            'meta_title' => 'Ručno upisan naslov',
            'meta_description' => 'Ručno upisan opis.',
        ]));

        $this->assertSame('Ručno upisan naslov', $seo['title']);
        $this->assertSame('Ručno upisan opis.', $seo['description']);
    }

    public function testLegacyCopiedAuthorTitleUsesFallback(): void
    {
        app()->setLocale('hr');

        $seo = Seo::getAuthorData($this->author([
            'title' => 'Adagio Angelique',
            'meta_title' => 'Adagio Angelique',
        ]));

        $this->assertSame('Angelique Adagio – knjige i rabljena izdanja | Biblos', $seo['title']);
        $this->assertStringStartsWith('Angelique Adagio:', $seo['description']);
    }

    public function testWhitespaceOnlyAuthorSeoUsesFallback(): void
    {
        app()->setLocale('hr');

        $seo = Seo::getAuthorData($this->author([
            'title' => 'Rudan Vedrana',
            'meta_title' => '   ',
            'meta_description' => "\n\t",
        ]));

        $this->assertStringStartsWith('Vedrana Rudan –', $seo['title']);
        $this->assertStringStartsWith('Vedrana Rudan:', $seo['description']);
    }

    public function testEnglishAuthorFallbackUsesNaturalNameOrder(): void
    {
        app()->setLocale('en');

        $seo = Seo::getAuthorData($this->author([
            'title' => 'Rudan Vedrana',
        ]));

        $this->assertSame('Vedrana Rudan – used and rare books | Biblos', $seo['title']);
        $this->assertStringStartsWith('Vedrana Rudan:', $seo['description']);
    }

    public function testEnglishProductSeoDoesNotReuseCroatianManualMeta(): void
    {
        app()->setLocale('en');

        $product = new Product();
        $product->setRawAttributes([
            'name' => 'Probna knjiga',
            'name_en' => null,
            'year' => '1984',
            'meta_title' => 'Hrvatski ručno upisani SEO naslov proizvoda',
            'meta_title_en' => null,
            'meta_description' => 'Ovo je ručno upisan hrvatski opis koji se ne smije prikazati na engleskoj inačici proizvoda.',
            'meta_description_en' => null,
        ], true);
        $product->setRelation('author', $this->author(['title' => 'Rudan Vedrana']));

        $seo = Seo::getProductData($product);

        $this->assertNotSame($product->getRawOriginal('meta_title'), $seo['title']);
        $this->assertNotSame($product->getRawOriginal('meta_description'), $seo['description']);
        $this->assertStringContainsString('Buy Probna knjiga by Vedrana Rudan', $seo['description']);
        $this->assertLessThanOrEqual(65, mb_strlen($seo['title']));
        $this->assertLessThanOrEqual(160, mb_strlen($seo['description']));
    }

    public function testProductManualSeoIsPreserved(): void
    {
        app()->setLocale('hr');

        $product = new Product();
        $product->setRawAttributes([
            'name' => 'Probna knjiga',
            'meta_title' => 'Kratki ručni naslov',
            'meta_description' => 'Kratki ručni opis.',
        ], true);

        $seo = Seo::getProductData($product);

        $this->assertSame('Kratki ručni naslov', $seo['title']);
        $this->assertSame('Kratki ručni opis.', $seo['description']);
    }

    public function testCatalogEntityIsIndexableWhenItHasAnAvailableProduct(): void
    {
        $thinAuthor = $this->author([
            'title' => 'Rudan Vedrana',
            'description' => 'Kratak opis.',
        ]);
        $this->assertFalse(Seo::shouldIndexCatalogEntity($thinAuthor, 0, 'hr'));
        $this->assertTrue(Seo::shouldIndexCatalogEntity($thinAuthor, 1, 'hr'));
        $this->assertTrue(Seo::shouldIndexCatalogEntity($thinAuthor, 2, 'hr'));
    }

    private function author(array $attributes): Author
    {
        $author = new Author();
        $author->setRawAttributes(array_merge([
            'title' => null,
            'title_en' => null,
            'meta_title' => null,
            'meta_title_en' => null,
            'meta_description' => null,
            'meta_description_en' => null,
            'description' => null,
            'description_en' => null,
        ], $attributes), true);

        return $author;
    }
}
