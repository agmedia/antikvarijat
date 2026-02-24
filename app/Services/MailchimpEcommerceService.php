<?php

namespace App\Services;

use App\Models\Back\Orders\Order;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailchimpEcommerceService
{
    private bool $storeEnsured = false;

    public function isConfigured(): bool
    {
        return $this->getApiKey() !== ''
            && $this->getServerPrefix() !== ''
            && $this->getStoreId() !== '';
    }

    public function syncCart(array $cart, array $customer, ?string $checkoutUrl = null): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'error' => 'Mailchimp e-commerce nije konfiguriran.'];
        }

        $email = strtolower(trim((string) Arr::get($customer, 'email')));
        $cartId = trim((string) Arr::get($cart, 'id'));
        $items = Arr::get($cart, 'items');

        if ($email === '' || $cartId === '' || empty($items)) {
            return ['ok' => false, 'error' => 'Nedostaje email, cart id ili cart stavke.'];
        }

        $this->ensureStore();

        $customerId = $this->customerId($email);
        $this->upsertCustomer($customerId, $customer);

        $lines = [];
        foreach ($items as $item) {
            $line = $this->mapLineFromCartItem($item);
            if (! $line) {
                continue;
            }
            $this->upsertProductFromCartItem($item, $line['product_id']);
            $lines[] = $line;
        }

        if (empty($lines)) {
            return ['ok' => false, 'error' => 'Nema valjanih artikala za sync košarice.'];
        }

        $payload = [
            'id' => $cartId,
            'customer' => [
                'id' => $customerId,
                'email_address' => $email,
                'opt_in_status' => true,
            ],
            'currency_code' => $this->getCurrencyCode(),
            'order_total' => (float) Arr::get($cart, 'total', 0),
            'lines' => $lines,
        ];

        if ($checkoutUrl) {
            $payload['checkout_url'] = $checkoutUrl;
        }

        $response = $this->request(
            'put',
            '/ecommerce/stores/' . rawurlencode($this->getStoreId()) . '/carts/' . rawurlencode($cartId),
            $payload
        );

        if ($response->successful()) {
            return ['ok' => true, 'error' => null];
        }

        return ['ok' => false, 'error' => $this->extractError($response)];
    }

    public function syncOrder(Order $order): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'error' => 'Mailchimp e-commerce nije konfiguriran.'];
        }

        $order->loadMissing(['products', 'totals']);

        $email = strtolower(trim((string) $order->payment_email));
        if ($email === '') {
            return ['ok' => false, 'error' => 'Order nema payment email.'];
        }

        $this->ensureStore();

        $customerId = $this->customerId($email);
        $this->upsertCustomer($customerId, [
            'email' => $email,
            'first_name' => (string) $order->payment_fname,
            'last_name' => (string) $order->payment_lname,
        ]);

        $lines = [];
        foreach ($order->products as $item) {
            $productId = (string) $item->product_id;
            if ($productId === '') {
                continue;
            }

            $this->upsertProductFromOrderItem($item, $productId);

            $lines[] = [
                'id' => (string) $item->id,
                'product_id' => $productId,
                'product_variant_id' => $productId,
                'quantity' => (int) $item->quantity,
                'price' => (float) $item->price,
            ];
        }

        if (empty($lines)) {
            return ['ok' => false, 'error' => 'Order nema stavki za Mailchimp sync.'];
        }

        $totals = $order->totals;
        $tax = (float) optional($totals->firstWhere('code', 'tax'))->value;
        $shipping = (float) optional($totals->firstWhere('code', 'shipping'))->value;

        $payload = [
            'id' => (string) $order->id,
            'customer' => [
                'id' => $customerId,
                'email_address' => $email,
                'opt_in_status' => true,
            ],
            'currency_code' => $this->getCurrencyCode(),
            'order_total' => (float) $order->total,
            'tax_total' => $tax,
            'shipping_total' => $shipping,
            'financial_status' => 'paid',
            'processed_at_foreign' => optional($order->updated_at)->toIso8601String(),
            'lines' => $lines,
        ];

        $response = $this->request(
            'put',
            '/ecommerce/stores/' . rawurlencode($this->getStoreId()) . '/orders/' . rawurlencode((string) $order->id),
            $payload
        );

        if ($response->successful()) {
            return ['ok' => true, 'error' => null];
        }

        return ['ok' => false, 'error' => $this->extractError($response)];
    }

    public function deleteCartById(?string $cartId): void
    {
        $cartId = trim((string) $cartId);
        if (! $this->isConfigured() || $cartId === '') {
            return;
        }

        $this->ensureStore();

        $response = $this->request(
            'delete',
            '/ecommerce/stores/' . rawurlencode($this->getStoreId()) . '/carts/' . rawurlencode($cartId)
        );

        if ($response->failed() && $response->status() !== 404) {
            Log::warning('Mailchimp cart delete failed', [
                'cart_id' => $cartId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }

    private function ensureStore(): void
    {
        if ($this->storeEnsured) {
            return;
        }

        $storeId = $this->getStoreId();
        $response = $this->request(
            'put',
            '/ecommerce/stores/' . rawurlencode($storeId),
            [
                'id' => $storeId,
                'list_id' => (string) config('services.mailchimp.audience_id', ''),
                'name' => (string) config('services.mailchimp.ecommerce_store_name', 'Antikvarijat Biblos'),
                'currency_code' => $this->getCurrencyCode(),
            ]
        );

        if ($response->failed() && $response->status() !== 400) {
            Log::warning('Mailchimp store ensure failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        $this->storeEnsured = true;
    }

    private function upsertCustomer(string $customerId, array $customer): void
    {
        $email = strtolower(trim((string) Arr::get($customer, 'email')));
        if ($email === '') {
            return;
        }

        $payload = [
            'id' => $customerId,
            'email_address' => $email,
            'opt_in_status' => true,
            'first_name' => trim((string) Arr::get($customer, 'first_name', '')),
            'last_name' => trim((string) Arr::get($customer, 'last_name', '')),
        ];

        $response = $this->request(
            'put',
            '/ecommerce/stores/' . rawurlencode($this->getStoreId()) . '/customers/' . rawurlencode($customerId),
            $payload
        );

        if ($response->failed()) {
            Log::warning('Mailchimp customer sync failed', [
                'customer_id' => $customerId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }

    private function upsertProductFromCartItem($item, string $productId): void
    {
        $title = trim((string) data_get($item, 'name', ''));
        if ($title === '') {
            $title = 'Proizvod ' . $productId;
        }

        $price = (float) data_get($item, 'price', 0);
        $url = (string) data_get($item, 'attributes.path', config('app.url'));
        if ($url !== '' && str_starts_with($url, '/')) {
            $url = rtrim((string) config('app.url'), '/') . $url;
        }

        $image = (string) data_get($item, 'associatedModel.image', '');

        $payload = [
            'id' => $productId,
            'title' => $title,
            'url' => $url,
            'variants' => [[
                'id' => $productId,
                'title' => $title,
                'price' => $price,
                'inventory_quantity' => (int) data_get($item, 'associatedModel.quantity', 1),
            ]],
        ];

        if ($image !== '') {
            $payload['image_url'] = $image;
        }

        $response = $this->request(
            'put',
            '/ecommerce/stores/' . rawurlencode($this->getStoreId()) . '/products/' . rawurlencode($productId),
            $payload
        );

        if ($response->failed()) {
            Log::warning('Mailchimp product sync failed (cart)', [
                'product_id' => $productId,
                'status' => $response->status(),
            ]);
        }
    }

    private function upsertProductFromOrderItem($item, string $productId): void
    {
        $title = trim((string) $item->name);
        if ($title === '') {
            $title = 'Proizvod ' . $productId;
        }

        $payload = [
            'id' => $productId,
            'title' => $title,
            'url' => rtrim((string) config('app.url'), '/') . '/proizvod/' . $productId,
            'variants' => [[
                'id' => $productId,
                'title' => $title,
                'price' => (float) $item->price,
                'inventory_quantity' => 1,
            ]],
        ];

        $response = $this->request(
            'put',
            '/ecommerce/stores/' . rawurlencode($this->getStoreId()) . '/products/' . rawurlencode($productId),
            $payload
        );

        if ($response->failed()) {
            Log::warning('Mailchimp product sync failed (order)', [
                'product_id' => $productId,
                'status' => $response->status(),
            ]);
        }
    }

    private function mapLineFromCartItem($item): ?array
    {
        $id = (string) data_get($item, 'id', '');
        if ($id === '') {
            return null;
        }

        return [
            'id' => $id,
            'product_id' => $id,
            'product_variant_id' => $id,
            'quantity' => (int) data_get($item, 'quantity', 1),
            'price' => (float) data_get($item, 'price', 0),
        ];
    }

    private function customerId(string $email): string
    {
        return md5(strtolower(trim($email)));
    }

    private function request(string $method, string $path, ?array $payload = null): Response
    {
        $url = 'https://' . $this->getServerPrefix() . '.api.mailchimp.com/3.0' . $path;

        $request = Http::withBasicAuth('anystring', $this->getApiKey())
            ->acceptJson()
            ->timeout(20);

        if ($payload === null) {
            return $request->{$method}($url);
        }

        return $request->{$method}($url, $payload);
    }

    private function extractError(Response $response): string
    {
        $detail = (string) ($response->json('detail') ?? '');
        $title = (string) ($response->json('title') ?? '');
        $statusCode = (string) ($response->json('status') ?? $response->status());

        $error = trim($statusCode . ' ' . $title . ': ' . $detail);

        if ($error === ':' || $error === '') {
            $error = (string) ($response->body() ?? 'Nepoznata Mailchimp e-commerce greška.');
        }

        return $error;
    }

    private function getApiKey(): string
    {
        return trim((string) config('services.mailchimp.api_key', ''));
    }

    private function getServerPrefix(): string
    {
        $prefix = trim((string) config('services.mailchimp.server_prefix', ''));

        if ($prefix === '' && str_contains($this->getApiKey(), '-')) {
            $parts = explode('-', $this->getApiKey());
            $prefix = trim((string) end($parts));
        }

        return $prefix;
    }

    private function getStoreId(): string
    {
        $storeId = trim((string) config('services.mailchimp.ecommerce_store_id', ''));

        if ($storeId !== '') {
            return $storeId;
        }

        return 'antikvarijat-biblos';
    }

    private function getCurrencyCode(): string
    {
        return strtoupper(trim((string) config('services.mailchimp.ecommerce_currency_code', 'EUR')));
    }
}
