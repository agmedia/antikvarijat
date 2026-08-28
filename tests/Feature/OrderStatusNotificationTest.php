<?php

namespace Tests\Feature;

use App\Mail\StatusCanceled;
use App\Mail\StatusPaid;
use App\Models\Back\Orders\Order;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderStatusNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('order_status_id');
            $table->string('payment_email')->nullable();
            $table->string('payment_fname')->nullable();
            $table->string('payment_lname')->nullable();
            $table->string('shipping_fname')->nullable();
            $table->string('shipping_lname')->nullable();
            $table->string('locale', 5)->nullable();
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('order_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('user_id')->default(0);
            $table->unsignedInteger('status')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        // The single-order endpoint renders the new history row before
        // returning JSON, so these read-only relations need their tables.
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
        });
        Schema::create('settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code');
            $table->string('key');
            $table->text('value');
            $table->boolean('json')->default(false);
            $table->timestamps();
        });

        config([
            'mail.order_status_notifications_enabled' => true,
            'settings.order.status.new' => 1,
            'settings.order.status.paid' => 3,
            'settings.order.status.canceled' => 5,
        ]);

        Mail::fake();
    }

    protected function tearDown(): void
    {
        app()->setLocale((string) config('app.locale', 'hr'));
        parent::tearDown();
    }

    public function test_paid_and_canceled_subjects_include_the_order_id_in_both_locales(): void
    {
        $order = new Order();
        $order->setRawAttributes(['id' => 26031], true);

        $mailables = [
            StatusPaid::class => 'front.email.order_subject_paid',
            StatusCanceled::class => 'front.email.order_subject_canceled',
        ];

        foreach (['hr', 'en'] as $locale) {
            app()->setLocale($locale);

            foreach ($mailables as $mailableClass => $translationKey) {
                $subject = (new $mailableClass($order))->build()->subject;

                $this->assertSame(
                    trans($translationKey, ['order_id' => 26031], $locale),
                    $subject
                );
                $this->assertStringContainsString('26031', $subject);
            }
        }
    }

    public function test_single_order_actual_transition_to_paid_sends_paid_mail_once(): void
    {
        $this->insertOrder(201, 1);

        $this->postJson(route('api.order.status.change'), [
            'order_id' => 201,
            'status' => 3,
            'comment' => 'Uplata zaprimljena.',
        ])->assertOk();

        $this->assertSame(3, (int) DB::table('orders')->where('id', 201)->value('order_status_id'));
        Mail::assertSent(StatusPaid::class, function (StatusPaid $mail) {
            return (int) $mail->order->id === 201;
        });
        Mail::assertSent(StatusPaid::class, 1);
        Mail::assertNotSent(StatusCanceled::class);
    }

    public function test_single_order_actual_transition_to_canceled_sends_canceled_mail_once(): void
    {
        $this->insertOrder(204, 3);

        $this->postJson(route('api.order.status.change'), [
            'order_id' => 204,
            'status' => 5,
            'comment' => 'Narudžba otkazana.',
        ])->assertOk();

        $this->assertSame(5, (int) DB::table('orders')->where('id', 204)->value('order_status_id'));
        Mail::assertNotSent(StatusPaid::class);
        Mail::assertSent(StatusCanceled::class, function (StatusCanceled $mail) {
            return (int) $mail->order->id === 204;
        });
        Mail::assertSent(StatusCanceled::class, 1);
    }

    public function test_single_order_unchanged_paid_or_canceled_status_does_not_send_mail(): void
    {
        $this->insertOrder(202, 3);
        $this->insertOrder(203, 5);

        $this->postJson(route('api.order.status.change'), [
            'order_id' => 202,
            'status' => 3,
            'comment' => 'Bez promjene.',
        ])->assertOk();
        $this->postJson(route('api.order.status.change'), [
            'order_id' => 203,
            'status' => 5,
            'comment' => 'Bez promjene.',
        ])->assertOk();

        Mail::assertNothingSent();
    }

    public function test_bulk_paid_change_compares_each_orders_previous_status(): void
    {
        $this->insertOrder(301, 1);
        $this->insertOrder(302, 3);
        $this->insertOrder(303, 5);

        $this->postJson(route('api.order.status.change'), [
            'orders' => '[301,302,303]',
            'selected' => 3,
        ])->assertOk();

        $this->assertSame(
            [301, 303],
            Mail::sent(StatusPaid::class)
                ->map(fn (StatusPaid $mail) => (int) $mail->order->id)
                ->sort()
                ->values()
                ->all()
        );
        Mail::assertNotSent(StatusCanceled::class);
    }

    public function test_bulk_canceled_change_compares_each_orders_previous_status(): void
    {
        $this->insertOrder(304, 1);
        $this->insertOrder(305, 5);
        $this->insertOrder(306, 3);

        $this->postJson(route('api.order.status.change'), [
            'orders' => '[304,305,306]',
            'selected' => 5,
        ])->assertOk();

        $this->assertSame(
            [304, 306],
            Mail::sent(StatusCanceled::class)
                ->map(fn (StatusCanceled $mail) => (int) $mail->order->id)
                ->sort()
                ->values()
                ->all()
        );
        Mail::assertNotSent(StatusPaid::class);
        Mail::assertSent(StatusCanceled::class, 2);
    }

    private function insertOrder(int $id, int $status): void
    {
        DB::table('orders')->insert([
            'id' => $id,
            'order_status_id' => $status,
            'payment_email' => "buyer{$id}@example.test",
            'payment_fname' => 'Test',
            'payment_lname' => 'Buyer',
            'shipping_fname' => 'Test',
            'shipping_lname' => 'Buyer',
            'locale' => $id % 2 === 0 ? 'en' : 'hr',
            'total' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
