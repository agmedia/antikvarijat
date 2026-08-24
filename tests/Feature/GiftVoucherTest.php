<?php

namespace Tests\Feature;

use App\Exceptions\GiftVoucherUnavailableException;
use App\Mail\GiftVoucherDelivered;
use App\Models\Back\Orders\Order;
use App\Models\Back\Settings\Settings;
use App\Models\Front\AgCart;
use App\Models\Front\Checkout\Payment\GiftVoucher as GiftVoucherPayment;
use App\Models\GiftVoucher;
use App\Models\GiftVoucherRedemption;
use App\Services\GiftVoucherService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GiftVoucherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
        Cache::flush();

        // This also clears Settings' request-level static caches between tests.
        Settings::set('testing', 'gift-vouchers', '1', false);

        config([
            'gift_vouchers.code_prefix' => 'BIBLOS-',
            'gift_vouchers.code_length' => 10,
            'gift_vouchers.reservation_minutes' => 180,
            'gift_vouchers.emails_enabled' => true,
            'settings.special_action.start' => null,
        ]);
    }

    public function test_front_routes_exist_but_are_not_linked_from_navigation_yet(): void
    {
        $this->assertSame(30, app(GiftVoucherService::class)->normalizeAmount(null));

        $croatian = Route::getRoutes()->getByName('poklon-bon.create');
        $english = Route::getRoutes()->getByName('en.poklon-bon.create');
        $croatianStore = Route::getRoutes()->getByName('poklon-bon.store');
        $englishStore = Route::getRoutes()->getByName('en.poklon-bon.store');

        $this->assertNotNull($croatian);
        $this->assertNotNull($english);
        $this->assertNotNull($croatianStore);
        $this->assertNotNull($englishStore);
        $this->assertSame('poklon-bon', $croatian->uri());
        $this->assertSame('en/gift-voucher', $english->uri());
        $this->assertContains('POST', $croatianStore->methods());
        $this->assertContains('POST', $englishStore->methods());

        foreach ([
            resource_path('views/front/layouts/partials/header.blade.php'),
            resource_path('views/front/layouts/partials/footer.blade.php'),
            resource_path('views/back/layouts/partials/sidebar.blade.php'),
        ] as $navigationFile) {
            $source = file_get_contents($navigationFile);

            $this->assertStringNotContainsString('poklon-bon', $source);
            $this->assertStringNotContainsString('gift-voucher', $source);
        }

        $admin = Route::getRoutes()->getByName('gift-vouchers.index');

        $this->assertNotNull($admin);
        $this->assertContains('auth:sanctum', $admin->gatherMiddleware());
        $this->assertContains('not.editor', $admin->gatherMiddleware());
    }

    public function test_gift_voucher_purchase_must_remain_in_a_separate_cart(): void
    {
        $service = app(GiftVoucherService::class);
        $giftCart = new AgCart('gift-voucher-test-cart');
        $giftRequest = $service->buildCartItemRequest([
            'amount' => 50,
            'recipient_name' => 'Ivan Primatelj',
            'recipient_email' => 'ivan@example.test',
            'sender_name' => 'Ana Pošiljatelj',
            'message' => 'Sretan rođendan!',
            'locale' => 'hr',
        ]);

        $giftResponse = $giftCart->add($giftRequest);

        $this->assertTrue($giftResponse['has_gift_voucher']);
        $this->assertTrue($giftResponse['gift_voucher_only']);
        $this->assertSame(1, $giftResponse['count']);
        $this->assertSame(50.0, (float) $giftResponse['total']);

        $mixedResponse = $giftCart->add(['item' => ['id' => 991, 'quantity' => 1]]);

        $this->assertArrayHasKey('error', $mixedResponse);
        $this->assertSame(__('front.gift_voucher.errors.mixed_cart'), $mixedResponse['error']);

        Cart::session('regular-product-test-cart')->add([
            'id' => 992,
            'name' => 'Obična knjiga',
            'price' => 12.50,
            'quantity' => 1,
            'attributes' => ['path' => '/obicna-knjiga'],
        ]);
        $regularCart = new AgCart('regular-product-test-cart');

        $reverseMixedResponse = $regularCart->add($giftRequest);

        $this->assertArrayHasKey('error', $reverseMixedResponse);
        $this->assertSame(__('front.gift_voucher.errors.mixed_cart'), $reverseMixedResponse['error']);
        $this->assertFalse(Cart::session('regular-product-test-cart')->has(GiftVoucherService::CART_ITEM_ID));
        $this->assertTrue(Cart::session('regular-product-test-cart')->has(992));
    }

    public function test_unpaid_purchase_cannot_issue_or_email_a_voucher(): void
    {
        Mail::fake();
        $service = app(GiftVoucherService::class);
        $order = $this->createOrder(config('settings.order.status.unfinished'), 50);

        $service->syncOrder($order->id, $this->giftPurchaseOrderData($service, 50));
        $service->completeCheckout($order);

        $voucher = GiftVoucher::query()->where('purchase_order_id', $order->id)->firstOrFail();
        $this->assertSame(GiftVoucher::STATUS_PENDING, $voucher->status);
        $this->assertNull($voucher->issued_at);
        $this->assertNull($voucher->email_sent_at);
        $this->assertNull($voucher->getRawOriginal('code_hash'));
        $this->assertNull($voucher->getRawOriginal('code_ciphertext'));
        Mail::assertNothingSent();

        // Even an unpaid order accidentally moved to "new" must not create a code.
        $order->update(['order_status_id' => config('settings.order.status.new')]);
        $service->completeCheckout($order->fresh());

        $voucher->refresh();
        $this->assertSame(GiftVoucher::STATUS_PENDING, $voucher->status);
        $this->assertNull($voucher->code);
        Mail::assertNothingSent();
    }

    public function test_paid_purchase_is_issued_once_and_code_is_encrypted_at_rest(): void
    {
        Mail::fake();
        $service = app(GiftVoucherService::class);
        $order = $this->createOrder(config('settings.order.status.paid'), 80);

        $service->syncOrder($order->id, $this->giftPurchaseOrderData($service, 80));
        $service->completeCheckout($order);

        $voucher = GiftVoucher::query()->where('purchase_order_id', $order->id)->firstOrFail();
        $plainCode = $voucher->code;

        $this->assertSame(GiftVoucher::STATUS_ACTIVE, $voucher->status);
        $this->assertNotNull($voucher->issued_at);
        $this->assertNotNull($voucher->email_sent_at);
        $this->assertMatchesRegularExpression('/^BIBLOS-[A-Z0-9]{10}$/', $plainCode);
        $this->assertSame(substr($plainCode, -6), $voucher->code_suffix);

        $stored = DB::table('gift_vouchers')->where('id', $voucher->id)->first();
        $this->assertSame(hash('sha256', $plainCode), $stored->code_hash);
        $this->assertNotSame($plainCode, $stored->code_ciphertext);
        $this->assertStringNotContainsString($plainCode, $stored->code_ciphertext);

        $service->completeCheckout($order->fresh());

        $this->assertSame($plainCode, $voucher->fresh()->code);
        Mail::assertSent(GiftVoucherDelivered::class, 1);
        Mail::assertSent(GiftVoucherDelivered::class, function (GiftVoucherDelivered $mail) {
            return $mail->hasTo('ivan@example.test');
        });
    }

    public function test_partial_reservations_are_idempotent_and_release_or_redeem_the_exact_amount(): void
    {
        $service = app(GiftVoucherService::class);
        $voucher = $this->createActiveVoucher(100, 'BIBLOS-PARTIAL01');
        $firstOrder = $this->createOrder(config('settings.order.status.unfinished'), 70);

        $service->syncOrder($firstOrder->id, $this->redemptionOrderData($voucher, 30));
        $service->syncOrder($firstOrder->id, $this->redemptionOrderData($voucher, 30));

        $this->assertSame(70.0, (float) $voucher->fresh()->balance);
        $this->assertDatabaseHas('gift_voucher_redemptions', [
            'gift_voucher_id' => $voucher->id,
            'order_id' => $firstOrder->id,
            'amount' => 30,
            'status' => GiftVoucherRedemption::STATUS_RESERVED,
        ]);

        $service->handleStatusChange($firstOrder, config('settings.order.status.canceled'));

        $this->assertSame(100.0, (float) $voucher->fresh()->balance);
        $this->assertDatabaseHas('gift_voucher_redemptions', [
            'gift_voucher_id' => $voucher->id,
            'order_id' => $firstOrder->id,
            'status' => GiftVoucherRedemption::STATUS_RELEASED,
            'release_reason' => 'order_status_' . config('settings.order.status.canceled'),
        ]);

        $secondOrder = $this->createOrder(config('settings.order.status.paid'), 60);
        $service->syncOrder($secondOrder->id, $this->redemptionOrderData($voucher, 40));
        $service->completeCheckout($secondOrder);
        $service->completeCheckout($secondOrder->fresh());

        $this->assertSame(60.0, (float) $voucher->fresh()->balance);
        $this->assertSame(GiftVoucher::STATUS_ACTIVE, $voucher->fresh()->status);
        $this->assertDatabaseHas('gift_voucher_redemptions', [
            'gift_voucher_id' => $voucher->id,
            'order_id' => $secondOrder->id,
            'amount' => 40,
            'status' => GiftVoucherRedemption::STATUS_REDEEMED,
        ]);
    }

    public function test_a_second_order_cannot_reserve_more_than_the_remaining_balance(): void
    {
        $service = app(GiftVoucherService::class);
        $voucher = $this->createActiveVoucher(50, 'BIBLOS-BALANCE01');
        $firstOrder = $this->createOrder(config('settings.order.status.unfinished'), 5);
        $secondOrder = $this->createOrder(config('settings.order.status.unfinished'), 10);

        $service->syncOrder($firstOrder->id, $this->redemptionOrderData($voucher, 45));

        try {
            $service->syncOrder($secondOrder->id, $this->redemptionOrderData($voucher, 10));
            $this->fail('Reservation above the remaining balance should have failed.');
        } catch (GiftVoucherUnavailableException $exception) {
            $this->assertSame(__('front.gift_voucher.errors.balance_changed'), $exception->getMessage());
        }

        $this->assertSame(5.0, (float) $voucher->fresh()->balance);
        $this->assertSame(1, GiftVoucherRedemption::query()->count());
        $this->assertDatabaseMissing('gift_voucher_redemptions', [
            'gift_voucher_id' => $voucher->id,
            'order_id' => $secondOrder->id,
        ]);
    }

    public function test_full_coverage_uses_internal_payment_and_finishes_only_with_a_reservation(): void
    {
        $service = app(GiftVoucherService::class);
        $voucher = $this->createActiveVoucher(25, 'BIBLOS-FULLCOVER');
        $order = $this->createOrder(config('settings.order.status.unfinished'), 0, GiftVoucherService::PAYMENT_CODE);

        $service->syncOrder($order->id, $this->redemptionOrderData($voucher, 25));

        $payment = new GiftVoucherPayment($order);
        $this->assertTrue($payment->finishOrder($order));
        $this->assertTrue($payment->finishOrder($order->fresh()));
        $this->assertSame((int) config('settings.order.status.paid'), (int) $order->fresh()->order_status_id);
        $this->assertSame(0.0, (float) $voucher->fresh()->balance);
        $this->assertSame(GiftVoucher::STATUS_EXHAUSTED, $voucher->fresh()->status);
        $this->assertDatabaseHas('gift_voucher_redemptions', [
            'gift_voucher_id' => $voucher->id,
            'order_id' => $order->id,
            'amount' => 25,
            'status' => GiftVoucherRedemption::STATUS_REDEEMED,
        ]);

        $uncoveredOrder = $this->createOrder(
            config('settings.order.status.unfinished'),
            1,
            GiftVoucherService::PAYMENT_CODE
        );

        $this->assertFalse((new GiftVoucherPayment($uncoveredOrder))->finishOrder($uncoveredOrder));
        $this->assertSame(
            (int) config('settings.order.status.unfinished'),
            (int) $uncoveredOrder->fresh()->order_status_id
        );
    }

    private function giftPurchaseOrderData(GiftVoucherService $service, int $amount): array
    {
        return [
            'address' => [
                'fname' => 'Ana',
                'lname' => 'Kupac',
                'email' => 'ana@example.test',
            ],
            'cart' => [
                'items' => [
                    $service->buildCartItem([
                        'amount' => $amount,
                        'recipient_name' => 'Ivan Primatelj',
                        'recipient_email' => 'ivan@example.test',
                        'sender_name' => 'Ana Kupac',
                        'message' => 'Uživaj u čitanju!',
                        'locale' => 'hr',
                    ]),
                ],
                'detail_con' => [],
            ],
        ];
    }

    private function redemptionOrderData(GiftVoucher $voucher, float $amount): array
    {
        return [
            'cart' => [
                'items' => [],
                'detail_con' => [[
                    'name' => 'Poklon bon',
                    'type' => 'gift_voucher',
                    'target' => 'total',
                    'value' => '-' . number_format($amount, 2, '.', ''),
                    'attributes' => [
                        'type' => 'gift_voucher',
                        'description' => $voucher->code,
                        'gift_voucher_id' => $voucher->id,
                    ],
                ]],
            ],
        ];
    }

    private function createOrder(int $status, float $total, ?string $paymentCode = null): Order
    {
        return Order::query()->create([
            'order_status_id' => $status,
            'total' => $total,
            'payment_code' => $paymentCode,
        ]);
    }

    private function createActiveVoucher(float $amount, string $code): GiftVoucher
    {
        $voucher = new GiftVoucher([
            'initial_amount' => $amount,
            'balance' => $amount,
            'currency' => 'EUR',
            'recipient_name' => 'Ivan Primatelj',
            'recipient_email' => 'ivan@example.test',
            'sender_name' => 'Ana Kupac',
            'locale' => 'hr',
            'status' => GiftVoucher::STATUS_ACTIVE,
            'issued_at' => now(),
        ]);
        $voucher->storeCode($code);
        $voucher->save();

        return $voucher;
    }

    private function createSchema(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('code');
            $table->string('key');
            $table->text('value');
            $table->boolean('json')->default(false);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('order_status_id');
            $table->decimal('total', 15, 4)->default(0);
            $table->string('payment_code')->nullable();
            $table->timestamps();
        });

        Schema::create('gift_vouchers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('purchase_order_id')->nullable()->index();
            $table->string('cart_item_key', 64)->nullable();
            $table->string('code_hash', 64)->nullable()->unique();
            $table->text('code_ciphertext')->nullable();
            $table->string('code_suffix', 10)->nullable()->index();
            $table->decimal('initial_amount', 15, 4);
            $table->decimal('balance', 15, 4);
            $table->string('currency', 3)->default('EUR');
            $table->string('buyer_name')->nullable();
            $table->string('buyer_email')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_email');
            $table->string('sender_name')->nullable();
            $table->text('message')->nullable();
            $table->string('locale', 5)->default('hr');
            $table->string('status', 32)->default('pending')->index();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamp('last_email_sent_at')->nullable();
            $table->text('email_error')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['purchase_order_id', 'cart_item_key'], 'gift_voucher_order_item_unique');
        });

        Schema::create('gift_voucher_redemptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('gift_voucher_id')->index();
            $table->unsignedBigInteger('order_id')->index();
            $table->decimal('amount', 15, 4);
            $table->string('status', 24)->default('reserved')->index();
            $table->timestamp('reserved_until')->nullable()->index();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->string('release_reason')->nullable();
            $table->timestamps();

            $table->unique(['gift_voucher_id', 'order_id'], 'gift_voucher_order_redemption_unique');
        });
    }
}
