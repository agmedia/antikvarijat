<?php

namespace Tests\Unit;

use App\Models\Back\Catalog\Product\Product;
use PHPUnit\Framework\TestCase;

class ProductIsbnTest extends TestCase
{
    public function test_isbn_is_normalized_before_storage(): void
    {
        $this->assertSame('9780306406157', Product::normalizeIsbn('978-0-306-40615-7'));
        $this->assertSame('0306406152', Product::normalizeIsbn('0 306 40615 2'));
        $this->assertNull(Product::normalizeIsbn(''));
    }

    public function test_isbn_10_and_13_checksums_are_validated(): void
    {
        $this->assertTrue(Product::isValidIsbn('0-306-40615-2'));
        $this->assertTrue(Product::isValidIsbn('978-0-306-40615-7'));
        $this->assertFalse(Product::isValidIsbn('978-0-306-40615-8'));
        $this->assertFalse(Product::isValidIsbn('12345'));
    }
}
