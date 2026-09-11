<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageBoxNowPromotionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_box_now_promotion_is_visible_through_december_first(): void
    {
        Carbon::setTestNow('2026-12-01 23:59:59');

        $this->get('/')
            ->assertOk()
            ->assertSee('<h2 class="display-6 from-bottom">Besplatna BOX NOW dostava</h2>', false)
            ->assertSee('media/img/box-now-besplatna-dostava.webp');
    }

    public function test_box_now_promotion_is_hidden_after_december_first(): void
    {
        Carbon::setTestNow('2026-12-02 00:00:00');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('<h2 class="display-6 from-bottom">Besplatna BOX NOW dostava</h2>', false)
            ->assertDontSee('media/img/box-now-besplatna-dostava.webp');
    }
}
