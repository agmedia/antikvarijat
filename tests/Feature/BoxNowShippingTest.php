<?php

namespace Tests\Feature;

use App\Mail\ShippingTrackingAvailable;
use App\Models\Back\Orders\Order;
use App\Models\User;
use App\Services\Shipping\BoxNowService;
use App\Services\Shipping\OrderTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class BoxNowShippingTest extends TestCase
{
    use RefreshDatabase;

    public function test_boxnow_order_sent_from_legacy_gls_action_uses_boxnow_api(): void
    {
        $this->configureBoxNow();
        Mail::fake();
        Http::fake(function (HttpRequest $request) {
            if ($request->url() === 'https://boxnow.example.test/api/v1/auth-sessions') {
                return Http::response(['access_token' => 'boxnow-token'], 200);
            }

            if ($request->url() === 'https://boxnow.example.test/api/v1/delivery-requests') {
                return Http::response([
                    'id' => 'delivery-request-1',
                    'parcels' => [['id' => 'BOX-123456']],
                ], 200);
            }

            return Http::response(['message' => 'Unexpected request'], 500);
        });

        $orderId = $this->createOrder();
        $response = $this->actingAs(User::factory()->create())
            ->postJson(route('api.order.send.gls'), ['order_id' => $orderId]);

        $response->assertOk()->assertJsonFragment(['message' => 'Box Now pošiljka uspješno je kreirana s ID-em: BOX-123456']);

        $order = Order::findOrFail($orderId);
        $this->assertSame(BoxNowService::CARRIER, $order->shipping_carrier);
        $this->assertSame('BOX-123456', $order->shipping_parcel_id);
        $this->assertSame('BOX-123456', $order->tracking_code);
        $this->assertSame('new', $order->shipping_tracking_status_code);

        Http::assertSent(function (HttpRequest $request) use ($orderId) {
            if ($request->url() !== 'https://boxnow.example.test/api/v1/delivery-requests') {
                return false;
            }

            return $request->hasHeader('Authorization', 'Bearer boxnow-token')
                && $request['orderNumber'] === (string) $orderId
                && $request['destination']['locationId'] === 'LOCKER-987'
                && $request['destination']['contactNumber'] === '+385912345678';
        });
        Mail::assertSent(ShippingTrackingAvailable::class, 1);
    }

    public function test_boxnow_tracking_status_is_refreshed_and_delivered_order_is_marked_shipped(): void
    {
        $this->configureBoxNow();
        Mail::fake();
        Http::fake(function (HttpRequest $request) {
            if ($request->url() === 'https://boxnow.example.test/api/v1/auth-sessions') {
                return Http::response(['access_token' => 'boxnow-token'], 200);
            }

            if (Str::startsWith($request->url(), 'https://boxnow.example.test/api/v1/parcels')) {
                return Http::response([
                    'data' => [[
                        'id' => 'BOX-123456',
                        'state' => 'delivered',
                    ]],
                ], 200);
            }

            return Http::response(['message' => 'Unexpected request'], 500);
        });

        $orderId = $this->createOrder([
            'shipping_carrier' => BoxNowService::CARRIER,
            'shipping_parcel_id' => 'BOX-123456',
            'tracking_code' => 'BOX-123456',
            'shipping_tracking_status_code' => 'in-transit',
            'shipping_tracking_email_sent_at' => now(),
        ]);

        $result = app(OrderTrackingService::class)->refresh(Order::findOrFail($orderId));
        $order = Order::findOrFail($orderId);

        $this->assertTrue($result['updated']);
        $this->assertSame('delivered', $order->shipping_tracking_status_code);
        $this->assertSame('Pošiljka je preuzeta.', $order->shipping_tracking_status);
        $this->assertTrue((bool) $order->shipped);

        Http::assertSent(function (HttpRequest $request) {
            if (! Str::startsWith($request->url(), 'https://boxnow.example.test/api/v1/parcels')) {
                return false;
            }

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['parcelId'] ?? null) === 'BOX-123456';
        });
    }

    public function test_boxnow_action_rejects_non_boxnow_order(): void
    {
        Http::fake();
        $orderId = $this->createOrder([
            'shipping_method' => 'GLS dostava',
            'shipping_code' => 'gls',
            'comment' => null,
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('api.order.send.boxnow'), ['order_id' => $orderId])
            ->assertStatus(422)
            ->assertJsonFragment(['error' => 'Narudžba nema odabranu Box Now dostavu.']);

        Http::assertNothingSent();
    }

    public function test_shipment_actions_require_an_authenticated_administrator(): void
    {
        $orderId = $this->createOrder();

        $this->postJson(route('api.order.send.boxnow'), ['order_id' => $orderId])
            ->assertStatus(401);
        $this->postJson(route('api.order.send.gls'), ['order_id' => $orderId])
            ->assertStatus(401);
    }

    private function configureBoxNow(): void
    {
        config([
            'services.boxnow.base_url' => 'https://boxnow.example.test/api/v1',
            'services.boxnow.client_id' => 'client-id',
            'services.boxnow.client_secret' => 'client-secret',
            'services.boxnow.warehouse_location_id' => 'WAREHOUSE-1',
            'services.boxnow.origin_name' => 'Antikvarijat Biblos',
            'services.boxnow.origin_email' => 'info@example.test',
            'services.boxnow.origin_phone' => '091 111 2222',
            'services.boxnow.tracking_url' => 'https://track.boxnow.hr/en?track={parcel}',
        ]);
    }

    private function createOrder(array $overrides = []): int
    {
        return (int) DB::table('orders')->insertGetId(array_merge([
            'user_id' => 0,
            'affiliate_id' => 0,
            'order_status_id' => (int) config('settings.order.status.paid', 3),
            'invoice' => null,
            'total' => 19.82,
            'payment_fname' => 'Test',
            'payment_lname' => 'Kupac',
            'payment_address' => 'Test ulica 1',
            'payment_zip' => '10000',
            'payment_city' => 'Zagreb',
            'payment_phone' => '0912345678',
            'payment_email' => 'boxnow@example.test',
            'payment_method' => 'Kartice',
            'payment_code' => 'card',
            'payment_card' => null,
            'payment_installment' => 0,
            'shipping_fname' => 'Test',
            'shipping_lname' => 'Kupac',
            'shipping_address' => 'Test ulica 1',
            'shipping_zip' => '10000',
            'shipping_city' => 'Zagreb',
            'shipping_phone' => '0912345678',
            'shipping_email' => 'boxnow@example.test',
            'shipping_method' => 'Box Now paketomat',
            'shipping_code' => 'boxnow',
            'shipping_carrier' => null,
            'shipping_parcel_id' => null,
            'shipping_tracking_status_code' => null,
            'shipping_tracking_status' => null,
            'company' => '',
            'oib' => '',
            'comment' => '10000, Ilica 1_LOCKER-987',
            'tracking_code' => '',
            'shipped' => false,
            'printed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
