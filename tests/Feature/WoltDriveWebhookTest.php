<?php

namespace Tests\Feature;

use App\Models\Back\Orders\Order;
use App\Models\Back\Settings\Settings;
use App\Services\Shipping\WoltDriveService;
use App\Services\Shipping\WoltDriveSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WoltDriveWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Settings::insert('test', 'wolt-webhook-cache-reset', '1', false);
        app(WoltDriveSettingsService::class)->save([
            'module_enabled' => true,
            'environment' => 'development',
            'api_key' => 'wolt-api-secret',
            'webhook_secret' => 'wolt-webhook-secret',
            'venue_id' => 'VENUE-WEBHOOK',
            'merchant_id' => 'MERCHANT-WEBHOOK',
            'availability_cache_seconds' => 0,
            'preparation_time_minutes' => 20,
            'request_timeout_seconds' => 10,
            'fallback_weight_grams' => 500,
            'cod_enabled' => false,
            'pricing_mode' => 'fixed',
            'quote_markup_percent' => 0,
            'max_quote_price' => 0,
            'support_url' => 'https://example.test/contact',
            'support_email' => 'support@example.test',
            'support_phone' => '+385 91 111 2222',
        ]);
        Mail::fake();
    }

    public function test_signed_delivered_webhook_updates_order_once_and_marks_it_shipped(): void
    {
        $orderId = $this->createOrder([
            'shipping_tracking_status_code' => 'order.dropoff_started',
            'shipping_tracking_status' => 'Pošiljka je na putu prema kupcu.',
            'shipping_tracking_updated_at' => now()->subMinute(),
            'shipping_tracking_payload' => json_encode([
                '_wolt_create' => ['state' => 'completed'],
            ]),
        ]);
        $token = $this->token($this->claims($orderId, [
            'type' => 'order.delivered',
            'event_id' => 'event-delivered-1',
        ]));

        $this->postJson(route('api.wolt.webhook'), ['token' => $token])
            ->assertOk()
            ->assertExactJson(['received' => true, 'updated' => true]);

        $order = Order::findOrFail($orderId);
        $this->assertSame('order.delivered', $order->shipping_tracking_status_code);
        $this->assertTrue((bool) $order->shipped);
        $this->assertSame('event-delivered-1', data_get(
            $order->shipping_tracking_payload,
            'last_webhook.id'
        ));
        $this->assertSame('completed', data_get(
            $order->shipping_tracking_payload,
            '_wolt_create.state'
        ));
        $this->assertSame(1, DB::table('order_history')->where('order_id', $orderId)->count());

        $this->postJson(route('api.wolt.webhook'), ['token' => $token])
            ->assertOk()
            ->assertExactJson(['received' => true, 'updated' => false]);

        $this->assertSame(1, DB::table('order_history')->where('order_id', $orderId)->count());
    }

    public function test_older_webhook_cannot_overwrite_newer_tracking_state(): void
    {
        $orderId = $this->createOrder([
            'shipping_tracking_status_code' => 'order.picked_up',
            'shipping_tracking_status' => 'Wolt dostavljač preuzeo je pošiljku.',
            'shipping_tracking_updated_at' => now(),
        ]);
        $token = $this->token($this->claims($orderId, [
            'type' => 'order.received',
            'event_id' => 'event-stale-1',
            'dispatched_at' => now()->subMinutes(5)->toIso8601String(),
        ]));

        $this->postJson(route('api.wolt.webhook'), ['token' => $token])
            ->assertOk()
            ->assertExactJson(['received' => true, 'updated' => false]);

        $order = Order::findOrFail($orderId);
        $this->assertSame('order.picked_up', $order->shipping_tracking_status_code);
        $this->assertSame('Wolt dostavljač preuzeo je pošiljku.', $order->shipping_tracking_status);
    }

    public function test_delivered_precedence_rejects_even_newer_non_terminal_event(): void
    {
        $orderId = $this->createOrder([
            'shipping_tracking_status_code' => 'order.delivered',
            'shipping_tracking_status' => 'Pošiljka je dostavljena.',
            'shipping_tracking_updated_at' => now()->subMinute(),
            'shipped' => true,
        ]);
        $token = $this->token($this->claims($orderId, [
            'type' => 'order.location_updated',
            'event_id' => 'event-after-delivery',
            'dispatched_at' => now()->toIso8601String(),
        ]));

        $this->postJson(route('api.wolt.webhook'), ['token' => $token])
            ->assertOk()
            ->assertExactJson(['received' => true, 'updated' => false]);

        $this->assertSame('order.delivered', Order::findOrFail($orderId)->shipping_tracking_status_code);
    }

    public function test_webhook_can_resolve_order_by_parcel_reference_without_local_order_number(): void
    {
        $orderId = $this->createOrder();
        $claims = $this->claims($orderId, [
            'type' => 'order.picked_up',
            'event_id' => 'event-reference-lookup',
        ]);
        unset(
            $claims['payload']['details']['order_number'],
            $claims['payload']['details']['merchant_order_reference_id']
        );

        $this->postJson(route('api.wolt.webhook'), [
            'token' => $this->token($claims),
        ])->assertOk()->assertExactJson(['received' => true, 'updated' => true]);

        $this->assertSame('order.picked_up', Order::findOrFail($orderId)->shipping_tracking_status_code);
    }

    public function test_unknown_or_mismatched_reference_is_acknowledged_without_updating_order(): void
    {
        $orderId = $this->createOrder();
        $claims = $this->claims($orderId, [
            'event_id' => 'event-reference-mismatch',
            'reference' => 'different-remote-reference',
        ]);

        $this->postJson(route('api.wolt.webhook'), [
            'token' => $this->token($claims),
        ])->assertOk()->assertExactJson(['received' => true, 'updated' => false]);

        $this->assertSame('INFO_RECEIVED', Order::findOrFail($orderId)->shipping_tracking_status_code);

        $unknown = $this->claims(999999, [
            'event_id' => 'event-unknown-order',
            'reference' => 'unknown-reference',
        ]);
        $this->postJson(route('api.wolt.webhook'), [
            'token' => $this->token($unknown),
        ])->assertOk()->assertExactJson(['received' => true, 'updated' => false]);
    }

    public function test_webhook_rejects_missing_invalid_signature_and_wrong_venue(): void
    {
        $this->postJson(route('api.wolt.webhook'), [])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Missing webhook token.']);

        $claims = $this->claims(123, ['event_id' => 'event-invalid-signature']);
        $this->postJson(route('api.wolt.webhook'), [
            'token' => $this->token($claims, 'wrong-secret'),
        ])->assertUnauthorized()->assertJsonFragment([
            'message' => 'Invalid webhook token.',
        ]);

        $claims['payload']['details']['venue_id'] = 'OTHER-VENUE';
        $this->postJson(route('api.wolt.webhook'), [
            'token' => $this->token($claims),
        ])->assertStatus(403)->assertJsonFragment([
            'message' => 'Invalid webhook token.',
        ]);
    }

    private function claims(int $orderId, array $overrides = []): array
    {
        $reference = (string) ($overrides['reference'] ?? 'wolt-reference-webhook');
        $type = (string) ($overrides['type'] ?? 'order.received');
        $eventId = (string) ($overrides['event_id'] ?? 'event-webhook');
        $dispatchedAt = (string) ($overrides['dispatched_at'] ?? now()->toIso8601String());

        return [
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
            'payload' => [
                'type' => $type,
                'dispatched_at' => $dispatchedAt,
                'details' => [
                    'id' => $eventId,
                    'venue_id' => 'VENUE-WEBHOOK',
                    'wolt_order_reference_id' => $reference,
                    'tracking_reference' => 'tracking-webhook',
                    'merchant_order_reference_id' => 'BIBLOS-' . $orderId,
                    'order_number' => (string) $orderId,
                ],
            ],
        ];
    }

    private function token(array $claims, string $secret = 'wolt-webhook-secret'): string
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
            'shipping_phone' => '0912345678',
            'shipping_email' => 'ana@example.test',
            'shipping_method' => 'Wolt Drive',
            'shipping_code' => WoltDriveService::CARRIER,
            'shipping_carrier' => WoltDriveService::CARRIER,
            'shipping_parcel_id' => 'wolt-reference-webhook',
            'shipping_tracking_url' => 'https://tracking.example.test/tracking-webhook',
            'shipping_tracking_status_code' => 'INFO_RECEIVED',
            'shipping_tracking_status' => 'Wolt je zaprimio podatke o dostavi.',
            'shipping_tracking_updated_at' => now()->subMinutes(2),
            'shipping_tracking_payload' => null,
            'company' => '',
            'oib' => '',
            'comment' => '',
            'tracking_code' => 'tracking-webhook',
            'shipped' => false,
            'printed' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
