<?php

namespace Tests\Feature;

use App\Services\MailchimpEcommerceService;
use Illuminate\Support\Facades\Http;
use ReflectionClass;
use Tests\TestCase;

class MailchimpEcommerceServiceTest extends TestCase
{
    public function test_it_creates_a_new_cart_with_post_when_the_mailchimp_cart_does_not_exist(): void
    {
        config([
            'services.mailchimp.api_key' => 'test-key-us17',
            'services.mailchimp.server_prefix' => 'us17',
            'services.mailchimp.audience_id' => 'audience123',
            'services.mailchimp.ecommerce_store_id' => 'store123',
            'services.mailchimp.storefront_url' => 'https://www.antikvarijat-biblos.hr',
        ]);

        $payloads = $this->fakeCartSyncRequests(404);

        $service = new MailchimpEcommerceService();
        $result = $service->syncCart(
            $this->cartPayload(),
            $this->customerPayload(),
            'https://www.antikvarijat-biblos.hr/naplata'
        );

        $this->assertTrue($result['ok'], $result['error'] ?? 'Mailchimp cart sync nije uspio.');
        $this->assertNotNull($payloads->cartPost);
        $this->assertNull($payloads->cartPatch);
        $this->assertSame('cart-123', $payloads->cartPost['id']);
        $this->assertSame('EUR', $payloads->cartPost['currency_code']);
        $this->assertSame('https://www.antikvarijat-biblos.hr/naplata', $payloads->cartPost['checkout_url']);
        $this->assertSame('customer@example.com', $payloads->cartPost['customer']['email_address']);
        $this->assertSame([
            [
                'id' => '101',
                'product_id' => '101',
                'product_variant_id' => '101',
                'quantity' => 2,
                'price' => 14.5,
            ],
        ], $payloads->cartPost['lines']);
    }

    public function test_it_updates_an_existing_cart_with_patch(): void
    {
        config([
            'services.mailchimp.api_key' => 'test-key-us17',
            'services.mailchimp.server_prefix' => 'us17',
            'services.mailchimp.audience_id' => 'audience123',
            'services.mailchimp.ecommerce_store_id' => 'store123',
            'services.mailchimp.storefront_url' => 'https://www.antikvarijat-biblos.hr',
        ]);

        $payloads = $this->fakeCartSyncRequests(200);

        $service = new MailchimpEcommerceService();
        $result = $service->syncCart(
            $this->cartPayload(),
            $this->customerPayload(),
            'https://www.antikvarijat-biblos.hr/naplata'
        );

        $this->assertTrue($result['ok'], $result['error'] ?? 'Mailchimp cart sync nije uspio.');
        $this->assertNull($payloads->cartPost);
        $this->assertNotNull($payloads->cartPatch);
        $this->assertArrayNotHasKey('id', $payloads->cartPatch);
        $this->assertSame('EUR', $payloads->cartPatch['currency_code']);
        $this->assertSame('https://www.antikvarijat-biblos.hr/naplata', $payloads->cartPatch['checkout_url']);
        $this->assertSame('customer@example.com', $payloads->cartPatch['customer']['email_address']);
        $this->assertSame([
            [
                'id' => '101',
                'product_id' => '101',
                'product_variant_id' => '101',
                'quantity' => 2,
                'price' => 14.5,
            ],
        ], $payloads->cartPatch['lines']);
    }

    public function test_it_normalizes_relative_product_urls_to_the_storefront_domain(): void
    {
        config([
            'services.mailchimp.storefront_url' => 'https://www.antikvarijat-biblos.hr',
        ]);

        $result = $this->invokeNormalizeStorefrontUrl('knjige/psihologija/praksa-psihoterapije');

        $this->assertSame(
            'https://www.antikvarijat-biblos.hr/knjige/psihologija/praksa-psihoterapije',
            $result
        );
    }

    public function test_it_rewrites_absolute_product_urls_to_the_configured_storefront_domain(): void
    {
        config([
            'services.mailchimp.storefront_url' => 'https://www.antikvarijat-biblos.hr',
        ]);

        $result = $this->invokeNormalizeStorefrontUrl(
            'http://antlaravel.test/knjige/psihologija/praksa-psihoterapije?utm_source=mailchimp#buy'
        );

        $this->assertSame(
            'https://www.antikvarijat-biblos.hr/knjige/psihologija/praksa-psihoterapije?utm_source=mailchimp#buy',
            $result
        );
    }

    private function invokeNormalizeStorefrontUrl(string $url): string
    {
        $service = new MailchimpEcommerceService();
        $method = (new ReflectionClass($service))->getMethod('normalizeStorefrontUrl');
        $method->setAccessible(true);

        return $method->invoke($service, $url);
    }

    private function cartPayload(): array
    {
        return [
            'id' => 'cart-123',
            'total' => 29.0,
            'items' => [[
                'id' => '101',
                'name' => 'Praksa psihoterapije',
                'quantity' => 2,
                'price' => 14.5,
                'attributes' => [
                    'path' => '/knjige/psihologija/praksa-psihoterapije',
                ],
                'associatedModel' => [
                    'image' => '/media/praksa-psihoterapije.jpg',
                    'quantity' => 4,
                ],
            ]],
        ];
    }

    private function customerPayload(): array
    {
        return [
            'email' => 'customer@example.com',
            'first_name' => 'Ana',
            'last_name' => 'Anić',
        ];
    }

    private function fakeCartSyncRequests(int $cartStatus): \stdClass
    {
        $payloads = (object) [
            'cartPost' => null,
            'cartPatch' => null,
        ];

        $baseUrl = 'https://us17.api.mailchimp.com/3.0/ecommerce/stores/store123';
        $cartId = 'cart-123';
        $customerId = md5('customer@example.com');
        $productId = '101';

        Http::fake(function ($request) use ($payloads, $baseUrl, $cartId, $customerId, $productId, $cartStatus) {
            if ($request->method() === 'GET' && $request->url() === $baseUrl) {
                return Http::response(['id' => 'store123'], 200);
            }

            if ($request->method() === 'PUT' && $request->url() === $baseUrl . '/customers/' . $customerId) {
                return Http::response([], 200);
            }

            if ($request->method() === 'PUT' && $request->url() === $baseUrl . '/products/' . $productId) {
                return Http::response([], 200);
            }

            if ($request->method() === 'GET' && $request->url() === $baseUrl . '/carts/' . $cartId) {
                return Http::response([], $cartStatus);
            }

            if ($request->method() === 'POST' && $request->url() === $baseUrl . '/carts') {
                $payloads->cartPost = $request->data();

                return Http::response([], 200);
            }

            if ($request->method() === 'PATCH' && $request->url() === $baseUrl . '/carts/' . $cartId) {
                $payloads->cartPatch = $request->data();

                return Http::response([], 200);
            }

            return Http::response([], 404);
        });

        return $payloads;
    }
}
