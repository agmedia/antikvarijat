<?php

namespace Tests\Feature;

use App\Mail\ShippingTrackingAvailable;
use App\Models\Back\Orders\Order;
use App\Services\Shipping\GlsTrackingService;
use App\Services\Shipping\OrderTrackingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ShippingTrackingAvailableEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracking_email_is_sent_when_gls_tracking_number_first_appears(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-08-11 10:15:00');
        $orderId = $this->createOrder();

        app(OrderTrackingService::class)->apply(Order::findOrFail($orderId), [
            'carrier' => GlsTrackingService::CARRIER,
            'parcel_id' => 'PARCEL-123',
            'tracking_code' => '123456789',
            'tracking_url' => 'https://gls.example.test/?match=123456789',
            'status_code' => '51',
            'status' => 'Podaci o pošiljci su uneseni u GLS sustav.',
            'tracked_at' => now(),
            'payload' => ['test' => true],
        ]);

        Mail::assertSent(ShippingTrackingAvailable::class, function (ShippingTrackingAvailable $mail) use ($orderId) {
            $rendered = view('emails.shipping-tracking-available', [
                'order' => $mail->order,
                'carrierLabel' => $mail->carrierLabel,
            ])->render();

            return $mail->hasTo('tracking@example.com')
                && (int) $mail->order->id === $orderId
                && $mail->build()->subject === 'Vaša pošiljka je poslana - Antikvarijat Biblos'
                && str_contains($rendered, '123456789')
                && str_contains($rendered, 'Prati pošiljku')
                && str_contains($rendered, 'class="mail-shell"')
                && str_contains($rendered, 'Vaša pošiljka je na putu');
        });

        $this->assertNotNull(DB::table('orders')->where('id', $orderId)->value('shipping_tracking_email_sent_at'));
        $this->assertDatabaseHas('order_history', [
            'order_id' => $orderId,
            'user_id' => 0,
            'comment' => 'Kupcu poslan email s podacima za praćenje pošiljke. Broj pošiljke: 123456789.',
        ]);
    }

    public function test_later_status_changes_are_stored_without_resending_the_email(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-08-11 10:15:00');
        $orderId = $this->createOrder();
        $service = app(OrderTrackingService::class);

        $service->apply(Order::findOrFail($orderId), [
            'carrier' => GlsTrackingService::CARRIER,
            'tracking_code' => '123456789',
            'tracking_url' => 'https://gls.example.test/?match=123456789',
            'status_code' => '51',
            'status' => 'Podaci o pošiljci su uneseni u GLS sustav.',
            'tracked_at' => now(),
            'payload' => [],
        ]);

        Carbon::setTestNow('2026-08-11 11:15:00');

        $service->apply(Order::findOrFail($orderId), [
            'carrier' => GlsTrackingService::CARRIER,
            'tracking_code' => '123456789',
            'tracking_url' => 'https://gls.example.test/?match=123456789',
            'status_code' => '4',
            'status' => 'Pošiljka je planirana za dostavu tijekom dana.',
            'tracked_at' => now(),
            'payload' => [],
        ]);

        Mail::assertSent(ShippingTrackingAvailable::class, 1);
        $this->assertDatabaseHas('order_history', [
            'order_id' => $orderId,
            'comment' => 'Tracking update (GLS): Pošiljka je planirana za dostavu tijekom dana. Broj pošiljke: 123456789.',
        ]);
    }

    public function test_tracking_email_can_be_sent_manually_for_an_existing_tracking_number(): void
    {
        Mail::fake();
        $orderId = $this->createOrder([
            'tracking_code' => '123456789',
            'shipping_tracking_url' => 'https://gls.example.test/?match=123456789',
            'shipping_tracking_status' => 'Pošiljka je predana GLS-u.',
        ]);

        $result = app(OrderTrackingService::class)
            ->sendTrackingAvailableMailManually(Order::findOrFail($orderId));

        $this->assertTrue($result['sent']);
        Mail::assertSent(ShippingTrackingAvailable::class, 1);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
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
            'payment_phone' => null,
            'payment_email' => 'tracking@example.com',
            'payment_method' => 'Kartice',
            'payment_code' => 'card',
            'payment_card' => null,
            'payment_installment' => 0,
            'shipping_fname' => 'Test',
            'shipping_lname' => 'Kupac',
            'shipping_address' => 'Test ulica 1',
            'shipping_zip' => '10000',
            'shipping_city' => 'Zagreb',
            'shipping_phone' => null,
            'shipping_email' => 'tracking@example.com',
            'shipping_method' => 'GLS dostava',
            'shipping_code' => 'gls',
            'shipping_carrier' => GlsTrackingService::CARRIER,
            'company' => '',
            'oib' => '',
            'comment' => null,
            'tracking_code' => '',
            'shipped' => false,
            'printed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
