<?php

namespace Tests\Feature;

use App\Mail\ProductReviewRequestMail;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductReviewBackfillTest extends TestCase
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
        Schema::create('product_review_backfills', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date_from');
            $table->date('date_to');
            $table->unsignedInteger('requested_limit');
            $table->unsignedSmallInteger('interval_seconds')->default(5);
            $table->unsignedInteger('eligible_count')->default(0);
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('processed_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
        Schema::create('product_review_backfill_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('backfill_id');
            $table->unsignedBigInteger('order_id');
            $table->string('status', 20)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['backfill_id', 'order_id']);
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_it_queues_and_throttles_old_review_requests_without_changing_daily_rule(): void
    {
        Carbon::setTestNow('2026-08-09 12:00:00');
        Mail::fake();
        config([
            'reviews.request_emails_enabled' => true,
            'reviews.request_delay_days' => 30,
            'reviews.request_max_attempts' => 3,
            'reviews.backfill_interval_options' => [1, 5],
        ]);

        DB::table('products')->insert(['id' => 10, 'name' => 'Knjiga']);
        $this->insertOrder(1, '2026-05-10 10:00:00');
        $this->insertOrder(2, '2026-06-15 10:00:00');
        $this->insertOrder(3, '2026-07-20 10:00:00');

        $this->artisan('reviews:backfill', [
            '--from' => '2026-05-01',
            '--to' => '2026-06-30',
            '--limit' => 1000,
            '--interval' => 1,
            '--yes' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('product_review_backfills', [
            'id' => 1,
            'total_count' => 2,
            'interval_seconds' => 1,
            'status' => 'pending',
        ]);
        $this->assertSame([1, 2], DB::table('product_review_backfill_items')->orderBy('id')->pluck('order_id')->map(fn ($id) => (int) $id)->all());
        Mail::assertNothingSent();

        $this->artisan('reviews:backfill', [
            '--from' => '2026-05-01',
            '--to' => '2026-06-30',
            '--limit' => 1000,
            '--interval' => 1,
            '--yes' => true,
        ])->assertExitCode(0);
        $this->assertSame(1, DB::table('product_review_backfills')->count());

        $this->artisan('reviews:process-backfills', ['--batch' => 1, '--max-seconds' => 1])->assertExitCode(0);
        Mail::assertSent(ProductReviewRequestMail::class, 1);
        $this->assertDatabaseHas('product_review_backfills', ['id' => 1, 'processed_count' => 1, 'status' => 'running']);

        $this->artisan('reviews:process-backfills', ['--batch' => 1, '--max-seconds' => 1])->assertExitCode(0);
        Mail::assertSent(ProductReviewRequestMail::class, 2);
        $this->assertDatabaseHas('product_review_backfills', [
            'id' => 1,
            'processed_count' => 2,
            'sent_count' => 2,
            'status' => 'completed',
        ]);

        $this->artisan('reviews:send-requests')->assertExitCode(0);
        Mail::assertSent(ProductReviewRequestMail::class, 2);
    }

    public function test_backfill_rejects_the_day_reserved_for_automatic_sending(): void
    {
        Carbon::setTestNow('2026-08-09 12:00:00');
        config([
            'reviews.request_delay_days' => 30,
            'reviews.backfill_interval_options' => [5],
        ]);

        $this->artisan('reviews:backfill', [
            '--from' => '2026-07-01',
            '--to' => '2026-07-10',
            '--limit' => 100,
            '--interval' => 5,
            '--dry-run' => true,
        ])->assertExitCode(1);
    }

    public function test_backfill_counts_and_sends_only_one_request_per_normalized_email(): void
    {
        Carbon::setTestNow('2026-08-09 12:00:00');
        Mail::fake();
        config([
            'reviews.request_emails_enabled' => true,
            'reviews.request_delay_days' => 30,
            'reviews.request_max_attempts' => 3,
            'reviews.backfill_interval_options' => [1],
        ]);

        DB::table('products')->insert(['id' => 10, 'name' => 'Knjiga']);
        $this->insertOrder(1, '2026-05-10 10:00:00', 'Kupac@Example.test');
        $this->insertOrder(2, '2026-05-11 10:00:00', ' kupac@example.test ');
        $this->insertOrder(3, '2026-05-12 10:00:00', 'drugi@example.test');

        $this->artisan('reviews:backfill', [
            '--from' => '2026-05-01',
            '--to' => '2026-05-31',
            '--limit' => 100,
            '--interval' => 1,
            '--yes' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('product_review_backfills', [
            'id' => 1,
            'eligible_count' => 2,
            'total_count' => 2,
        ]);
        $this->assertSame([1, 3], DB::table('product_review_backfill_items')->orderBy('id')->pluck('order_id')->map(fn ($id) => (int) $id)->all());

        $this->artisan('reviews:process-backfills', ['--batch' => 1, '--max-seconds' => 1])->assertExitCode(0);
        $this->artisan('reviews:process-backfills', ['--batch' => 1, '--max-seconds' => 1])->assertExitCode(0);

        Mail::assertSent(ProductReviewRequestMail::class, 2);
        $this->assertDatabaseHas('product_review_backfills', [
            'id' => 1,
            'sent_count' => 2,
            'status' => 'completed',
        ]);
    }

    private function insertOrder(int $id, string $createdAt, ?string $email = null): void
    {
        DB::table('orders')->insert([
            'id' => $id,
            'user_id' => 0,
            'order_status_id' => 1,
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
