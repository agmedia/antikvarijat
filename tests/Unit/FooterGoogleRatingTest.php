<?php

namespace Tests\Unit;

use Tests\TestCase;

class FooterGoogleRatingTest extends TestCase
{
    public function test_it_renders_the_google_rating_without_changing_the_maps_link(): void
    {
        app()->setLocale('hr');

        $html = view()->file(resource_path('views/front/layouts/partials/footer.blade.php'), [
            'products' => 20083,
            'customers' => 14355,
            'uvjeti_kupnje' => collect(),
            'googleRating' => [
                'rating' => 4.8,
                'review_count' => 127,
            ],
        ])->render();

        $this->assertStringContainsString('4,8', $html);
        $this->assertStringContainsString('(127)', $html);
        $this->assertStringContainsString('fa-star', $html);
        $this->assertStringContainsString('Google recenzije', $html);
        $this->assertStringContainsString('https://www.google.com/maps?cid=13117805627465473758', $html);
    }
}
