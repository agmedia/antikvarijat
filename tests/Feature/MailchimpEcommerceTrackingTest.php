<?php

namespace Tests\Feature;

use App\Models\Back\Orders\Order;
use App\Services\MailchimpAttributionService;
use App\Services\MailchimpEcommerceService;
use App\Services\MailchimpOrderSynchronizer;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use ReflectionProperty;
use RuntimeException;
use Tests\TestCase;

class MailchimpEcommerceTrackingTest extends TestCase
{
    /** @var int */
    private $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'app.url' => 'https://shop.example.test',
            'services.mailchimp.api_key' => 'test-secret-us7',
            'services.mailchimp.server_prefix' => 'us7',
            'services.mailchimp.audience_id' => 'audience-123',
            'services.mailchimp.ecommerce_store_id' => 'store-123',
            'services.mailchimp.ecommerce_store_name' => 'Biblos test store',
            'services.mailchimp.ecommerce_currency_code' => 'EUR',
            'services.mailchimp.ecommerce_automations_enabled' => false,
            'services.mailchimp.ecommerce_sync_from' => '2026-08-28 00:00:00',
            'services.mailchimp.storefront_url' => 'https://shop.example.test/knjige',
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        $this->createTables();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_attribution_accepts_only_a_valid_campaign_cookie_and_preserves_it_when_cookie_is_missing(): void
    {
        $order = $this->makeOrder([
            'checkout_processed_at' => null,
            'mailchimp_campaign_id' => null,
        ]);
        $service = app(MailchimpAttributionService::class);

        $validRequest = Request::create('/', 'GET', [], [
            MailchimpAttributionService::CAMPAIGN_COOKIE => 'AbC_123-test',
        ]);

        $this->assertTrue($service->attachToOrder($order->id, $validRequest));
        $this->assertSame('AbC_123-test', $order->fresh()->mailchimp_campaign_id);

        $invalidRequest = Request::create('/', 'GET', [], [
            MailchimpAttributionService::CAMPAIGN_COOKIE => 'campaign<script>',
        ]);

        $this->assertFalse($service->attachToOrder($order->id, $invalidRequest));
        $this->assertSame('AbC_123-test', $order->fresh()->mailchimp_campaign_id);

        DB::table('orders')->where('id', $order->id)->update([
            'mailchimp_campaign_id' => 'previous-campaign',
        ]);

        $this->assertFalse($service->attachToOrder($order->id, Request::create('/', 'GET')));
        $this->assertSame('previous-campaign', $order->fresh()->mailchimp_campaign_id);

        $withdrawnConsentRequest = Request::create('/', 'GET', [], [
            'biblos_marketing_consent' => 'denied',
        ]);

        $this->assertFalse($service->attachToOrder($order->id, $withdrawnConsentRequest));
        $this->assertNull($order->fresh()->mailchimp_campaign_id);

        DB::table('orders')->where('id', $order->id)->update([
            'checkout_processed_at' => now(),
            'mailchimp_campaign_id' => 'locked-after-checkout',
        ]);

        $this->assertFalse($service->attachToOrder($order->id, $validRequest));
        $this->assertSame('locked-after-checkout', $order->fresh()->mailchimp_campaign_id);
        $this->assertFalse($service->attachToOrder($order->id, Request::create('/', 'GET')));
        $this->assertSame('locked-after-checkout', $order->fresh()->mailchimp_campaign_id);
    }

    public function test_campaign_landing_boots_consent_immediately_and_keeps_the_pending_campaign_id(): void
    {
        $source = file_get_contents(
            resource_path('views/front/layouts/partials/cookie-consent.blade.php')
        );

        $snapshotDeclaration = strpos($source, 'const pendingMailchimpCampaignId =');
        $attributionSync = strpos($source, 'const syncMailchimpAttribution =');

        $this->assertNotFalse($snapshotDeclaration);
        $this->assertNotFalse($attributionSync);
        $this->assertLessThan(
            $attributionSync,
            $snapshotDeclaration,
            'mc_cid mora biti snapshotan prije asinkronog CookieConsent lifecyclea.'
        );
        $this->assertStringContainsString("searchParams.get('mc_cid')", $source);
        $this->assertStringContainsString('biblos_marketing_consent', $source);
        $this->assertStringContainsString('denied', $source);
        $this->assertGreaterThanOrEqual(
            3,
            substr_count($source, 'pendingMailchimpCampaignId'),
            'Snapshot mora se ponovno koristiti kod spremanja cookieja i odluke o ranom bootu.'
        );
        $this->assertMatchesRegularExpression(
            '/hasStoredCookieConsent\(\)\s*\|\|\s*validMailchimpIdentifier\(pendingMailchimpCampaignId\)/',
            $source,
            'Landing s mc_cid mora odmah bootati consent, bez čekanja prvog klika ili 6 sekundi.'
        );
    }

