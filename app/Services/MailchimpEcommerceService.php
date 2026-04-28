<?php

namespace App\Services;

use App\Helpers\ProductHelper;
use App\Models\Back\Catalog\Product\Product as CatalogProduct;
use App\Models\Back\Orders\Order;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

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

        try {
            $this->ensureStore();
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

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

        $response = $this->upsertCartPayload($cartId, $payload);

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

        $order->loadMissing(['products.product', 'totals']);

        $email = strtolower(trim((string) $order->payment_email));
        if ($email === '') {
            return ['ok' => false, 'error' => 'Order nema payment email.'];
        }

        try {
            $this->ensureStore();
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

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

    public function syncCatalogProduct(CatalogProduct $product): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'error' => 'Mailchimp e-commerce nije konfiguriran.'];
        }

        if (! $this->shouldSyncCatalogProduct($product)) {
            $this->deleteProductById((string) $product->id);

            return ['ok' => true, 'error' => null];
        }

        try {
            $this->ensureStore();
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        $productId = (string) $product->id;
        $price = (float) ($product->special() ?: $product->price);
        $url = $this->resolveCatalogProductUrl($product);
        $image = $this->absoluteUrl((string) $product->image);

        $payload = [
            'id' => $productId,
            'title' => trim((string) $product->name) ?: ('Proizvod ' . $productId),
            'description' => trim(strip_tags((string) $product->description)),
            'url' => $url,
            'variants' => [[
                'id' => $productId,
                'title' => trim((string) $product->name) ?: ('Proizvod ' . $productId),
                'price' => $price,
                'inventory_quantity' => max((int) $product->quantity, 0),
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

        try {
            $this->ensureStore();
        } catch (\Throwable $e) {
            Log::warning('Mailchimp cart delete skipped because store is unavailable', [
                'cart_id' => $cartId,
                'error' => $e->getMessage(),
            ]);

            return;
        }

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

    public function deleteProductById(?string $productId): void
    {
        $productId = trim((string) $productId);
        if (! $this->isConfigured() || $productId === '') {
            return;
        }

        try {
            $this->ensureStore();
        } catch (\Throwable $e) {
            Log::warning('Mailchimp product delete skipped because store is unavailable', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $response = $this->request(
            'delete',
            '/ecommerce/stores/' . rawurlencode($this->getStoreId()) . '/products/' . rawurlencode($productId)
        );

        if ($response->failed() && $response->status() !== 404) {
            Log::warning('Mailchimp product delete failed', [
                'product_id' => $productId,
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

        $existing = $this->request(
            'get',
            '/ecommerce/stores/' . rawurlencode($storeId)
        );

        if ($existing->successful()) {
            $this->storeEnsured = true;

            return;
        }

        $payload = [
            'id' => $storeId,
            'list_id' => (string) config('services.mailchimp.audience_id', ''),
            'name' => (string) config('services.mailchimp.ecommerce_store_name', 'Antikvarijat Biblos'),
            'currency_code' => $this->getCurrencyCode(),
        ];

        if ($existing->status() === 404) {
            $create = $this->request('post', '/ecommerce/stores', $payload);

            if (! $create->successful()) {
                throw new RuntimeException('Mailchimp store create failed: ' . $this->extractError($create));
            }

            $this->storeEnsured = true;

            return;
        }

        if ($existing->failed()) {
            throw new RuntimeException('Mailchimp store lookup failed: ' . $this->extractError($existing));
        }

        $update = $this->request(
            'patch',
            '/ecommerce/stores/' . rawurlencode($storeId),
            Arr::except($payload, ['id'])
        );

        if (! $update->successful()) {
            throw new RuntimeException('Mailchimp store update failed: ' . $this->extractError($update));
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
        $url = $this->normalizeStorefrontUrl((string) data_get($item, 'attributes.path', config('app.url')));
        $image = $this->absoluteUrl((string) data_get($item, 'associatedModel.image', ''));

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

        $this->upsertProductPayload($productId, $payload, 'cart');
    }

    private function upsertProductFromOrderItem($item, string $productId): void
    {
        $product = $item->product;
        $title = trim((string) ($item->name ?: optional($product)->name));
        if ($title === '') {
            $title = 'Proizvod ' . $productId;
        }

        $url = trim((string) optional($product)->url);
        if ($url === '') {
            try {
                $url = ProductHelper::url($product);
            } catch (\Throwable $e) {
                $url = '';
            }
        }

        $payload = [
            'id' => $productId,
            'title' => $title,
            'url' => $this->normalizeStorefrontUrl($url),
            'variants' => [[
                'id' => $productId,
                'title' => $title,
                'price' => (float) $item->price,
                'inventory_quantity' => max((int) optional($product)->quantity, 0),
            ]],
        ];

        $image = $this->absoluteUrl((string) optional($product)->image);
        if ($image !== '') {
            $payload['image_url'] = $image;
        }

        $this->upsertProductPayload($productId, $payload, 'order');
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

    private function shouldSyncCatalogProduct(CatalogProduct $product): bool
    {
        return (int) $product->status === 1
            && (float) $product->price > 0
            && (int) $product->quantity > 0;
    }

    private function resolveCatalogProductUrl(CatalogProduct $product): string
    {
        $url = trim((string) $product->url);

        if ($url === '' || $url === '/') {
            try {
                $url = ProductHelper::url($product);
            } catch (\Throwable $e) {
                $url = '';
            }
        }

        return $this->normalizeStorefrontUrl($url);
    }

    private function absoluteUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }

        return rtrim($this->getStorefrontUrl(), '/') . '/' . ltrim($url, '/');
    }

    private function normalizeStorefrontUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, '//')) {
            $url = 'https:' . $url;
        }

        $parts = parse_url($url);
        if (is_array($parts) && isset($parts['host'])) {
            $normalized = rtrim($this->getStorefrontUrl(), '/');
            $path = (string) ($parts['path'] ?? '/');

            $normalized .= '/' . ltrim($path !== '' ? $path : '/', '/');

            if (! empty($parts['query'])) {
                $normalized .= '?' . $parts['query'];
            }

            if (! empty($parts['fragment'])) {
                $normalized .= '#' . $parts['fragment'];
            }

            return $normalized;
        }

        return $this->absoluteUrl($url);
    }

    private function getStorefrontUrl(): string
    {
        $url = trim((string) config('services.mailchimp.storefront_url', ''));

        if ($url !== '') {
            return $url;
        }

        $imagesDomain = trim((string) config('settings.images_domain', ''));
        if ($imagesDomain !== '') {
            return $imagesDomain;
        }

        return trim((string) config('app.url', ''));
    }

    private function upsertProductPayload(string $productId, array $payload, string $context): void
    {
        $response = $this->request(
            'put',
            '/ecommerce/stores/' . rawurlencode($this->getStoreId()) . '/products/' . rawurlencode($productId),
            $payload
        );

        if ($response->failed()) {
            Log::warning('Mailchimp product sync failed (' . $context . ')', [
                'product_id' => $productId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }

    private function upsertCartPayload(string $cartId, array $payload): Response
    {
        $cartPath = '/ecommerce/stores/' . rawurlencode($this->getStoreId()) . '/carts/' . rawurlencode($cartId);
        $existing = $this->request('get', $cartPath);

        if ($existing->successful()) {
            return $this->request('patch', $cartPath, Arr::except($payload, ['id']));
        }

        if ($existing->status() === 404) {
            return $this->request(
                'post',
                '/ecommerce/stores/' . rawurlencode($this->getStoreId()) . '/carts',
                $payload
            );
        }

        return $existing;
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
