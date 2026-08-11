<?php

namespace Tests\Unit;

use App\Services\CustomerMetricsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerMetricsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('orders');
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_status_id');
            $table->string('payment_email')->nullable();
        });
    }

    public function test_it_counts_unique_registered_and_guest_buyers_from_valid_orders(): void
    {
        DB::table('orders')->insert([
            ['order_status_id' => 1, 'payment_email' => 'kupac@example.com'],
            ['order_status_id' => 3, 'payment_email' => ' KUPAC@example.com '],
            ['order_status_id' => 4, 'payment_email' => 'gost@example.com'],
            ['order_status_id' => 5, 'payment_email' => 'otkazan@example.com'],
            ['order_status_id' => 8, 'payment_email' => 'nedovrsen@example.com'],
            ['order_status_id' => 3, 'payment_email' => ''],
            ['order_status_id' => 3, 'payment_email' => null],
        ]);

        $this->assertSame(2, app(CustomerMetricsService::class)->uniqueBuyers());
    }
}
