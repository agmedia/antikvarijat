<?php

namespace Tests\Unit;

use App\Helpers\Metatags;
use App\Models\Front\Catalog\Author;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\TestCase;

class MetatagsTest extends TestCase
{
    public function testSearchPagesAreNoindexAndCanonicalizeWithoutTheQuery(): void
    {
        $request = $this->request('https://www.antikvarijat-biblos.hr/pretrazi?pojam=boskovic', 'pretrazi');

        $this->assertSame('noindex,follow', Metatags::robots($request));
        $this->assertSame('https://www.antikvarijat-biblos.hr/pretrazi', Metatags::canonical($request));
    }

    public function testFilteredCatalogPagesAreNoindexAndCanonicalizeToTheCleanUrl(): void
    {
        $request = $this->request(
            'https://www.antikvarijat-biblos.hr/knjige/knjizevnost?start=1900&page=2',
            'catalog.route'
        );

        $this->assertSame('noindex,follow', Metatags::robots($request));
        $this->assertSame(
            'https://www.antikvarijat-biblos.hr/knjige/knjizevnost',
            Metatags::canonical($request)
        );
    }

    /**
     * @dataProvider additionalCatalogFilterProvider
     */
    public function testAdditionalCatalogFiltersAreNoindex(string $parameter): void
    {
        $request = $this->request(
            'https://www.antikvarijat-biblos.hr/knjige?' . $parameter . '=primjer',
            'catalog.route'
        );

        $this->assertSame('noindex,follow', Metatags::robots($request));
        $this->assertSame('https://www.antikvarijat-biblos.hr/knjige', Metatags::canonical($request));
    }

    public function additionalCatalogFilterProvider(): array
    {
        return [
            'script' => ['pismo'],
            'condition' => ['stanje'],
            'binding' => ['uvez'],
        ];
    }

    public function testPaginatedContentKeepsItsPageInTheCanonicalUrl(): void
    {
        $request = $this->request(
            'https://www.antikvarijat-biblos.hr/blog?page=2&utm_source=newsletter',
            'catalog.route.blog'
        );

        $this->assertSame('index,follow,max-image-preview:large', Metatags::robots($request));
        $this->assertSame('https://www.antikvarijat-biblos.hr/blog?page=2', Metatags::canonical($request));
        $this->assertSame(['page' => 2], Metatags::canonicalQuery($request));
    }

    public function testEnglishPrivateRoutesRemainNoindex(): void
    {
        $request = $this->request('https://www.antikvarijat-biblos.hr/en/cart', 'en.kosarica');

        $this->assertSame('noindex,follow,noarchive', Metatags::robots($request));
    }

    public function testFilteredAuthorPathCanonicalizesToTheAuthorPage(): void
    {
        $author = new Author();
        $author->setRawAttributes([
            'id' => 1,
            'slug' => 'boskovic-ruder-josip',
            'slug_en' => null,
        ], true);

        $request = $this->request(
            'https://www.antikvarijat-biblos.hr/autor/boskovic-ruder-josip/knjizevnost',
            'catalog.route.author',
            ['author' => $author, 'cat' => 'knjizevnost']
        );

        $this->assertSame('noindex,follow', Metatags::robots($request));
        $this->assertSame(
            'http://antlaravel.test/autor/boskovic-ruder-josip',
            Metatags::canonical($request)
        );
    }

    private function request(string $uri, string $name, array $parameters = []): Request
    {
        $request = Request::create($uri);
        $route = new Route(['GET'], ltrim($request->path(), '/'), fn () => null);
        $route->name($name);
        $route->bind($request);

        foreach ($parameters as $key => $value) {
            $route->setParameter($key, $value);
        }

        $request->setRouteResolver(fn () => $route);

        return $request;
    }
}
