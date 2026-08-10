<?php

namespace Tests\Feature;

use App\Mail\ProductReviewRequestMail;
use App\Models\Back\Orders\Order;
use App\Services\ProductReviewRequestService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SendProductReviewRequestsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->default(0);
            $table->unsignedInteger('order_status_id');
            $table->string('payment_fname');
            $table->string('payment_lname');
            $table->string('payment_email');
            $table->timestamp('checkout_processed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('order_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedInteger('status');
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
        });
        Schema::create('order_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->timestamps();
        });
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('product_id');
        });
        Schema::create('product_review_invitations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id')->unique();
            $table->char('token_hash', 64)->unique();
            $table->string('recipient_email');
            $table->string('recipient_name');
            $table->string('locale', 5)->default('hr');
            $table->timestamp('eligible_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_only_orders_on_the_exact_thirtieth_day_receive_one_request(): void
    {
        Carbon::setTestNow('2026-08-09 12:00:00');
        Mail::fake();
        config([
            'reviews.request_emails_enabled' => true,
            'reviews.request_delay_days' => 30,
            'reviews.request_max_attempts' => 3,
        ]);

        DB::table('products')->insert(['id' => 10, 'name' => 'Knjiga']);
        $this->insertOrder(1, 1, '2026-07-10 10:00:00');
        $this->insertOrder(2, 3, '2026-07-10 11:00:00');
        $this->insertOrder(3, 4, '2026-07-03 10:00:00');
        $this->insertOrder(4, 1, '2026-08-01 10:00:00');
        $this->insertOrder(5, 5, '2026-07-10 10:00:00');
        $this->insertOrder(6, 1, '2026-07-09 10:00:00');
        $this->insertOrder(7, 3, '2026-07-11 10:00:00');

        DB::table('order_history')->insert([
            'order_id' => 3,
            'status' => 4,
            'created_at' => '2026-07-10 12:00:00',
            'updated_at' => '2026-07-10 12:00:00',
        ]);

        $this->artisan('reviews:send-requests')->assertExitCode(0);

        $this->assertSame([1, 2, 3], DB::table('product_review_invitations')->orderBy('order_id')->pluck('order_id')->map(fn ($id) => (int) $id)->all());
        Mail::assertSent(ProductReviewRequestMail::class, 3);

        $this->artisan('reviews:send-requests')->assertExitCode(0);
        $this->assertSame(3, DB::table('product_review_invitations')->count());
        Mail::assertSent(ProductReviewRequestMail::class, 3);
    }

    public function test_each_normalized_email_address_receives_only_one_request(): void
    {
        Carbon::setTestNow('2026-08-09 12:00:00');
        Mail::fake();
        config([
            'reviews.request_emails_enabled' => true,
            'reviews.request_delay_days' => 30,
            'reviews.request_max_attempts' => 3,
        ]);

        DB::table('products')->insert(['id' => 10, 'name' => 'Knjiga']);
        $this->insertOrder(1, 1, '2026-07-10 10:00:00', 'Kupac@Example.test');
        $this->insertOrder(2, 1, '2026-07-10 11:00:00', ' kupac@example.test ');
        $this->insertOrder(3, 1, '2026-07-10 12:00:00', 'drugi@example.test');

        $this->artisan('reviews:send-requests')->assertExitCode(0);

        $this->assertSame([1, 3], DB::table('product_review_invitations')->orderBy('order_id')->pluck('order_id')->map(fn ($id) => (int) $id)->all());
        Mail::assertSent(ProductReviewRequestMail::class, 2);

        $duplicateResult = app(ProductReviewRequestService::class)->send(Order::query()->findOrFail(2));
        $this->assertSame(ProductReviewRequestService::STATUS_SKIPPED, $duplicateResult['status']);
        $this->assertSame('Poziv na ovu e-mail adresu već je poslan.', $duplicateResult['message']);
        Mail::assertSent(ProductReviewRequestMail::class, 2);

        $this->insertOrder(4, 1, '2026-07-10 13:00:00', 'KUPAC@example.test');
        $this->artisan('reviews:send-requests')->assertExitCode(0);

        $this->assertSame(2, DB::table('product_review_invitations')->count());
        Mail::assertSent(ProductReviewRequestMail::class, 2);
    }

    private function insertOrder(int $id, int $status, string $createdAt, ?string $email = null): void
    {
        DB::table('orders')->insert([
            'id' => $id,
            'user_id' => 0,
            'order_status_id' => $status,
            'payment_fname' => 'Kupac',
            'payment_lname' => (string) $id,
            'payment_email' => $email ?: "kupac{$id}@example.test",
            'checkout_processed_at' => $createdAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
        DB::table('order_products')->insert([
            'order_id' => $id,
            'product_id' => 10,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
