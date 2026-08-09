<?php

namespace Tests\Feature;

use App\Mail\AbandonedCartReminderMail;
use App\Models\AbandonedCartReminder;
use App\Models\Back\Orders\Order;
use App\Services\AbandonedCartReminderService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AbandonedCartReminderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->default(0);
            $table->unsignedInteger('order_status_id');
            $table->string('locale', 5)->nullable();
            $table->string('payment_fname')->nullable();
            $table->string('payment_lname')->nullable();
            $table->string('payment_email')->nullable();
            $table->string('shipping_state')->nullable();
            $table->timestamp('unfinished_at')->nullable();
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('image')->nullable();
        });
        Schema::create('order_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->string('name');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();
        });
        Schema::create('order_total', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->string('code');
            $table->string('title');
            $table->decimal('value', 15, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('order_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->string('lang', 5)->nullable();
            $table->timestamps();
        });
        Schema::create('order_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('user_id')->default(0);
            $table->unsignedInteger('status');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
        Schema::create('abandoned_cart_reminders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedTinyInteger('sequence');
            $table->timestamp('scheduled_for');
            $table->timestamp('sent_at')->nullable();
            $table->string('source', 20);
            $table->string('recipient_email');
            $table->string('locale', 5)->default('hr');
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamps();
            $table->unique(['order_id', 'sequence']);
        });

        DB::table('products')->insert([
            'id' => 10,
            'name' => 'Testna knjiga',
            'sku' => 'TEST-10',
            'image' => 'media/img/products/testna-knjiga.jpg',
        ]);

        config([
            'abandoned_cart.enabled' => true,
            'abandoned_cart.starts_at' => '2026-08-09 00:00:00',
            'abandoned_cart.delays_minutes' => [1 => 60, 2 => 1440],
            'abandoned_cart.max_reminders' => 2,
            'settings.images_domain' => 'https://images.example.test/',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_first_reminder_is_sent_at_sixty_minutes_and_never_for_old_orders(): void
    {
        Carbon::setTestNow('2026-08-09 14:00:00');
        Mail::fake();

        $this->insertOrder(1, 8, '2026-08-09 13:00:00', 'en');
        $this->insertOrder(2, 8, '2026-08-09 13:01:00', 'hr');
        $this->insertOrder(3, 8, '2026-08-08 12:00:00', 'hr');
        $this->insertOrder(4, 1, '2026-08-09 12:00:00', 'hr');

        $this->artisan('orders:send-abandoned-cart-reminders')->assertExitCode(0);

        $this->assertDatabaseHas('abandoned_cart_reminders', [
            'order_id' => 1,
            'sequence' => 1,
            'source' => AbandonedCartReminder::SOURCE_AUTOMATIC,
            'locale' => 'en',
        ]);
        $this->assertDatabaseMissing('abandoned_cart_reminders', ['order_id' => 2]);
        $this->assertDatabaseMissing('abandoned_cart_reminders', ['order_id' => 3]);
        $this->assertDatabaseMissing('abandoned_cart_reminders', ['order_id' => 4]);
        Mail::assertSent(AbandonedCartReminderMail::class, function (AbandonedCartReminderMail $mail) {
            return (int) $mail->order->id === 1
                && $mail->sequence === 1
                && $mail->locale === 'en';
        });

        $this->artisan('orders:send-abandoned-cart-reminders')->assertExitCode(0);
        $this->assertSame(1, DB::table('abandoned_cart_reminders')->count());
        Mail::assertSent(AbandonedCartReminderMail::class, 1);
    }

    public function test_second_reminder_is_due_exactly_next_day_at_original_time(): void
    {
        Carbon::setTestNow('2026-08-10 12:59:00');
        Mail::fake();
        $this->insertOrder(5, 8, '2026-08-09 13:00:00', 'hr');
        DB::table('abandoned_cart_reminders')->insert([
            'order_id' => 5,
            'sequence' => 1,
            'scheduled_for' => '2026-08-09 14:00:00',
            'sent_at' => '2026-08-09 14:00:00',
            'source' => 'automatic',
            'recipient_email' => 'kupac5@example.test',
            'locale' => 'hr',
            'created_at' => '2026-08-09 14:00:00',
            'updated_at' => '2026-08-09 14:00:00',
        ]);

        $service = app(AbandonedCartReminderService::class);
        $this->assertTrue($service->candidatesForSequence(2, 10)->isEmpty());

        Carbon::setTestNow('2026-08-10 13:00:00');
        $this->assertSame([5], $service->candidatesForSequence(2, 10)->pluck('id')->map(fn ($id) => (int) $id)->all());

        $this->artisan('orders:send-abandoned-cart-reminders')->assertExitCode(0);
        $this->assertDatabaseHas('abandoned_cart_reminders', [
            'order_id' => 5,
            'sequence' => 2,
            'source' => AbandonedCartReminder::SOURCE_AUTOMATIC,
        ]);
        Mail::assertSent(AbandonedCartReminderMail::class, fn (AbandonedCartReminderMail $mail) => $mail->sequence === 2);
    }

    public function test_admin_can_send_next_reminder_early_and_test_send_changes_no_records(): void
    {
        Carbon::setTestNow('2026-08-09 13:15:00');
        Mail::fake();
        $this->insertOrder(6, 8, '2026-08-09 13:00:00', 'hr');

        $service = app(AbandonedCartReminderService::class);
        $order = Order::query()->findOrFail(6);
        $state = $service->adminState($order);

        $this->assertTrue($state['available']);
        $this->assertSame(1, $state['next_sequence']);
        $service->send($order, 1, AbandonedCartReminder::SOURCE_MANUAL);

        $this->assertDatabaseHas('abandoned_cart_reminders', [
            'order_id' => 6,
            'sequence' => 1,
            'source' => AbandonedCartReminder::SOURCE_MANUAL,
        ]);
        $this->assertDatabaseHas('order_history', ['order_id' => 6, 'status' => 8]);

        $before = DB::table('abandoned_cart_reminders')->count();
        $service->sendTest($order, 'tomislav@agmedia.hr', 'en', 2);
        $this->assertSame($before, DB::table('abandoned_cart_reminders')->count());
        Mail::assertSent(AbandonedCartReminderMail::class, function (AbandonedCartReminderMail $mail) {
            return $mail->hasTo('tomislav@agmedia.hr')
                && $mail->locale === 'en'
                && $mail->sequence === 2;
        });
    }

    public function test_both_email_variants_render_in_the_checkout_language(): void
    {
        $this->insertOrder(7, 8, '2026-08-09 13:00:00', 'hr');
        $order = Order::query()->with(['products.product', 'totals'])->findOrFail(7);

        $croatian = (new AbandonedCartReminderMail($order, 'https://example.test/kosarica', 1))
            ->locale('hr')
            ->render();
        $english = (new AbandonedCartReminderMail($order, 'https://example.test/en/cart', 2))
            ->locale('en')
            ->render();

        $this->assertStringContainsString('Izgleda da je košarica ostala otvorena', $croatian);
        $this->assertStringContainsString('Vrati knjige u košaricu', $croatian);
        $this->assertStringContainsString('https://images.example.test/media/img/products/testna-knjiga.webp', $croatian);
        $this->assertStringContainsString('These books have not given up on you', $english);
        $this->assertStringContainsString('Restore books to my cart', $english);
    }

    private function insertOrder(int $id, int $status, string $createdAt, string $locale): void
    {
        DB::table('orders')->insert([
            'id' => $id,
            'user_id' => 0,
            'order_status_id' => $status,
            'locale' => $locale,
            'payment_fname' => 'Kupac',
            'payment_lname' => (string) $id,
            'payment_email' => "kupac{$id}@example.test",
            'shipping_state' => 'Croatia',
            'unfinished_at' => $createdAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
        DB::table('order_products')->insert([
            'order_id' => $id,
            'product_id' => 10,
            'name' => 'Testna knjiga',
            'quantity' => 1,
            'price' => 20,
            'total' => 20,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
        DB::table('order_total')->insert([
            'order_id' => $id,
            'code' => 'total',
            'title' => 'Ukupno',
            'value' => 20,
            'sort_order' => 1,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