    public function test_plain_marketing_consent_cookie_is_available_to_the_server(): void
    {
        $middleware = app(\App\Http\Middleware\EncryptCookies::class);
        $except = new ReflectionProperty($middleware, 'except');
        $except->setAccessible(true);

        $this->assertContains(
            'biblos_marketing_consent',
            $except->getValue($middleware),
            'Client-side consent cookie mora biti izuzet od Laravel dekripcije.'
        );
    }

    public function test_service_creates_store_then_customer_product_and_attributed_order_with_safe_payloads(): void
    {
        Carbon::setTestNow('2026-08-28 07:20:00');
        $order = $this->makeOrder([], true);
        $requests = [];

        $this->fakeSuccessfulMailchimp($requests);

        $this->assertSame(' Buyer@Example.test ', $order->payment_email);
        $result = app(MailchimpEcommerceService::class)->syncOrder($order);

        $this->assertTrue($result['ok'], json_encode($result));
        $this->assertSame('paid', $result['financial_status']);
        $this->assertSame([
            'GET',
            'POST',
            'PUT',
            'PUT',
            'PUT',
        ], array_column($requests, 'method'));

        $store = $requests[1];
        $this->assertSame('https://us7.api.mailchimp.com/3.0/ecommerce/stores', $store['url']);
        $this->assertSame('store-123', $store['data']['id']);
        $this->assertSame('audience-123', $store['data']['list_id']);
        $this->assertSame('Biblos test store', $store['data']['name']);
        $this->assertSame('EUR', $store['data']['currency_code']);
        $this->assertTrue($store['data']['is_syncing']);
        $this->assertArrayNotHasKey('domain', $store['data']);
        $this->assertArrayNotHasKey('platform', $store['data']);

        $customer = $requests[2];
        $this->assertStringContainsString('/customers/' . md5('buyer@example.test'), $customer['url']);
        $this->assertSame('buyer@example.test', $customer['data']['email_address']);
        $this->assertFalse($customer['data']['opt_in_status']);

        $product = $requests[3];
        $this->assertStringContainsString('/products/501', $product['url']);
        $this->assertSame('501', $product['data']['id']);
        $this->assertSame('501', $product['data']['variants'][0]['id']);

        $remoteOrder = $requests[4];
        $this->assertSame(
            'https://us7.api.mailchimp.com/3.0/ecommerce/stores/store-123/orders/' . $order->id,
            $remoteOrder['url']
        );
        $this->assertSame('campaign_abc', $remoteOrder['data']['campaign_id']);
        $this->assertSame('paid', $remoteOrder['data']['financial_status']);
        $this->assertSame(
            $order->fresh()->checkout_processed_at->toIso8601String(),
            $remoteOrder['data']['processed_at_foreign']
        );
        $this->assertSame(9.5, $remoteOrder['data']['tax_total']);
        $this->assertSame(4.9, $remoteOrder['data']['shipping_total']);
        $this->assertSame(['id' => md5('buyer@example.test')], $remoteOrder['data']['customer']);
        $this->assertSame('501', $remoteOrder['data']['lines'][0]['product_id']);
        $this->assertSame(2, $remoteOrder['data']['lines'][0]['quantity']);
        $this->assertSame(19.95, $remoteOrder['data']['lines'][0]['price']);
        $this->assertTrue($requests[0]['authenticated']);
        Http::assertSentCount(5);
    }

