<?php

namespace Tests\Feature;

use App\Models\Back\Orders\Order;
use App\Models\Back\Settings\Settings;
use App\Models\Front\Checkout\PaymentMethod;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CorvusPaymentTest extends TestCase
{
    private const SHOP_ID = '123';

    private const SECRET_KEY = 'corvus-test-secret';

    private const LIVE_SHOP_ID = '987';

    private const LIVE_SECRET_KEY = 'corvus-live-secret';

    protected function setUp(): void
    {
        parent::setUp();

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

        Schema::create('order_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->boolean('success');
            $table->decimal('amount', 10, 2);
            $table->string('signature');
            $table->string('payment_type', 32)->nullable();
            $table->dateTime('datetime');
            $table->string('approval_code')->nullable();
            $table->string('pg_order_id')->nullable();
            $table->string('lang', 5);
            $table->string('error')->nullable();
            $table->timestamps();
        });

        Settings::set('geo_zone', 'list', [
            'id' => 1,
            'title' => 'All',
            'state' => [],
            'status' => true,
        ]);

        Settings::set('payment', 'list.corvus', $this->paymentSetting('corvus', 'Kartice', 0, [
            'shop_id' => self::LIVE_SHOP_ID,
            'secret_key' => self::LIVE_SECRET_KEY,
            'test_shop_id' => self::SHOP_ID,
            'test_secret_key' => self::SECRET_KEY,
            'callback' => 'https://example.test/narudzba',
            'test' => '1',
        ]));
        Settings::set('payment', 'list.corvus_wallets', $this->paymentSetting('corvus_wallets', 'Apple Pay / Google Pay', 1, [
            'credential_source' => 'corvus',
        ]));
        Settings::set('payment', 'list.bank', $this->paymentSetting('bank', 'Uplatnica', 2));
        Settings::set('payment', 'list.pickup', $this->paymentSetting('pickup', 'Preuzimanje', 3));
        Settings::set('payment', 'list.cod', $this->paymentSetting('cod', 'Pouzeće', 4));
    }

    public function test_card_form_hides_wallet_tabs_and_signs_exact_post_fields(): void
    {
        $order = $this->formOrder();
        $html = (new PaymentMethod('corvus'))->resolveForm($order)->render();

        $fields = $this->baseFormFields('pis,wallet,paysafecard,googlepay,applepay,ips,crypto');
        $fields['payment_all'] = 'Y0299';
        $signature = $this->signature($fields);

        $this->assertStringContainsString('name="hide_tabs" value="pis,wallet,paysafecard,googlepay,applepay,ips,crypto"', $html);
        $this->assertStringContainsString('name="payment_all" value="Y0299"', $html);
        $this->assertStringContainsString('name="signature" value="' . $signature . '"', $html);
        $this->assertStringNotContainsString('name="terms"', $html);
        $this->assertStringNotContainsString('name="obavijesti"', $html);
    }

    public function test_wallet_form_inherits_credentials_and_leaves_only_apple_and_google_pay_tabs(): void
    {
        $order = $this->formOrder();
        $html = (new PaymentMethod('corvus_wallets'))->resolveForm($order)->render();

        $fields = $this->baseFormFields('checkout,pis,wallet,paysafecard,ips,crypto');
        $signature = $this->signature($fields);

        $this->assertStringContainsString('action="https://wallet.test.corvuspay.com/checkout/"', $html);
        $this->assertStringContainsString('name="store_id" value="' . self::SHOP_ID . '"', $html);
        $this->assertStringContainsString('name="hide_tabs" value="checkout,pis,wallet,paysafecard,ips,crypto"', $html);
        $this->assertStringNotContainsString('name="payment_all"', $html);
        $this->assertStringContainsString('name="signature" value="' . $signature . '"', $html);
    }

    public function test_live_mode_keeps_the_live_endpoint_and_credentials(): void
    {
        Settings::set('payment', 'list.corvus', $this->paymentSetting('corvus', 'Kartice', 0, [
            'shop_id' => self::LIVE_SHOP_ID,
            'secret_key' => self::LIVE_SECRET_KEY,
            'test_shop_id' => self::SHOP_ID,
            'test_secret_key' => self::SECRET_KEY,
            'callback' => 'https://example.test/narudzba',
            'test' => '0',
        ]));

        $html = (new PaymentMethod('corvus'))->resolveForm($this->formOrder())->render();
        $fields = $this->baseFormFields(
            'pis,wallet,paysafecard,googlepay,applepay,ips,crypto',
            self::LIVE_SHOP_ID
        );
        $fields['payment_all'] = 'Y0299';

        $this->assertStringContainsString('action="https://wallet.corvuspay.com/checkout/"', $html);
        $this->assertStringContainsString('name="store_id" value="' . self::LIVE_SHOP_ID . '"', $html);
        $this->assertStringContainsString(
            'name="signature" value="' . $this->signature($fields, self::LIVE_SECRET_KEY) . '"',
            $html
        );
    }

    public function test_valid_signed_wallet_return_marks_order_paid_and_is_idempotent(): void
    {
        $order = Order::query()->create([
            'order_status_id' => config('settings.order.status.unfinished'),
            'total' => 49.90,
            'payment_code' => 'corvus_wallets',
        ]);
        $parameters = [
            'order_number' => (string) $order->id,
            'language' => 'hr',
            'approval_code' => 'APPROVED-1',
            'transaction_type' => '17',
        ];
        $parameters['signature'] = $this->signature($parameters);
        $request = Request::create('/narudzba', 'GET', $parameters);

        $this->assertTrue((new PaymentMethod('corvus_wallets'))->finish($order, $request));
        $this->assertTrue((new PaymentMethod('corvus_wallets'))->finish($order->fresh(), $request));
        $this->assertSame(1, DB::table('order_transactions')->count());
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status_id' => config('settings.order.status.paid'),
        ]);
        $this->assertDatabaseHas('order_transactions', [
            'order_id' => $order->id,
            'success' => 1,
            'approval_code' => 'APPROVED-1',
            'payment_type' => '17',
        ]);
    }

    public function test_valid_signed_card_return_still_marks_order_paid(): void
    {
        $order = Order::query()->create([
            'order_status_id' => config('settings.order.status.unfinished'),
            'total' => 29.90,
            'payment_code' => 'corvus',
        ]);
        $parameters = [
            'order_number' => (string) $order->id,
            'language' => 'hr',
            'response_code' => '0',
            'approval_code' => 'CARD-APPROVED-1',
        ];
        $parameters['signature'] = $this->signature($parameters);

        $this->assertTrue(
            (new PaymentMethod('corvus'))->finish(
                $order,
                Request::create('/narudzba', 'GET', $parameters)
            )
        );
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status_id' => config('settings.order.status.paid'),
        ]);
        $this->assertDatabaseHas('order_transactions', [
            'order_id' => $order->id,
            'success' => 1,
            'approval_code' => 'CARD-APPROVED-1',
        ]);
    }

    public function test_invalid_return_signature_does_not_change_order_or_create_transaction(): void
    {
        $order = Order::query()->create([
            'order_status_id' => config('settings.order.status.unfinished'),
            'total' => 19.90,
            'payment_code' => 'corvus',
        ]);
        $request = Request::create('/narudzba', 'GET', [
            'order_number' => (string) $order->id,
            'language' => 'hr',
            'approval_code' => 'FORGED',
            'signature' => str_repeat('0', 64),
        ]);

        $this->assertFalse((new PaymentMethod('corvus'))->finish($order, $request));
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status_id' => config('settings.order.status.unfinished'),
        ]);
        $this->assertSame(0, DB::table('order_transactions')->count());
    }

    public function test_corvus_payment_variants_follow_the_same_shipping_matrix(): void
    {
        $this->assertSame(
            ['corvus', 'corvus_wallets', 'pickup'],
            $this->paymentCodesFor('pickup')
        );
        $this->assertSame(
            ['bank', 'corvus', 'corvus_wallets'],
            $this->paymentCodesFor('gls_eu')
        );
        $this->assertSame(
            ['bank', 'cod', 'corvus', 'corvus_wallets'],
            $this->paymentCodesFor('gls')
        );
        $this->assertSame(['cod'], $this->paymentCodesFor('boxnow'));
    }

    private function paymentSetting(string $code, string $title, int $sortOrder, array $data = []): array
    {
        return [
            'title' => $title,
            'title_en' => $title,
            'code' => $code,
            'min' => null,
            'data' => array_merge([
                'price' => 0,
                'short_description' => '',
                'short_description_en' => '',
                'description' => '',
                'description_en' => '',
            ], $data),
            'geo_zone' => null,
            'status' => true,
            'sort_order' => $sortOrder,
        ];
    }

    private function formOrder(): Order
    {
        $order = new Order();
        $order->forceFill([
            'id' => 321,
            'total' => 20.50,
            'payment_fname' => 'Ana',
            'payment_lname' => 'Anić',
            'payment_phone' => '0911111111',
            'payment_email' => 'ana@example.test',
        ]);

        return $order;
    }

    private function baseFormFields(string $hideTabs, string $shopId = self::SHOP_ID): array
    {
        return [
            'amount' => '20.50',
            'cart' => 'Web shop kupnja 321',
            'currency' => 'EUR',
            'language' => 'hr',
            'order_number' => 321,
            'require_complete' => 'false',
            'store_id' => $shopId,
            'cardholder_name' => 'Ana',
            'cardholder_surname' => 'Anić',
            'cardholder_phone' => '0911111111',
            'cardholder_email' => 'ana@example.test',
            'hide_tabs' => $hideTabs,
            'version' => '1.3',
        ];
    }

    private function signature(array $fields, string $secretKey = self::SECRET_KEY): string
    {
        unset($fields['signature']);
        ksort($fields);

        $payload = '';
        foreach ($fields as $key => $value) {
            $payload .= $key . (string) $value;
        }

        return hash_hmac('sha256', $payload, $secretKey);
    }

    private function paymentCodesFor(string $shipping): array
    {
        $codes = (new PaymentMethod())
            ->findGeo(1)
            ->checkShipping($shipping)
            ->resolve()
            ->pluck('code')
            ->sort()
            ->values()
            ->all();

        return $codes;
    }
}
