<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\ProductReviewBackfillAccess;
use Tests\TestCase;

class ProductReviewBackfillAccessTest extends TestCase
{
    public function test_only_configured_email_can_manage_product_review_backfills(): void
    {
        config(['reviews.backfill_admin_email' => 'tomislav@agmedia.hr']);

        $tomislav = (new User())->forceFill(['email' => 'Tomislav@agmedia.hr']);
        $otherAdmin = (new User())->forceFill(['email' => 'admin@example.test']);

        $this->assertTrue(ProductReviewBackfillAccess::allows($tomislav));
        $this->assertFalse(ProductReviewBackfillAccess::allows($otherAdmin));
        $this->assertFalse(ProductReviewBackfillAccess::allows(null));
    }
}
