<?php

namespace Tests\Feature;

use App\Helpers\Session\CheckoutSession;
use App\Http\Livewire\Front\Checkout;
use App\Models\Back\Settings\Settings;
use App\Models\Front\AgCart;
use App\Models\Front\Checkout\PaymentMethod;
use App\Services\Shipping\WoltDriveService;
use App\Services\Shipping\WoltDriveSettingsService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class WoltDriveCheckoutTest extends TestCase
{
    use RefreshDatabase;

    /** @var string */
    private $cartId;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'session.driver' => 'array',
        ]);

        // Settings maintains a request-level static cache; touching it through
        // the model also resets state left by a previous transaction-backed test.
        Settings::insert('test', 'wolt-checkout-cache-reset', '1', false);
        Settings::set('geo_zone', 'list', [
            'id' => 1,
            'title' => 'Sve države',
            'state' => [],
            'status' => true,
        ]);
        Settings::set('shipping', 'list.wolt_drive', $this->shippingMethod(
            'Wolt Drive',
            WoltDriveService::CARRIER,
            1
        ));
        Settings::set('shipping', 'list.gls', $this->shippingMethod('GLS dostava', 'gls', 2));

        foreach (['corvus', 'corvus_wallets', 'bank', 'cod', 'pickup'] as $index => $code) {
            Settings::set('payment', 'list.' . $code, $this->paymentMethod($code, $index + 1));
        }

        $this->saveWoltSettings();

        $this->cartId = 'wolt-checkout-' . Str::uuid();
        session()->start();
        session([config('session.cart') => $this->cartId]);
        Cart::session($this->cartId)->add([
            'id' => 9001,
            'name' => 'Testna knjiga',
            'price' => 40,
            'quantity' => 1,
            'attributes' => ['path' => '/testna-knjiga'],
        ]);
        CheckoutSession::setAddress($this->address());
    }

    public function test_available_quote_allows_wolt_selection_and_exposes_quote_details(): void
    {
        $wolt = $this->mockWolt([
            'available' => true,
            'price' => 7.25,
            'eta_minutes' => 35,
            'message' => null,
        ]);
        $wolt->shouldReceive('forgetCheckoutQuote')->zeroOrMoreTimes();

        Livewire::test(Checkout::class, ['step' => 'dostava'])
            ->assertSet('woltAvailable', true)
            ->assertSet('woltQuotePrice', 7.25)
            ->assertSet('woltEtaMinutes', 35)
            ->call('selectShipping', WoltDriveService::CARRIER)
            ->assertSet('shipping', WoltDriveService::CARRIER)
            ->assertHasNoErrors('shipping');

        $this->assertSame(WoltDriveService::CARRIER, CheckoutSession::getShipping());
    }

    public function test_unavailable_quote_is_shown_fail_closed_and_cannot_be_selected(): void
    {
        $message = 'Wolt Drive nije dostupan za ovu adresu.';
        $wolt = $this->mockWolt([
            'available' => false,
            'error_code' => 'DROPOFF_OUTSIDE_OF_DELIVERY_AREA',
            'message' => $message,
        ]);
        $wolt->shouldReceive('forgetCheckoutQuote')->zeroOrMoreTimes();

        Livewire::test(Checkout::class, ['step' => 'dostava'])
            ->assertSet('woltAvailable', false)
            ->assertSet('woltUnavailableReason', $message)
            ->assertSee($message)
            ->call('selectShipping', WoltDriveService::CARRIER)
            ->assertSet('shipping', '')
            ->assertHasErrors(['shipping']);

        $this->assertFalse(CheckoutSession::hasShipping());
    }

    public function test_address_change_forgets_quote_shipping_and_payment(): void
    {
        $wolt = $this->mockWolt([
            'available' => true,
            'price' => 6.80,
            'eta_minutes' => 25,
        ]);
        $wolt->shouldReceive('forgetCheckoutQuote')->once();

        Livewire::test(Checkout::class, ['step' => 'dostava'])
            ->call('selectShipping', WoltDriveService::CARRIER)
            ->call('selectPayment', 'corvus')
            ->assertSet('shipping', WoltDriveService::CARRIER)
            ->assertSet('payment', 'corvus')
            ->set('address.address', 'Nova adresa 22')
            ->assertSet('woltAvailable', null)
            ->assertSet('woltQuotePrice', null)
            ->assertSet('shipping', '')
            ->assertSet('payment', '');

        $this->assertFalse(CheckoutSession::hasShipping());
        $this->assertFalse(CheckoutSession::hasPayment());
        $this->assertSame('Nova adresa 22', CheckoutSession::getAddress()['address']);
    }

    public function test_shipping_rules_reject_wolt_before_any_remote_quote(): void
    {
        Settings::set('shipping', 'list.wolt_drive', $this->shippingMethod(
            'Wolt Drive',
            WoltDriveService::CARRIER,
            1,
            ['allowed_postal_codes' => '10000']
        ));
        CheckoutSession::setAddress($this->address([
            'zip' => '31000',
            'city' => 'Osijek',
        ]));

        $wolt = Mockery::mock(WoltDriveService::class);
        $wolt->shouldNotReceive('quote');
        $wolt->shouldReceive('forgetCheckoutQuote')->zeroOrMoreTimes();
        $wolt->shouldReceive('checkoutQuote')->zeroOrMoreTimes()->andReturnNull();
        $this->app->instance(WoltDriveService::class, $wolt);

        Livewire::test(Checkout::class, ['step' => 'dostava'])
            ->assertSet('woltAvailable', null)
            ->call('selectShipping', WoltDriveService::CARRIER)
            ->assertSet('shipping', '')
            ->assertHasErrors(['shipping']);

        $this->assertFalse(CheckoutSession::hasShipping());
    }

    public function test_tampered_checkout_session_is_revalidated_server_side_before_order_creation(): void
    {
        CheckoutSession::setShipping(WoltDriveService::CARRIER);
        CheckoutSession::setPayment('corvus');
        $message = 'Wolt je u međuvremenu odbio adresu.';
        $quote = [
            'available' => false,
            'error_code' => 'DROPOFF_OUTSIDE_OF_DELIVERY_AREA',
            'message' => $message,
        ];
        $wolt = Mockery::mock(WoltDriveService::class);
        $wolt->shouldReceive('checkoutQuote')->zeroOrMoreTimes()->andReturnNull();
        $wolt->shouldReceive('quote')
            ->once()
            ->withArgs(function (array $address, array $cart): bool {
                return ($cart['payment_code'] ?? null) === 'corvus';
            })
            ->andReturn($quote);
        $this->app->instance(WoltDriveService::class, $wolt);

        $this->get(route('pregled'))
            ->assertRedirect(route('naplata', ['step' => 'dostava']))
            ->assertSessionHas('error', $message);

        $this->assertDatabaseCount('orders', 0);
    }

    /**
     * @dataProvider forbiddenWoltPayments
     */
    public function test_tampered_wolt_payment_is_rejected_by_server_side_matrix(string $payment): void
    {
        CheckoutSession::setShipping(WoltDriveService::CARRIER);
        CheckoutSession::setPayment($payment);
        $this->mockWolt([
            'available' => true,
            'price' => 7.25,
            'eta_minutes' => 30,
        ], null);

        $this->get(route('pregled'))
            ->assertRedirect(route('naplata', ['step' => 'dostava']))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_selecting_cod_requotes_with_payment_and_recalculates_wolt_total(): void
    {
        $this->saveWoltSettings(['cod_enabled' => true]);
        $quotedPaymentCodes = [];
        $baseQuote = [
            'available' => true,
            'price' => 6.50,
            'eta_minutes' => 25,
            'message' => null,
        ];
        $codQuote = [
            'available' => true,
            'price' => 8.75,
            'eta_minutes' => 25,
            'message' => null,
        ];
        $wolt = Mockery::mock(WoltDriveService::class);
        $wolt->shouldReceive('quote')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function (array $address, array $cart) use (
                &$quotedPaymentCodes,
                $baseQuote,
                $codQuote
            ): array {
                $paymentCode = (string) ($cart['payment_code'] ?? '');
                $quotedPaymentCodes[] = $paymentCode;

                return $paymentCode === 'cod' ? $codQuote : $baseQuote;
            });
        $wolt->shouldReceive('checkoutQuote')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function () use ($baseQuote, $codQuote): array {
                return CheckoutSession::getPayment() === 'cod' ? $codQuote : $baseQuote;
            });
        $wolt->shouldReceive('forgetCheckoutQuote')->zeroOrMoreTimes();
        $this->app->instance(WoltDriveService::class, $wolt);

        Livewire::test(Checkout::class, ['step' => 'dostava'])
            ->call('selectShipping', WoltDriveService::CARRIER)
            ->call('selectPayment', 'cod')
            ->assertSet('shipping', WoltDriveService::CARRIER)
            ->assertSet('payment', 'cod')
            ->assertSet('woltQuotePrice', 8.75)
            ->assertHasNoErrors('payment');

        $this->assertContains('cod', $quotedPaymentCodes);
        $this->assertSame('cod', CheckoutSession::getPayment());
        $this->assertEquals(48.75, (float) (new AgCart($this->cartId))->get()['total']);
    }

    public function test_wolt_payment_matrix_respects_the_cod_setting(): void
    {
        $withoutCod = (new PaymentMethod())
            ->findGeo(1)
            ->checkShipping(WoltDriveService::CARRIER)
            ->resolve();

        $this->assertEqualsCanonicalizing(
            ['bank', 'corvus', 'corvus_wallets'],
            $withoutCod->keys()->all()
        );

        $this->saveWoltSettings(['cod_enabled' => true]);
        $withCod = (new PaymentMethod())
            ->findGeo(1)
            ->checkShipping(WoltDriveService::CARRIER)
            ->resolve();

        $this->assertEqualsCanonicalizing(
            ['bank', 'cod', 'corvus', 'corvus_wallets'],
            $withCod->keys()->all()
        );
    }

    public function forbiddenWoltPayments(): array
    {
        return [['pickup'], ['unknown-payment']];
    }

    private function mockWolt(array $quote, ?array $checkoutQuote = []): WoltDriveService
    {
        $wolt = Mockery::mock(WoltDriveService::class);
        $wolt->shouldReceive('quote')->zeroOrMoreTimes()->andReturn($quote);
        $wolt->shouldReceive('checkoutQuote')->zeroOrMoreTimes()->andReturn(
            $checkoutQuote === [] ? $quote : $checkoutQuote
        );
        $this->app->instance(WoltDriveService::class, $wolt);

        return $wolt;
    }

    private function saveWoltSettings(array $overrides = []): void
    {
        app(WoltDriveSettingsService::class)->save(array_merge([
            'module_enabled' => true,
            'environment' => 'development',
            'api_key' => 'checkout-api-secret',
            'webhook_secret' => 'checkout-webhook-secret',
            'venue_id' => 'VENUE-CHECKOUT',
            'merchant_id' => 'MERCHANT-CHECKOUT',
            'availability_cache_seconds' => 0,
            'preparation_time_minutes' => 20,
            'request_timeout_seconds' => 10,
            'fallback_weight_grams' => 500,
            'cod_enabled' => false,
            'pricing_mode' => 'quote',
            'quote_markup_percent' => 0,
            'max_quote_price' => 0,
            'support_url' => 'https://example.test/contact',
            'support_email' => 'support@example.test',
            'support_phone' => '+385 91 111 2222',
        ], $overrides));
    }

    private function address(array $overrides = []): array
    {
        return array_merge([
            'fname' => 'Ana',
            'lname' => 'Anić',
            'email' => 'ana@example.test',
            'phone' => '+385 91 234 5678',
            'birthday_year' => '',
            'address' => 'Ilica 1',
            'city' => 'Zagreb',
            'zip' => '10000',
            'state' => 'Croatia',
            'company' => '',
            'oib' => '',
        ], $overrides);
    }

    private function shippingMethod(
        string $title,
        string $code,
        int $sortOrder,
        array $rules = []
    ): array {
        return [
            'id' => $sortOrder,
            'title' => $title,
            'title_en' => $title,
            'code' => $code,
            'geo_zone' => 1,
            'status' => true,
            'sort_order' => $sortOrder,
            'data' => [
                'price' => 5.50,
                'time' => '1-2 sata',
                'time_en' => '1-2 hours',
                'short_description' => '',
                'short_description_en' => '',
                'description' => '',
                'description_en' => '',
                'rules' => $rules,
            ],
        ];
    }

    private function paymentMethod(string $code, int $sortOrder): array
    {
        return [
            'id' => $sortOrder,
            'title' => strtoupper($code),
            'code' => $code,
            'geo_zone' => 1,
            'status' => true,
            'sort_order' => $sortOrder,
            'data' => [
                'price' => 0,
                'short_description' => '',
                'description' => '',
            ],
        ];
    }
}
