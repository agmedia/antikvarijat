<?php

namespace Tests\Unit;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Sitemap;
use Illuminate\Session\Middleware\StartSession;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    public function testSitemapNamesAndImageShardsAreParsedStrictly(): void
    {
        $this->assertSame(
            ['type' => 'products', 'shard' => 2],
            Sitemap::parseName('products-2.xml')
        );
        $this->assertSame(
            ['type' => 'authors', 'shard' => null],
            Sitemap::parseName('authors.xml')
        );
        $this->assertNull(Sitemap::parseName('unknown.xml'));
        $this->assertSame(3, Sitemap::parseImageShard('3.xml'));
        $this->assertNull(Sitemap::parseImageShard(null));
        $this->assertSame(0, Sitemap::parseImageShard('invalid'));
    }

    public function testShardCountKeepsEverySitemapBelowTheConfiguredHeadroom(): void
    {
        config(['settings.sitemap_max_urls' => 20000]);

        $this->assertSame(1, Sitemap::shardCountForUrlCount(0));
        $this->assertSame(1, Sitemap::shardCountForUrlCount(20000));
        $this->assertSame(2, Sitemap::shardCountForUrlCount(20001));
        $this->assertSame(3, Sitemap::shardCountForUrlCount(50001));
    }

    public function testSitemapIndexRendersLocationsAndLastModifiedDates(): void
    {
        $xml = view('front.layouts.partials.sitemap-index', [
            'items' => [[
                'loc' => 'https://www.antikvarijat-biblos.hr/sitemap/products-1.xml',
                'lastmod' => '2026-08-09T08:00:00+00:00',
            ]],
        ])->render();

        $this->assertStringContainsString('<sitemapindex', $xml);
        $this->assertStringContainsString(
            '<loc>https://www.antikvarijat-biblos.hr/sitemap/products-1.xml</loc>',
            $xml
        );
        $this->assertStringContainsString('<lastmod>2026-08-09T08:00:00+00:00</lastmod>', $xml);
    }

    public function testCoreListingPagesAreIncludedForBothLocales(): void
    {
        $hr = Sitemap::corePageUrls('hr');
        $en = Sitemap::corePageUrls('en');

        $this->assertSame(url('knjige'), $hr['books']);
        $this->assertSame(url('zemljovidi-i-vedute'), $hr['maps']);
        $this->assertSame(url('blog'), $hr['blog']);
        $this->assertSame(url('recenzije'), $hr['reviews']);
        $this->assertSame(url('izdvojeno/najtrazenije-ovaj-mjesec'), $hr['monthly_best_sellers']);
        $this->assertSame(url('snizenja'), $hr['sale']);

        $this->assertSame(url('en/books'), $en['books']);
        $this->assertSame(url('en/maps-and-views'), $en['maps']);
        $this->assertSame(url('en/blog'), $en['blog']);
        $this->assertSame(url('en/reviews'), $en['reviews']);
        $this->assertSame(url('en/featured/most-wanted-this-month'), $en['monthly_best_sellers']);
        $this->assertSame(url('en/sale'), $en['sale']);
    }

    public function testPublicSitemapRoutesDoNotStartSessionsOrAddCsrfCookies(): void
    {
        $sitemapRoute = app('router')->getRoutes()->getByName('sitemap');
        $imageRoute = app('router')->getRoutes()->getByName('image-sitemap');

        foreach ([$sitemapRoute, $imageRoute] as $route) {
            $this->assertContains(StartSession::class, $route->excludedMiddleware());
            $this->assertContains(VerifyCsrfToken::class, $route->excludedMiddleware());
        }
    }
}
