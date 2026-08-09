<?php

namespace Tests\Feature;

use App\Services\ContractWithdrawalSettingsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotFoundSeoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        $this->mock(ContractWithdrawalSettingsService::class, function ($mock) {
            $mock->shouldReceive('get')->andReturn(['return_cost_policy' => 'consumer']);
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('title_en')->nullable();
            $table->string('slug')->nullable();
            $table->string('slug_en')->nullable();
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            $table->string('subgroup')->nullable();
        });
    }

    public function testFallbackReturnsARealNoindex404Response(): void
    {
        $response = $this->get('/putanja/koja/sigurno/ne/postoji');

        $response->assertNotFound();
        $response->assertSee('<meta name="robots" content="noindex,nofollow,noarchive">', false);
        $response->assertSee('Stranica nije pronađena - Antikvarijat Biblos');
    }

    public function testFramework404IsLocalizedAndNoindexForEnglishUrls(): void
    {
        $response = $this->get('/en/datoteka-koja-ne-postoji.xml');

        $response->assertNotFound();
        $response->assertSee('<html lang="en">', false);
        $response->assertSee('<meta name="robots" content="noindex,nofollow,noarchive">', false);
        $response->assertSee('Page not found - Antikvarijat Biblos');
        $response->assertSee('href="http://antlaravel.test/en/faq"', false);
    }
}