    public function test_synchronizer_records_success_metadata_and_does_not_send_an_unchanged_order_twice(): void
    {
        Carbon::setTestNow('2026-08-28 07:30:00');
        $order = $this->makeOrder([], true);
        $requests = [];
        $this->fakeSuccessfulMailchimp($requests);
        $synchronizer = app(MailchimpOrderSynchronizer::class);

        $first = $synchronizer->syncOrderId($order->id);

        $this->assertTrue($first['ok']);
        $this->assertFalse($first['skipped']);
        $this->assertFalse($first['stop']);
        $order->refresh();
        $this->assertSame('2026-08-28 07:30:00', $order->mailchimp_ecommerce_synced_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-28 07:30:00', $order->mailchimp_ecommerce_last_attempt_at->format('Y-m-d H:i:s'));
        $this->assertSame('paid', $order->mailchimp_ecommerce_financial_status);
        $this->assertNull($order->mailchimp_ecommerce_last_error);
        $this->assertCount(5, $requests);

        Carbon::setTestNow('2026-08-28 07:31:00');
        $second = $synchronizer->syncOrderId($order->id);

        $this->assertTrue($second['ok']);
        $this->assertTrue($second['skipped']);
        $this->assertCount(5, $requests);
        $this->assertSame('2026-08-28 07:30:00', $order->fresh()->mailchimp_ecommerce_synced_at->format('Y-m-d H:i:s'));
    }

    public function test_unauthorized_mailchimp_failure_is_fail_open_and_retries_only_after_backoff(): void
    {
        Carbon::setTestNow('2026-08-28 07:40:00');
        $order = $this->makeOrder([], true);
        $originalUpdatedAt = $order->updated_at->format('Y-m-d H:i:s');

        Http::fake(function (ClientRequest $request) {
            if ($request->method() === 'GET'
                && $request->url() === 'https://us7.api.mailchimp.com/3.0/ecommerce/stores/store-123') {
                return Http::response([
                    'title' => 'API Key Invalid',
                    'detail' => 'The provided API key for buyer@example.test is invalid.',
                ], 403);
            }

            throw new RuntimeException('Unexpected Mailchimp request: ' . $request->method() . ' ' . $request->url());
        });

        $synchronizer = app(MailchimpOrderSynchronizer::class);
        $result = $synchronizer->syncOrderId($order->id);

        $this->assertFalse($result['ok']);
        $this->assertFalse($result['skipped']);
        $this->assertTrue($result['stop']);
        $this->assertStringContainsString('403', (string) $result['error']);
        $this->assertStringNotContainsString('test-secret-us7', (string) $result['error']);
        $this->assertStringNotContainsString('buyer@example.test', (string) $result['error']);

        $order->refresh();
        $this->assertSame(3, (int) $order->order_status_id);
        $this->assertSame(49.9, (float) $order->total);
        $this->assertSame($originalUpdatedAt, $order->updated_at->format('Y-m-d H:i:s'));
        $this->assertNull($order->mailchimp_ecommerce_synced_at);
        $this->assertSame('2026-08-28 07:40:00', $order->mailchimp_ecommerce_last_attempt_at->format('Y-m-d H:i:s'));
        $this->assertStringContainsString('403', (string) $order->mailchimp_ecommerce_last_error);
        $this->assertStringNotContainsString('buyer@example.test', (string) $order->mailchimp_ecommerce_last_error);
        $this->assertCount(0, $synchronizer->pendingOrders(5));

        Carbon::setTestNow('2026-08-28 07:56:00');
        $this->assertSame([$order->id], $synchronizer->pendingOrders(5)->pluck('id')->all());
        Http::assertSentCount(1);
    }

    public function test_pending_orders_includes_recent_unattributed_orders_and_excludes_pre_rollout_history(): void
    {
        Carbon::setTestNow('2026-08-28 08:00:00');
        $attributed = $this->makeOrder(['mailchimp_campaign_id' => 'campaign-new']);
        $recentUnattributed = $this->makeOrder(['mailchimp_campaign_id' => null]);
        $preRollout = $this->makeOrder([
            'checkout_processed_at' => '2026-08-27 23:59:59',
            'mailchimp_campaign_id' => null,
        ]);

        $pendingIds = app(MailchimpOrderSynchronizer::class)
            ->pendingOrders(25)
            ->pluck('id')
            ->all();

        $this->assertSame([$attributed->id, $recentUnattributed->id], $pendingIds);
        $this->assertNotContains($preRollout->id, $pendingIds);
        Http::assertNothingSent();
    }

    public function test_command_syncs_recent_orders_for_native_attribution_but_not_pre_rollout_history(): void
    {
        Carbon::setTestNow('2026-08-28 08:00:00');
        $attributed = $this->makeOrder(['mailchimp_campaign_id' => 'campaign-command'], true);
        $recentUnattributed = $this->makeOrder(['mailchimp_campaign_id' => null], true);
        $preRollout = $this->makeOrder([
            'checkout_processed_at' => '2026-08-27 23:59:59',
            'mailchimp_campaign_id' => null,
        ], true);
        $requests = [];
        $this->fakeSuccessfulMailchimp($requests);

        $this->artisan('mailchimp:sync-ecommerce-orders', [
            '--limit' => 25,
            '--max-seconds' => 50,
        ])
            ->expectsOutput('Mailchimp e-commerce sync završen. Sinkronizirano: 2, neuspjelo: 0, preskočeno: 0.')
            ->assertExitCode(0);
        $this->assertNotNull($attributed->fresh()->mailchimp_ecommerce_synced_at);
        $this->assertSame('paid', $attributed->fresh()->mailchimp_ecommerce_financial_status);
        $this->assertNotNull($recentUnattributed->fresh()->mailchimp_ecommerce_synced_at);
        $this->assertSame('paid', $recentUnattributed->fresh()->mailchimp_ecommerce_financial_status);
        $this->assertNull($preRollout->fresh()->mailchimp_ecommerce_synced_at);
        $this->assertNull($preRollout->fresh()->mailchimp_ecommerce_last_attempt_at);
        $this->assertCount(1, array_filter($requests, function (array $request) use ($attributed) {
            return $request['method'] === 'PUT'
                && substr($request['url'], -strlen('/orders/' . $attributed->id)) === '/orders/' . $attributed->id;
        }));
        $recentRemoteOrder = collect($requests)->first(function (array $request) use ($recentUnattributed) {
            return $request['method'] === 'PUT'
                && substr($request['url'], -strlen('/orders/' . $recentUnattributed->id))
                    === '/orders/' . $recentUnattributed->id;
        });
        $this->assertNotNull($recentRemoteOrder);
        $this->assertArrayNotHasKey('campaign_id', $recentRemoteOrder['data']);
        $this->assertCount(0, array_filter($requests, function (array $request) use ($preRollout) {
            return $request['method'] === 'PUT'
                && substr($request['url'], -strlen('/orders/' . $preRollout->id)) === '/orders/' . $preRollout->id;
        }));
    }

    public function test_command_provisions_store_even_when_there_are_no_attributed_orders(): void
    {
        $historical = $this->makeOrder([
            'checkout_processed_at' => '2026-08-27 23:59:59',
            'mailchimp_campaign_id' => null,
        ], true);
        $requests = [];
        $this->fakeSuccessfulMailchimp($requests);

        $this->artisan('mailchimp:sync-ecommerce-orders', [
            '--limit' => 25,
            '--max-seconds' => 50,
        ])
            ->expectsOutput('Mailchimp e-commerce sync završen. Sinkronizirano: 0, neuspjelo: 0, preskočeno: 0.')
            ->assertExitCode(0);

        $this->assertSame(['GET', 'POST'], array_column($requests, 'method'));
        $this->assertSame(
            'https://us7.api.mailchimp.com/3.0/ecommerce/stores/store-123',
            $requests[0]['url']
        );
        $this->assertSame(
            'https://us7.api.mailchimp.com/3.0/ecommerce/stores',
            $requests[1]['url']
        );
        $this->assertNull($historical->fresh()->mailchimp_ecommerce_synced_at);
        $this->assertNull($historical->fresh()->mailchimp_ecommerce_last_attempt_at);
        Http::assertSentCount(2);
    }

    private function createTables(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_status_id');
            $table->string('payment_email')->nullable();
            $table->string('payment_fname')->nullable();
            $table->string('payment_lname')->nullable();
            $table->decimal('total', 15, 4)->default(0);
            $table->timestamp('checkout_processed_at')->nullable();
            $table->string('mailchimp_campaign_id', 100)->nullable();
            $table->timestamp('mailchimp_ecommerce_synced_at')->nullable();
            $table->string('mailchimp_ecommerce_financial_status', 20)->nullable();
            $table->timestamp('mailchimp_ecommerce_last_attempt_at')->nullable();
            $table->text('mailchimp_ecommerce_last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->integer('quantity')->default(0);
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::create('order_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->string('name');
            $table->integer('quantity');
            $table->decimal('org_price', 15, 4)->nullable();
            $table->decimal('discount', 15, 4)->nullable();
            $table->decimal('price', 15, 4);
            $table->decimal('total', 15, 4);
            $table->timestamps();
        });

        Schema::create('order_total', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->string('code');
            $table->string('title');
            $table->decimal('value', 15, 4);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    private function makeOrder(array $overrides = [], bool $withLines = false): Order
    {
        $this->sequence++;

        $attributes = array_merge([
            'order_status_id' => 3,
            'payment_email' => ' Buyer@Example.test ',
            'payment_fname' => 'Ana',
            'payment_lname' => 'Anić',
            'total' => 49.9,
            'checkout_processed_at' => now(),
            'mailchimp_campaign_id' => 'campaign_abc',
            'mailchimp_ecommerce_synced_at' => null,
            'mailchimp_ecommerce_financial_status' => null,
            'mailchimp_ecommerce_last_attempt_at' => null,
            'mailchimp_ecommerce_last_error' => null,
        ], $overrides, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderId = DB::table('orders')->insertGetId($attributes);
        $order = Order::query()->findOrFail($orderId);

        if ($withLines) {
            DB::table('products')->insert([
                'id' => 500 + $this->sequence,
                'name' => 'Test knjiga',
                'quantity' => 7,
                'description' => '<p>Opis testne knjige.</p>',
                'image' => 'image/test.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('order_products')->insert([
                'order_id' => $order->id,
                'product_id' => 500 + $this->sequence,
                'name' => 'Test knjiga',
                'quantity' => 2,
                'org_price' => 19.95,
                'discount' => null,
                'price' => 19.95,
                'total' => 39.9,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('order_total')->insert([
                [
                    'order_id' => $order->id,
                    'code' => 'tax',
                    'title' => 'PDV',
                    'value' => 9.5,
                    'sort_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'order_id' => $order->id,
                    'code' => 'shipping',
                    'title' => 'Dostava',
                    'value' => 4.9,
                    'sort_order' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        return $order;
    }

    /**
     * @param array<int,array{method:string,url:string,data:array,authenticated:bool}> $requests
     */
    private function fakeSuccessfulMailchimp(array &$requests): void
    {
        Http::fake(function (ClientRequest $request) use (&$requests) {
            $requests[] = [
                'method' => $request->method(),
                'url' => $request->url(),
                'data' => $request->data(),
                'authenticated' => $request->hasHeader(
                    'Authorization',
                    'Basic ' . base64_encode('anystring:test-secret-us7')
                ),
            ];

            if ($request->method() === 'GET'
                && $request->url() === 'https://us7.api.mailchimp.com/3.0/ecommerce/stores/store-123') {
                return Http::response([
                    'title' => 'Resource Not Found',
                    'detail' => 'The requested store does not exist.',
                ], 404);
            }

            if ($request->method() === 'POST'
                && $request->url() === 'https://us7.api.mailchimp.com/3.0/ecommerce/stores') {
                return Http::response(['id' => 'store-123'], 200);
            }

            if ($request->method() === 'PUT'
                && strpos($request->url(), 'https://us7.api.mailchimp.com/3.0/ecommerce/stores/store-123/customers/') === 0) {
                return Http::response(['id' => basename($request->url())], 200);
            }

            if ($request->method() === 'PUT'
                && strpos($request->url(), 'https://us7.api.mailchimp.com/3.0/ecommerce/stores/store-123/products/') === 0) {
                return Http::response(['id' => basename($request->url())], 200);
            }

            if ($request->method() === 'PUT'
                && strpos($request->url(), 'https://us7.api.mailchimp.com/3.0/ecommerce/stores/store-123/orders/') === 0) {
                return Http::response(['id' => basename($request->url())], 200);
            }

            throw new RuntimeException('Unexpected Mailchimp request: ' . $request->method() . ' ' . $request->url());
        });
    }
}
