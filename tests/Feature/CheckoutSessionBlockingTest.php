<?php

namespace Tests\Feature;

use Tests\TestCase;

class CheckoutSessionBlockingTest extends TestCase
{
    /**
     * @dataProvider checkoutReviewRoutes
     */
    public function test_checkout_review_routes_serialize_requests_from_the_same_session(string $routeName): void
    {
        $route = app('router')->getRoutes()->getByName($routeName);

        $this->assertNotNull($route);
        $this->assertSame(60, $route->locksFor());
        $this->assertSame(60, $route->waitsFor());
    }

    public function checkoutReviewRoutes(): array
    {
        return [
            ['pregled'],
            ['en.pregled'],
        ];
    }
}
