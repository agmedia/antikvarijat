<?php

namespace Tests\Feature;

use App\Mail\ShippingTrackingAvailable;
use App\Models\Back\Orders\Order;
use App\Models\Back\Settings\Settings;
use App\Models\User;
use App\Services\Shipping\WoltDriveAmbiguousCreateException;
use App\Services\Shipping\WoltDriveService;
use App\Services\Shipping\WoltDriveSettingsService;
use Bouncer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Silber\Bouncer\Database\Role;
use Tests\TestCase;

class WoltDriveShipmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        Settings::insert('test', 'wolt-shipment-cache-reset', '1', false);
        $this->saveSettings();
        Mail::fake();
    }

    public function test_admin_create_persists_standard_tracking_and_repeated_request_is_idempotent(): void
    {
        $wolt = Mockery::mock(WoltDriveService::class);
        $wolt->shouldReceive('createDelivery')->once()->andReturn($this->tracking());
        $this->app->instance(WoltDriveService::class, $wolt);
        $orderId = $this->createOrder();
        $admin = $this->administrator();

        $this->actingAs($admin)
            ->postJson(route('api.order.send.wolt'), ['order_id' => $orderId])
            ->assertOk()
            ->assertJsonFragment([
                'message' => 'Wolt Drive dostava uspješno je kreirana: tracking-123',
            ]);

        $order = Order::findOrFail($orderId);
        $this->assertSame(WoltDriveService::CARRIER, $order->shipping_carrier);
        $this->assertSame('wolt-reference-123', $order->shipping_parcel_id);
        $this->assertSame('tracking-123', $order->tracking_code);
        $this->assertSame('INFO_RECEIVED', $order->shipping_tracking_status_code);

        $this->actingAs($admin)
            ->postJson(route('api.order.send.wolt'), ['order_id' => $orderId])
            ->assertOk()
            ->assertJsonFragment([
                'message' => 'Wolt Drive dostava već je kreirana: tracking-123',
            ]);

        Mail::assertSent(ShippingTrackingAvailable::class, 1);
    }

    public function test_parallel_create_is_rejected_before_service_call(): void
    {
        $orderId = $this->createOrder();
        $lock = Cache::lock('wolt-delivery-create:' . $orderId, 180);
        $this->assertTrue($lock->get());
        $wolt = Mockery::mock(WoltDriveService::class);
        $wolt->shouldNotReceive('createDelivery');
        $this->app->instance(WoltDriveService::class, $wolt);

        try {
            $this->actingAs($this->administrator())
                ->postJson(route('api.order.send.wolt'), ['order_id' => $orderId])
                ->assertStatus(409)
                ->assertJsonFragment([
                    'error' => 'Kreiranje Wolt Drive dostave za ovu narudžbu već je u tijeku.',
                ]);
        } finally {
            $lock->release();
        }
    }

    public function test_create_rejects_wrong_carrier_and_ineligible_prepaid_statuses(): void
    {
        $wolt = Mockery::mock(WoltDriveService::class);
        $wolt->shouldNotReceive('createDelivery');
        $this->app->instance(WoltDriveService::class, $wolt);
        $admin = $this->administrator();

        $wrongCarrier = $this->createOrder([
            'shipping_method' => 'GLS dostava',
            'shipping_code' => 'gls',
        ]);
        $this->actingAs($admin)
            ->postJson(route('api.order.send.wolt'), ['order_id' => $wrongCarrier])
            ->assertStatus(422)
            ->assertJsonFragment(['error' => 'Narudžba nema odabranu Wolt Drive dostavu.']);

        foreach ([
            config('settings.order.status.unfinished'),
            config('settings.order.status.declined'),
            config('settings.order.status.canceled'),
            config('settings.order.status.new'),
        ] as $status) {
            $orderId = $this->createOrder([
                'order_status_id' => (int) $status,
                'payment_code' => 'corvus',
            ]);
            $this->actingAs($admin)
                ->postJson(route('api.order.send.wolt'), ['order_id' => $orderId])
                ->assertStatus(422);
        }
    }

    public function test_new_cod_order_requires_cod_setting_and_then_can_be_dispatched(): void
    {
        $wolt = Mockery::mock(WoltDriveService::class);
        $wolt->shouldReceive('createDelivery')->once()->andReturn($this->tracking());
        $this->app->instance(WoltDriveService::class, $wolt);
        $orderId = $this->createOrder([
            'order_status_id' => (int) config('settings.order.status.new'),
            'payment_method' => 'Pouzeće',
            'payment_code' => 'cod',
        ]);
        $admin = $this->administrator();

        $this->actingAs($admin)
            ->postJson(route('api.order.send.wolt'), ['order_id' => $orderId])
            ->assertStatus(422);

        $this->saveSettings(['cod_enabled' => true]);
        $this->actingAs($admin)
            ->postJson(route('api.order.send.wolt'), ['order_id' => $orderId])
            ->assertOk();
    }

    public function test_ambiguous_create_returns_conflict_without_local_tracking_identifier(): void
    {
        $wolt = Mockery::mock(WoltDriveService::class);
        $wolt->shouldReceive('createDelivery')->once()->andThrow(
            new WoltDriveAmbiguousCreateException(
                'Unknown remote outcome.',
                'WOLT_CREATE_OUTCOME_UNKNOWN',
                504
            )
        );
        $this->app->instance(WoltDriveService::class, $wolt);
        $orderId = $this->createOrder();

        $this->actingAs($this->administrator())
            ->postJson(route('api.order.send.wolt'), ['order_id' => $orderId])
            ->assertStatus(409)
            ->assertJsonFragment([
                'error' => 'Wolt nije potvrdio ishod zahtjeva. Novi zahtjev nije poslan kako se dostava ne bi duplicirala. Provjerite narudžbu u Wolt sustavu prije ponovnog pokušaja.',
            ]);

        $order = Order::findOrFail($orderId);
        $this->assertNull($order->shipping_parcel_id);
        $this->assertSame('', (string) $order->tracking_code);
    }

    public function test_cancel_is_persisted_and_repeated_cancel_does_not_call_service_again(): void
    {
        $wolt = Mockery::mock(WoltDriveService::class);
        $wolt->shouldReceive('cancel')->once()->andReturn($this->tracking([
            'status_code' => 'REJECTED',
            'status' => 'Wolt dostava je otkazana ili odbijena.',
        ]));
        $this->app->instance(WoltDriveService::class, $wolt);
        $orderId = $this->createOrder([
            'shipping_carrier' => WoltDriveService::CARRIER,
            'shipping_parcel_id' => 'wolt-reference-123',
            'tracking_code' => 'tracking-123',
            'shipping_tracking_status_code' => 'PICKED_UP',
            'printed' => true,
        ]);
        $admin = $this->administrator();

        $this->actingAs($admin)
            ->postJson(route('api.order.cancel.wolt'), [
                'order_id' => $orderId,
                'reason' => 'Kupac je odustao.',
            ])
            ->assertOk()
            ->assertJsonFragment(['message' => 'Wolt Drive dostava je otkazana.']);

        $this->actingAs($admin)
            ->postJson(route('api.order.cancel.wolt'), [
                'order_id' => $orderId,
                'reason' => 'Ponovljeni zahtjev.',
            ])
            ->assertOk()
            ->assertJsonFragment(['message' => 'Wolt Drive dostava već je otkazana.']);

        $this->assertSame('REJECTED', Order::findOrFail($orderId)->shipping_tracking_status_code);
    }

    public function test_delivered_order_cannot_be_cancelled_and_never_calls_service(): void
    {
        $wolt = Mockery::mock(WoltDriveService::class);
        $wolt->shouldNotReceive('cancel');
        $this->app->instance(WoltDriveService::class, $wolt);
        $orderId = $this->createOrder([
            'shipping_carrier' => WoltDriveService::CARRIER,
            'shipping_parcel_id' => 'wolt-reference-123',
            'tracking_code' => 'tracking-123',
            'shipping_tracking_status_code' => 'order.delivered',
            'printed' => true,
            'shipped' => true,
        ]);

        $this->actingAs($this->administrator())
            ->postJson(route('api.order.cancel.wolt'), [
                'order_id' => $orderId,
                'reason' => 'Prekasno otkazivanje.',
            ])
            ->assertStatus(422)
            ->assertJsonFragment(['error' => 'Dostavljena Wolt Drive pošiljka više se ne može otkazati.']);
    }

    public function test_create_and_cancel_require_web_session_and_privileged_administrator(): void
    {
        $wolt = Mockery::mock(WoltDriveService::class);
        $wolt->shouldNotReceive('createDelivery');
        $wolt->shouldNotReceive('cancel');
        $this->app->instance(WoltDriveService::class, $wolt);
        $orderId = $this->createOrder();

        $this->postJson(route('api.order.send.wolt'), ['order_id' => $orderId])
            ->assertUnauthorized();

        $plain = User::factory()->create();
        $this->actingAs($plain)
            ->postJson(route('api.order.send.wolt'), ['order_id' => $orderId])
            ->assertForbidden();

        $editor = $this->userWithRole('editor');
        $this->actingAs($editor)
            ->postJson(route('api.order.send.wolt'), ['order_id' => $orderId])
            ->assertForbidden();

        auth()->logout();
        $tokenUser = $this->administrator();
        $token = $tokenUser->createToken('wolt-shipment-test')->plainTextToken;
        $this->withToken($token)
            ->postJson(route('api.order.send.wolt'), ['order_id' => $orderId])
            ->assertUnauthorized();

        $this->withToken($token)
            ->postJson(route('api.order.send.gls'), ['order_id' => $orderId])
            ->assertForbidden();
    }

    private function tracking(array $overrides = []): array
    {
        return array_merge([
            'carrier' => WoltDriveService::CARRIER,
            'parcel_id' => 'wolt-reference-123',
            'tracking_code' => 'tracking-123',
            'tracking_url' => 'https://tracking.example.test/tracking-123',
            'status_code' => 'INFO_RECEIVED',
            'status' => 'Wolt je zaprimio podatke o dostavi.',
            'tracked_at' => now(),
            'payload' => ['wolt_order_reference_id' => 'wolt-reference-123'],
            'is_delivered' => false,
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
        ], $overrides));
    }

    private function administrator(): User
    {
        return $this->userWithRole('admin');
    }

    private function userWithRole(string $roleName): User
    {
        $user = User::factory()->create();
        $user->details()->create([
            'fname' => ucfirst($roleName),
            'lname' => 'Test',
            'role' => $roleName,
            'status' => true,
        ]);
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            ['title' => ucfirst($roleName)]
        );
        Bouncer::assign($role)->to($user);
        Bouncer::refresh();

        return $user->fresh('details');
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
}
