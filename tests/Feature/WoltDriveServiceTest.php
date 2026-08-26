<?php

namespace Tests\Feature;

use App\Models\Back\Orders\Order;
use App\Models\Back\Settings\Settings;
use App\Services\Shipping\WoltDriveAmbiguousCreateException;
use App\Services\Shipping\WoltDriveException;
use App\Services\Shipping\WoltDriveService;
use App\Services\Shipping\WoltDriveSettingsService;
use App\Services\Shipping\WoltDriveWebhookException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WoltDriveServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'session.driver' => 'array',
        ]);
        Settings::insert('test', 'wolt-service-cache-reset', '1', false);
        $this->saveSettings();
        session()->start();
    }

    public function test_quote_returns_raw_provider_price_and_is_fingerprinted_by_address_and_cart(): void
    {
        $promiseRequests = 0;
        Http::fake(function (HttpRequest $request) use (&$promiseRequests) {
            $this->assertStringEndsWith('/v1/venues/VENUE-TEST/shipment-promises', $request->url());
            $promiseRequests++;

            return Http::response($this->promiseResponse([
                'id' => 'promise-' . $promiseRequests,
            ]), 200);
        });

        $service = app(WoltDriveService::class);
        $address = $this->address();
        $cart = $this->cart();
        $first = $service->quote($address, $cart);
        $sameFingerprint = $service->quote($address, $cart);
        $newAddress = $service->quote(array_merge($address, ['address' => 'Ilica 2']), $cart);
        $shippingTotalOnly = $service->quote(
            array_merge($address, ['address' => 'Ilica 2']),
            array_merge($cart, ['total' => 27.25])
        );
        $newCart = $service->quote(array_merge($address, ['address' => 'Ilica 2']), array_merge($cart, [
            'subtotal' => 30,
            'total' => 30,
        ]));

        $this->assertTrue($first['available']);
        $this->assertSame(7.25, $first['price']);
        $this->assertSame(7.25, $first['provider_price']);
        $this->assertSame('promise-1', $first['promise_id']);
        $this->assertSame('promise-1', $sameFingerprint['promise_id']);
        $this->assertSame('promise-2', $newAddress['promise_id']);
        $this->assertSame('promise-2', $shippingTotalOnly['promise_id']);
        $this->assertSame('promise-3', $newCart['promise_id']);
        $this->assertSame(3, $promiseRequests);

        Http::assertSent(function (HttpRequest $request) {
            return $request->hasHeader('Authorization', 'Bearer wolt-api-secret')
                && $request['street'] === 'Ilica 1'
                && $request['city'] === 'Zagreb'
                && $request['post_code'] === '10000'
                && $request['min_preparation_time_minutes'] === 20
                && $request['parcels'][0]['dimensions']['weight_gram'] === 750
                && $request['parcels'][0]['price']['amount'] === 2000
                && ! isset($request['cash']);
        });
    }

    public function test_quote_near_expiry_is_not_reused(): void
    {
        $requests = 0;
        Http::fake(function () use (&$requests) {
            $requests++;

            return Http::response($this->promiseResponse([
                'id' => 'short-lived-' . $requests,
                'valid_until' => now()->addSeconds(10)->toIso8601String(),
            ]), 200);
        });

        $service = app(WoltDriveService::class);
        $this->assertSame('short-lived-1', $service->quote($this->address(), $this->cart())['promise_id']);
        $this->assertSame('short-lived-2', $service->quote($this->address(), $this->cart())['promise_id']);
        $this->assertSame(2, $requests);
    }

    public function test_quote_above_configured_safety_max_is_unavailable(): void
    {
        $this->saveSettings(['max_quote_price' => 7]);
        Http::fake([
            '*' => Http::response($this->promiseResponse([
                'price' => ['amount' => 725, 'currency' => 'EUR'],
            ]), 200),
        ]);

        $quote = app(WoltDriveService::class)->quote($this->address(), $this->cart());

        $this->assertFalse($quote['available']);
        $this->assertSame('WOLT_QUOTE_PRICE_TOO_HIGH', $quote['error_code']);
        $this->assertNull($quote['promise_id']);
        $this->assertNull(app(WoltDriveService::class)->checkoutQuote($this->address(), $this->cart()));
    }

    public function test_cod_quote_uses_final_total_including_dynamic_wolt_shipping(): void
    {
        $this->saveSettings([
            'cod_enabled' => true,
            'pricing_mode' => 'quote',
            'quote_markup_percent' => 0,
        ]);
        Settings::insert('shipping', 'list.wolt_drive', json_encode([[
            'id' => 1,
            'title' => 'Wolt Drive',
            'code' => WoltDriveService::CARRIER,
            'geo_zone' => 1,
            'status' => true,
            'sort_order' => 1,
            'data' => [
                'price' => 0,
                'rules' => ['free_shipping_mode' => 'never'],
            ],
        ]]), true);

        $cashAmounts = [];
        Http::fake(function (HttpRequest $request) use (&$cashAmounts) {
            $cashAmounts[] = (int) data_get($request['cash'] ?? [], 'amount_to_collect');

            return Http::response($this->promiseResponse([
                'id' => 'cod-promise-' . count($cashAmounts),
                'price' => ['amount' => 875, 'currency' => 'EUR'],
            ]), 200);
        });

        $cart = $this->cart([
            'subtotal' => 40,
            'total' => 40,
            'payment_code' => 'cod',
        ]);
        $service = app(WoltDriveService::class);
        $quote = $service->quote($this->address(), $cart);

        $this->assertTrue($quote['available']);
        $this->assertSame(48.75, $quote['cash_amount_to_collect']);
        $this->assertSame([4000, 4875], $cashAmounts);

        $cartWithShipping = array_merge($cart, [
            'total' => 48.75,
            'detail_con' => [[
                'name' => 'Wolt Drive',
                'type' => 'shipping',
                'value' => '+8.75',
            ]],
        ]);
        $this->assertSame(
            $quote['promise_id'],
            $service->checkoutQuote($this->address(), $cartWithShipping)['promise_id']
        );
    }

    public function test_create_uses_a_fresh_binding_promise_and_posts_delivery_exactly_once(): void
    {
        $this->saveSettings(['cod_enabled' => true]);
        $deliveryPosts = 0;
        Http::fake(function (HttpRequest $request) use (&$deliveryPosts) {
            if (str_ends_with($request->url(), '/shipment-promises')) {
                return Http::response($this->promiseResponse(), 200);
            }

            if (str_ends_with($request->url(), '/deliveries')) {
                $deliveryPosts++;

                return Http::response([
                    'id' => 'delivery-123',
                    'wolt_order_reference_id' => 'wolt-reference-123',
                    'status' => 'INFO_RECEIVED',
                    'tracking' => [
                        'id' => 'tracking-123',
                        'url' => 'https://tracking.example.test/tracking-123',
                    ],
                ], 200);
            }

            return Http::response(['error_code' => 'UNEXPECTED'], 500);
        });
        $order = Order::findOrFail($this->createOrder([
            'payment_method' => 'Pouzeće',
            'payment_code' => 'cod',
            'total' => 19.82,
        ]));

        $created = app(WoltDriveService::class)->createDelivery($order);
        $existing = app(WoltDriveService::class)->createDelivery($order->fresh());

        $this->assertSame('wolt-reference-123', $created['parcel_id']);
        $this->assertSame('tracking-123', $created['tracking_code']);
        $this->assertSame('tracking-123', $existing['tracking_code']);
        $this->assertSame(1, $deliveryPosts);

        $persisted = $order->fresh();
        $this->assertSame(WoltDriveService::CARRIER, $persisted->shipping_carrier);
        $this->assertSame('wolt-reference-123', $persisted->shipping_parcel_id);
        $this->assertSame('tracking-123', $persisted->tracking_code);
        $this->assertSame('completed', data_get($persisted->shipping_tracking_payload, '_wolt_create.state'));
        $this->assertTrue((bool) $persisted->printed);

        Http::assertSent(function (HttpRequest $request) use ($order) {
            if (! str_ends_with($request->url(), '/deliveries')) {
                return false;
            }

            return $request['shipment_promise_id'] === 'promise-123'
                && $request['merchant_order_reference_id'] === 'BIBLOS-' . $order->id
                && $request['order_number'] === (string) $order->id
                && $request['recipient']['name'] === 'Ana Anić'
                && $request['recipient']['phone_number'] === '+385912345678'
                && $request['recipient']['email'] === 'ana@example.test'
                && $request['dropoff']['location']['coordinates']['lat'] === 45.815
                && $request['dropoff']['location']['coordinates']['lon'] === 15.9819
                && $request['cash']['amount_to_collect'] === 1982
                && $request['parcels'][0]['dimensions']['weight_gram'] === 500;
        });
    }

    public function test_delivery_connection_timeout_is_marked_ambiguous_and_never_reposted(): void
    {
        $deliveryPosts = 0;
        Http::fake(function (HttpRequest $request) use (&$deliveryPosts) {
            if (str_ends_with($request->url(), '/shipment-promises')) {
                return Http::response($this->promiseResponse(), 200);
            }

            $deliveryPosts++;
            throw new ConnectionException('cURL error 28: Operation timed out.');
        });
        $order = Order::findOrFail($this->createOrder());

        try {
            app(WoltDriveService::class)->createDelivery($order);
            $this->fail('Expected ambiguous create exception.');
        } catch (WoltDriveAmbiguousCreateException $exception) {
            $this->assertSame('WOLT_CREATE_CONNECTION_OUTCOME_UNKNOWN', $exception->errorCode());
        }

        $this->assertSame('create_ambiguous', $order->fresh()->shipping_tracking_status_code);
        $this->assertSame('ambiguous', data_get(
            $order->fresh()->shipping_tracking_payload,
            '_wolt_create.state'
        ));

        try {
            app(WoltDriveService::class)->createDelivery($order->fresh());
            $this->fail('Expected previous ambiguous create exception.');
        } catch (WoltDriveAmbiguousCreateException $exception) {
            $this->assertSame('WOLT_PREVIOUS_CREATE_OUTCOME_UNKNOWN', $exception->errorCode());
        }

        $this->assertSame(1, $deliveryPosts);
    }

    /**
     * @dataProvider ambiguousDeliveryStatuses
     */
    public function test_ambiguous_delivery_http_response_is_never_reposted(int $status): void
    {
        $deliveryPosts = 0;
        Http::fake(function (HttpRequest $request) use (&$deliveryPosts, $status) {
            if (str_ends_with($request->url(), '/shipment-promises')) {
                return Http::response($this->promiseResponse(), 200);
            }

            $deliveryPosts++;

            return Http::response(['error_code' => 'REMOTE-' . $status], $status);
        });
        $order = Order::findOrFail($this->createOrder());

        foreach ([1, 2] as $attempt) {
            try {
                app(WoltDriveService::class)->createDelivery($order->fresh());
                $this->fail('Expected ambiguous create exception on attempt ' . $attempt . '.');
            } catch (WoltDriveAmbiguousCreateException $exception) {
                $this->assertNotSame('', $exception->errorCode());
            }
        }

        $this->assertSame(1, $deliveryPosts);
        $this->assertSame('ambiguous', data_get(
            $order->fresh()->shipping_tracking_payload,
            '_wolt_create.state'
        ));
    }

    public function test_cancel_posts_normalized_reason_once_and_persists_rejected_state(): void
    {
        Http::fake(function (HttpRequest $request) {
            if (str_ends_with($request->url(), '/status/cancel')) {
                return Http::response(['status' => 'REJECTED'], 200);
            }

            return Http::response(['error_code' => 'UNEXPECTED'], 500);
        });
        $order = Order::findOrFail($this->createOrder([
            'shipping_carrier' => WoltDriveService::CARRIER,
            'shipping_parcel_id' => 'wolt-ref-cancel',
            'tracking_code' => 'track-cancel',
            'shipping_tracking_status_code' => 'PICKED_UP',
            'shipping_tracking_payload' => json_encode([
                'wolt_order_reference_id' => 'wolt-ref-cancel',
            ]),
            'printed' => true,
        ]));

        $cancelled = app(WoltDriveService::class)->cancel(
            $order,
            "  Kupac   je promijenio mišljenje.  "
        );
        $alreadyCancelled = app(WoltDriveService::class)->cancel($order->fresh(), 'Ponovi');

        $this->assertSame('REJECTED', $cancelled['status_code']);
        $this->assertSame('REJECTED', $alreadyCancelled['status_code']);
        $this->assertSame('REJECTED', $order->fresh()->shipping_tracking_status_code);
        Http::assertSentCount(1);
        Http::assertSent(function (HttpRequest $request) {
            return str_ends_with($request->url(), '/order/wolt-ref-cancel/status/cancel')
                && $request->method() === 'PATCH'
                && $request['reason'] === 'Kupac je promijenio mišljenje.';
        });
    }

    public function test_terminal_delivered_cancel_is_rejected_without_provider_patch(): void
    {
        Http::fake();
        $order = Order::findOrFail($this->createOrder([
            'shipping_carrier' => WoltDriveService::CARRIER,
            'shipping_parcel_id' => 'wolt-ref-delivered',
            'tracking_code' => 'track-delivered',
            'shipping_tracking_status_code' => 'order.delivered',
            'printed' => true,
            'shipped' => true,
        ]));

        try {
            app(WoltDriveService::class)->cancel($order, 'Prekasno otkazivanje');
            $this->fail('Expected delivered cancellation rejection.');
        } catch (WoltDriveException $exception) {
            $this->assertSame('WOLT_ALREADY_DELIVERED', $exception->errorCode());
        }

        Http::assertNothingSent();
    }

    public function test_hs256_webhook_accepts_valid_event_and_rejects_signature_or_venue_mismatch(): void
    {
        $claims = $this->webhookClaims();
        $valid = app(WoltDriveService::class)->handleWebhookToken(
            $this->jwt($claims, 'wolt-webhook-secret')
        );

        $this->assertSame('order.delivered', $valid['status_code']);
        $this->assertSame('wolt-reference-webhook', $valid['parcel_id']);
        $this->assertSame('tracking-webhook', $valid['tracking_code']);
        $this->assertSame(321, $valid['order_id']);
        $this->assertTrue($valid['is_delivered']);

        try {
            app(WoltDriveService::class)->handleWebhookToken(
                $this->jwt($claims, 'wrong-secret')
            );
            $this->fail('Expected invalid webhook signature.');
        } catch (WoltDriveWebhookException $exception) {
            $this->assertSame('WOLT_WEBHOOK_INVALID_SIGNATURE', $exception->errorCode());
            $this->assertSame(401, $exception->httpStatus());
        }

        $mismatched = $claims;
        $mismatched['payload']['details']['venue_id'] = 'OTHER-VENUE';

        try {
            app(WoltDriveService::class)->handleWebhookToken(
                $this->jwt($mismatched, 'wolt-webhook-secret')
            );
            $this->fail('Expected venue mismatch.');
        } catch (WoltDriveWebhookException $exception) {
            $this->assertSame('WOLT_WEBHOOK_VENUE_MISMATCH', $exception->errorCode());
            $this->assertSame(403, $exception->httpStatus());
        }
    }

    public function ambiguousDeliveryStatuses(): array
    {
        return [[408], [409], [429], [500], [503]];
    }

    private function promiseResponse(array $overrides = []): array
    {
        return array_replace_recursive([
            'id' => 'promise-123',
            'is_binding' => true,
            'valid_until' => now()->addMinutes(2)->toIso8601String(),
            'price' => ['amount' => 725, 'currency' => 'EUR'],
            'time_estimate_minutes' => 30,
            'dropoff' => [
                'eta_minutes' => 30,
                'location' => [
                    'coordinates' => ['lat' => 45.815, 'lon' => 15.9819],
                ],
            ],
        ], $overrides);
    }

    private function address(array $overrides = []): array
    {
        return array_merge([
            'address' => 'Ilica 1',
            'city' => 'Zagreb',
            'zip' => '10000',
        ], $overrides);
    }

    private function cart(array $overrides = []): array
    {
        return array_merge([
            'subtotal' => 20,
            'total' => 20,
            'payment_code' => 'corvus',
            'items' => [[
                'id' => 11,
                'quantity' => 1,
                'price' => 20,
                'total' => 20,
                'weight_grams' => 750,
            ]],
        ], $overrides);
    }

    private function saveSettings(array $overrides = []): void
    {
        app(WoltDriveSettingsService::class)->save(array_merge([
            'module_enabled' => true,
            'environment' => 'development',
            'api_key' => 'wolt-api-secret',
            'webhook_secret' => 'wolt-webhook-secret',
            'venue_id' => 'VENUE-TEST',
            'merchant_id' => 'MERCHANT-TEST',
            'availability_cache_seconds' => 120,
            'preparation_time_minutes' => 20,
            'request_timeout_seconds' => 10,
            'fallback_weight_grams' => 500,
            'cod_enabled' => false,
            'pricing_mode' => 'quote',
            'quote_markup_percent' => 10,
            'max_quote_price' => 0,
            'support_url' => 'https://example.test/contact',
            'support_email' => 'support@example.test',
            'support_phone' => '+385 91 111 2222',
        ], $overrides));
    }

    private function createOrder(array $overrides = []): int
    {
        return (int) DB::table('orders')->insertGetId(array_merge([
            'user_id' => 0,
            'affiliate_id' => 0,
            'order_status_id' => (int) config('settings.order.status.paid'),
            'invoice' => null,
            'total' => 19.82,
            'payment_fname' => 'Ana',
            'payment_lname' => 'Anić',
            'payment_address' => 'Ilica 1',
            'payment_zip' => '10000',
            'payment_city' => 'Zagreb',
            'payment_phone' => '0912345678',
            'payment_email' => 'ana@example.test',
            'payment_method' => 'Kartice',
            'payment_code' => 'corvus',
            'payment_card' => null,
            'payment_installment' => 0,
            'shipping_fname' => 'Ana',
            'shipping_lname' => 'Anić',
            'shipping_address' => 'Ilica 1',
            'shipping_zip' => '10000',
            'shipping_city' => 'Zagreb',
            'shipping_phone' => '091 234 5678',
            'shipping_email' => 'ana@example.test',
            'shipping_method' => 'Wolt Drive',
            'shipping_code' => WoltDriveService::CARRIER,
            'shipping_carrier' => null,
            'shipping_parcel_id' => null,
            'shipping_tracking_status_code' => null,
            'shipping_tracking_status' => null,
            'shipping_tracking_payload' => null,
            'company' => '',
            'oib' => '',
            'comment' => '',
            'tracking_code' => '',
            'shipped' => false,
            'printed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function webhookClaims(): array
    {
        return [
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
            'payload' => [
                'type' => 'order.delivered',
                'dispatched_at' => now()->toIso8601String(),
                'details' => [
                    'id' => 'event-webhook-1',
                    'venue_id' => 'VENUE-TEST',
                    'wolt_order_reference_id' => 'wolt-reference-webhook',
                    'tracking_reference' => 'tracking-webhook',
                    'merchant_order_reference_id' => 'BIBLOS-321',
                    'order_number' => '321',
                ],
            ],
        ];
    }

    private function jwt(array $claims, string $secret): string
    {
        $header = $this->base64Url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = $this->base64Url(json_encode($claims));
        $signature = hash_hmac('sha256', $header . '.' . $payload, $secret, true);

        return $header . '.' . $payload . '.' . $this->base64Url($signature);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
