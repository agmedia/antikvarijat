<?php

namespace App\Services\Shipping;

use App\Models\Back\Orders\Order;
use App\Models\Front\Checkout\ShippingMethod;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class WoltDriveService
{
    public const CARRIER = 'wolt_drive';

    private const CHECKOUT_QUOTE_SESSION_KEY = 'wolt_drive.checkout_quote';
    private const CASH_AMOUNT_CART_KEY = '_wolt_cash_amount_to_collect';
    private const PROMISE_MAX_ATTEMPTS = 3;
    private const COD_QUOTE_MAX_ATTEMPTS = 4;

    private const CREATE_PENDING = 'pending';
    private const CREATE_AMBIGUOUS = 'ambiguous';
    private const CREATE_FAILED = 'failed';
    private const CREATE_COMPLETED = 'completed';

    /** @var WoltDriveSettingsService */
    private $settings;

    public function __construct(WoltDriveSettingsService $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Request a binding Wolt shipment promise for checkout.
     *
     * The returned price is always the raw provider price. Customer-facing
     * fixed/markup pricing belongs to the shipping rule resolver.
     */
    public function quote(array $address, array $cart): array
    {
        if (! $this->settings->isEnabled()) {
            $this->forgetCheckoutQuote();

            return $this->unavailableQuote(
                'WOLT_DISABLED',
                'Wolt Drive dostava trenutačno nije dostupna.'
            );
        }

        if (! $this->settings->isReady()) {
            $this->forgetCheckoutQuote();

            return $this->unavailableQuote(
                'WOLT_NOT_CONFIGURED',
                'Wolt Drive dostava trenutačno nije dostupna.'
            );
        }

        if ($this->isCashCart($cart) && ! array_key_exists(self::CASH_AMOUNT_CART_KEY, $cart)) {
            return $this->quoteWithFinalCashAmount($address, $cart);
        }

        $remembered = $this->checkoutQuote($address, $cart);

        if ($remembered !== null) {
            return $remembered;
        }

        try {
            $dropoff = $this->dropoffFromAddress($address);
        } catch (WoltDriveException $exception) {
            return $this->unavailableQuote($exception->errorCode(), $exception->getMessage());
        }

        $cacheKey = $this->quoteCacheKey($address, $cart);
        try {
            $cached = Cache::get($cacheKey);
        } catch (Throwable $exception) {
            // Cache is only an optimization; a cache outage must not prevent a
            // fresh, fail-closed availability check with Wolt.
            $cached = null;
        }

        if (is_array($cached) && $this->quoteIsUsable($cached)) {
            $this->rememberCheckoutQuote($cached, $address, $cart);

            return $cached;
        }

        $payload = $this->promisePayload(
            $dropoff,
            $this->parcelsFromCart($cart),
            $this->cashFromCart($cart)
        );

        try {
            $response = $this->requestShipmentPromise($payload);
        } catch (WoltDriveException $exception) {
            Log::warning('Wolt Drive checkout quote request failed.', [
                'error_code' => $exception->errorCode(),
                'http_status' => $exception->httpStatus(),
            ]);

            return $this->unavailableQuote(
                $exception->errorCode(),
                'Wolt Drive dostavu trenutačno nije moguće potvrditi. Odaberite drugi način dostave.'
            );
        }

        if (! $response->successful()) {
            return $this->quoteFailure($response);
        }

        $quote = $this->normalizeQuote($response->json() ?: []);
        $quote['cash_amount_to_collect'] = $this->requestedCashAmount($cart);

        if (! $quote['available']) {
            return $quote;
        }

        $maxQuotePrice = (float) $this->settings->get()['max_quote_price'];

        if ($maxQuotePrice > 0 && (float) $quote['price'] > $maxQuotePrice) {
            return $this->unavailableQuote(
                'WOLT_QUOTE_PRICE_TOO_HIGH',
                'Wolt Drive dostava trenutačno nije dostupna za ovu adresu.'
            );
        }

        $this->rememberCheckoutQuote($quote, $address, $cart);
        $this->cacheQuote($cacheKey, $quote);

        return $quote;
    }

    public function rememberCheckoutQuote(array $quote, array $address, array $cart): void
    {
        if (! $this->quoteIsUsable($quote)) {
            $this->forgetCheckoutQuote();

            return;
        }

        try {
            session()->put(self::CHECKOUT_QUOTE_SESSION_KEY, [
                'fingerprint' => $this->checkoutFingerprint($address, $cart),
                'quote' => $quote,
                'remembered_at' => now()->toIso8601String(),
            ]);
        } catch (Throwable $exception) {
            // Quotes are an optimization. Checkout stays fail-closed even when
            // the current execution context has no session store.
        }
    }

    public function checkoutQuote(array $address, array $cart): ?array
    {
        try {
            $remembered = session()->get(self::CHECKOUT_QUOTE_SESSION_KEY);
        } catch (Throwable $exception) {
            return null;
        }

        $hasCashAmountOverride = array_key_exists(self::CASH_AMOUNT_CART_KEY, $cart);
        $requestedCashAmount = $hasCashAmountOverride ? $this->requestedCashAmount($cart) : null;
        $rememberedCashAmount = data_get($remembered, 'quote.cash_amount_to_collect');

        if (! is_array($remembered)
            || ! hash_equals(
                (string) ($remembered['fingerprint'] ?? ''),
                $this->checkoutFingerprint($address, $cart)
            )
            || ($hasCashAmountOverride && $requestedCashAmount !== null && (
                ! is_numeric($rememberedCashAmount)
                || abs((float) $rememberedCashAmount - $requestedCashAmount) >= 0.005
            ))
            || ! is_array($remembered['quote'] ?? null)
            || ! $this->quoteIsUsable($remembered['quote'])) {
            $this->forgetCheckoutQuote();

            return null;
        }

        return $remembered['quote'];
    }

    public function forgetCheckoutQuote(): void
    {
        try {
            session()->forget(self::CHECKOUT_QUOTE_SESSION_KEY);
        } catch (Throwable $exception) {
            // A session is not guaranteed in queue/CLI contexts.
        }
    }

    /**
     * Create a Wolt delivery exactly once for the supplied order.
     *
     * There is deliberately no automatic retry of the delivery POST. Before
     * that POST a durable marker is written to the order. A timeout, conflict,
     * 429 or 5xx leaves the order in an ambiguous state and all later create
     * attempts fail closed until a signed webhook or manual reconciliation
     * supplies the Wolt reference.
     */
    public function createDelivery(Order $order): array
    {
        $this->assertOutboundReady();
        $order->refresh();
        $order->loadMissing('products.product');
        $this->assertWoltOrder($order);

        $existing = $this->existingDelivery($order);

        if ($existing !== null) {
            return $existing;
        }

        $this->assertNoAmbiguousAttempt($order);
        $attemptId = (string) Str::uuid();
        $this->storeCreateMarker($order, $attemptId, self::CREATE_PENDING, 'shipment_promise');

        try {
            $promiseResponse = $this->requestShipmentPromise(
                $this->promisePayload(
                    $this->dropoffFromOrder($order),
                    $this->parcelsFromOrder($order),
                    $this->cashFromOrder($order)
                )
            );
        } catch (WoltDriveException $exception) {
            $this->storeCreateMarker(
                $order,
                $attemptId,
                self::CREATE_FAILED,
                'shipment_promise',
                $exception->errorCode()
            );

            throw $exception;
        }

        if (! $promiseResponse->successful()) {
            $exception = $this->apiFailure(
                $promiseResponse,
                'Wolt nije mogao pripremiti ponudu dostave.',
                'WOLT_PROMISE_FAILED'
            );
            $this->storeCreateMarker(
                $order,
                $attemptId,
                self::CREATE_FAILED,
                'shipment_promise',
                $exception->errorCode()
            );

            throw $exception;
        }

        $promise = $promiseResponse->json() ?: [];
        $promiseId = trim((string) data_get($promise, 'id', ''));
        $pricePayload = data_get($promise, 'price');
        $coordinates = data_get($promise, 'dropoff.location.coordinates');

        if (data_get($promise, 'is_binding') !== true
            || $promiseId === ''
            || ! is_array($pricePayload)
            || ! is_array($coordinates)
            || ! isset($coordinates['lat'], $coordinates['lon'])) {
            $this->storeCreateMarker(
                $order,
                $attemptId,
                self::CREATE_FAILED,
                'shipment_promise',
                'WOLT_NON_BINDING_PROMISE'
            );

            throw new WoltDriveException(
                'Wolt Drive nije mogao potvrditi obvezujuću dostavu na ovu adresu.',
                'WOLT_NON_BINDING_PROMISE',
                422
            );
        }

        $maxQuotePrice = (float) $this->settings->get()['max_quote_price'];
        $providerPrice = $this->moneyToFloat($pricePayload);

        if ($providerPrice <= 0
            || strtoupper(trim((string) ($pricePayload['currency'] ?? ''))) !== 'EUR') {
            $this->storeCreateMarker(
                $order,
                $attemptId,
                self::CREATE_FAILED,
                'shipment_promise',
                'WOLT_PROMISE_PRICE_INVALID'
            );

            throw new WoltDriveException(
                'Wolt nije vratio valjanu cijenu dostave u eurima.',
                'WOLT_PROMISE_PRICE_INVALID',
                422
            );
        }

        if ($maxQuotePrice > 0 && $providerPrice > $maxQuotePrice) {
            $this->storeCreateMarker(
                $order,
                $attemptId,
                self::CREATE_FAILED,
                'shipment_promise',
                'WOLT_QUOTE_PRICE_TOO_HIGH'
            );

            throw new WoltDriveException(
                'Wolt cijena dostave prelazi dopušteni sigurnosni maksimum.',
                'WOLT_QUOTE_PRICE_TOO_HIGH',
                422
            );
        }

        $deliveryPayload = $this->deliveryPayload(
            $order,
            $promiseId,
            is_array($coordinates) ? $coordinates : null,
            $this->parcelsFromOrder($order),
            $this->cashFromOrder($order)
        );

        // This durable transition is intentionally before the one-shot POST.
        $this->storeCreateMarker($order, $attemptId, self::CREATE_PENDING, 'delivery_post_started');

        try {
            $response = $this->client()->post(
                $this->settings->baseUrl()
                    . '/v1/venues/' . rawurlencode($this->settings->get()['venue_id'])
                    . '/deliveries',
                $deliveryPayload
            );
        } catch (ConnectionException $exception) {
            $this->markAmbiguous($order, $attemptId, 'WOLT_CREATE_CONNECTION_OUTCOME_UNKNOWN');

            throw new WoltDriveAmbiguousCreateException(
                'Ishod slanja na Wolt nije moguće potvrditi. Zahtjev se ne smije ponoviti dok se dostava ručno ne provjeri.',
                'WOLT_CREATE_CONNECTION_OUTCOME_UNKNOWN',
                null,
                $exception
            );
        }

        if ($this->isAmbiguousCreateResponse($response)) {
            $errorCode = $this->responseErrorCode($response, 'WOLT_CREATE_OUTCOME_UNKNOWN');
            $this->markAmbiguous($order, $attemptId, $errorCode);

            throw new WoltDriveAmbiguousCreateException(
                'Wolt je vratio nejasan ishod. Zahtjev se ne smije ponoviti dok se dostava ručno ne provjeri.',
                $errorCode,
                $response->status()
            );
        }

        if (! $response->successful()) {
            $exception = $this->apiFailure(
                $response,
                'Wolt Drive dostava nije kreirana.',
                'WOLT_CREATE_FAILED'
            );
            $this->storeCreateMarker(
                $order,
                $attemptId,
                self::CREATE_FAILED,
                'delivery_rejected',
                $exception->errorCode()
            );

            throw $exception;
        }

        $normalized = $this->normalizeCreatedDelivery(
            $order,
            $response->json() ?: [],
            $attemptId,
            $promiseId,
            $pricePayload
        );

        if ($normalized['parcel_id'] === '' || $normalized['tracking_code'] === '') {
            $this->markAmbiguous($order, $attemptId, 'WOLT_CREATE_RESPONSE_INCOMPLETE');

            throw new WoltDriveAmbiguousCreateException(
                'Wolt je prihvatio zahtjev, ali nije vratio potpune identifikatore dostave. Zahtjev se ne smije ponoviti.',
                'WOLT_CREATE_RESPONSE_INCOMPLETE',
                $response->status()
            );
        }

        // Persist before returning to close the remote-success/local-crash gap.
        $this->persistTracking($order, $normalized, true);

        return $normalized;
    }

    public function cancel(Order $order, string $reason): array
    {
        $this->assertOutboundReady();
        $order->refresh();
        $this->assertWoltOrder($order, false);
        $currentStatus = strtolower(trim((string) $order->shipping_tracking_status_code));

        if (in_array($currentStatus, ['rejected', 'order.rejected', 'cancelled', 'canceled'], true)) {
            $existing = $this->existingDelivery($order);

            if ($existing !== null) {
                return $existing;
            }

            return [
                'carrier' => self::CARRIER,
                'parcel_id' => '',
                'tracking_code' => '',
                'tracking_url' => null,
                'status_code' => strtoupper($currentStatus),
                'status' => $this->statusLabel($currentStatus),
                'tracked_at' => $order->shipping_tracking_updated_at ?: now(),
                'payload' => is_array($order->shipping_tracking_payload)
                    ? $order->shipping_tracking_payload
                    : [],
                'is_delivered' => false,
            ];
        }

        if (in_array($currentStatus, ['delivered', 'order.delivered'], true)) {
            throw new WoltDriveException(
                'Dostavljena Wolt Drive pošiljka više se ne može otkazati.',
                'WOLT_ALREADY_DELIVERED',
                422
            );
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw new WoltDriveException(
                'Razlog otkazivanja Wolt dostave je obavezan.',
                'WOLT_CANCEL_REASON_REQUIRED',
                422
            );
        }

        $reason = Str::limit(preg_replace('/\s+/u', ' ', $reason) ?: '', 500, '');
        $reference = $this->woltOrderReference($order);

        if ($reference === '') {
            throw new WoltDriveException(
                'Narudžba nema spremljen Wolt identifikator dostave.',
                'WOLT_REFERENCE_MISSING',
                422
            );
        }

        try {
            $response = $this->client()->patch(
                $this->settings->baseUrl() . '/order/' . rawurlencode($reference) . '/status/cancel',
                ['reason' => $reason]
            );
        } catch (ConnectionException $exception) {
            throw new WoltDriveException(
                'Ishod otkazivanja Wolt dostave nije moguće potvrditi. Nemojte ponavljati zahtjev bez provjere statusa.',
                'WOLT_CANCEL_OUTCOME_UNKNOWN',
                null,
                $exception
            );
        }

        if (in_array($response->status(), [408, 409, 429], true) || $response->serverError()) {
            throw new WoltDriveException(
                'Ishod otkazivanja Wolt dostave nije moguće potvrditi. Nemojte ponavljati zahtjev bez provjere statusa.',
                $this->responseErrorCode($response, 'WOLT_CANCEL_OUTCOME_UNKNOWN'),
                $response->status()
            );
        }

        if (! $response->successful()) {
            throw $this->apiFailure(
                $response,
                'Wolt dostavu više nije moguće automatski otkazati.',
                'WOLT_CANCEL_FAILED'
            );
        }

        $payload = $response->json() ?: [];
        $statusCode = substr(
            strtoupper(trim((string) data_get($payload, 'status', 'REJECTED'))),
            0,
            32
        );
        $normalized = [
            'carrier' => self::CARRIER,
            'parcel_id' => $reference,
            'tracking_code' => trim((string) $order->tracking_code),
            'tracking_url' => filled($order->shipping_tracking_url)
                ? (string) $order->shipping_tracking_url
                : null,
            'status_code' => $statusCode,
            'status' => $this->statusLabel($statusCode),
            'tracked_at' => now(),
            'payload' => [
                'wolt_order_reference_id' => $reference,
                'status' => $statusCode,
                'cancellation' => [
                    'confirmed_at' => now()->toIso8601String(),
                ],
            ],
            'is_delivered' => false,
        ];

        $this->persistTracking($order, $normalized, true);

        return $normalized;
    }

    /**
     * Verify and normalize a Wolt HS256 webhook token.
     */
    public function handleWebhookToken(string $token): array
    {
        $token = trim($token);
        $secret = trim((string) $this->settings->get()['webhook_secret']);

        if ($secret === '') {
            throw new WoltDriveWebhookException(
                'Wolt webhook nije konfiguriran.',
                'WOLT_WEBHOOK_NOT_CONFIGURED',
                503
            );
        }

        if ($token === '' || strlen($token) > 100000) {
            throw new WoltDriveWebhookException(
                'Neispravan Wolt webhook token.',
                'WOLT_WEBHOOK_INVALID_TOKEN',
                401
            );
        }

        $segments = explode('.', $token);

        if (count($segments) !== 3) {
            throw new WoltDriveWebhookException(
                'Neispravan Wolt webhook token.',
                'WOLT_WEBHOOK_INVALID_TOKEN',
                401
            );
        }

        $header = $this->decodeJwtPart($segments[0]);
        $claims = $this->decodeJwtPart($segments[1]);

        if (($header['alg'] ?? null) !== 'HS256'
            || (! empty($header['crit']))
            || ! hash_equals(
                hash_hmac('sha256', $segments[0] . '.' . $segments[1], $secret, true),
                $this->base64UrlDecode($segments[2])
            )) {
            throw new WoltDriveWebhookException(
                'Wolt webhook potpis nije valjan.',
                'WOLT_WEBHOOK_INVALID_SIGNATURE',
                401
            );
        }

        $this->validateJwtTimes($claims);
        $event = is_array($claims['payload'] ?? null) ? $claims['payload'] : $claims;
        $details = is_array($event['details'] ?? null) ? $event['details'] : [];
        $type = trim((string) ($event['type'] ?? ''));

        if (strpos($type, 'order.') !== 0) {
            throw new WoltDriveWebhookException(
                'Nepodržan Wolt webhook događaj.',
                'WOLT_WEBHOOK_UNSUPPORTED_EVENT',
                422
            );
        }

        $configuredVenue = trim((string) $this->settings->get()['venue_id']);
        $eventVenue = trim((string) ($details['venue_id'] ?? ''));

        if ($configuredVenue !== '' && $eventVenue !== '' && ! hash_equals($configuredVenue, $eventVenue)) {
            throw new WoltDriveWebhookException(
                'Wolt webhook nije namijenjen konfiguriranoj lokaciji.',
                'WOLT_WEBHOOK_VENUE_MISMATCH',
                403
            );
        }

        $reference = trim((string) ($details['wolt_order_reference_id'] ?? $details['id'] ?? ''));
        $trackingCode = trim((string) (
            $details['tracking_reference']
            ?? $details['tracking_id']
            ?? $reference
        ));

        if ($reference === '' || $trackingCode === '' || trim((string) ($details['id'] ?? '')) === '') {
            throw new WoltDriveWebhookException(
                'Wolt webhook nema obavezne identifikatore događaja i dostave.',
                'WOLT_WEBHOOK_INCOMPLETE_EVENT',
                422
            );
        }
        $trackedAt = $this->webhookTimestamp((string) ($event['dispatched_at'] ?? ''));
        $statusCode = $this->webhookStatusCode($type);

        return [
            'carrier' => self::CARRIER,
            'parcel_id' => $reference,
            'tracking_code' => $trackingCode,
            'tracking_url' => null,
            'status_code' => $statusCode,
            'status' => $this->webhookStatusLabel($type),
            'tracked_at' => $trackedAt,
            'payload' => $this->safeWebhookPayload($event, $details),
            'is_delivered' => $type === 'order.delivered',
            'order_id' => $this->webhookOrderId($details),
        ];
    }

    private function assertOutboundReady(): void
    {
        if (! $this->settings->isEnabled()) {
            throw new WoltDriveException(
                'Wolt Drive modul nije uključen.',
                'WOLT_DISABLED',
                503
            );
        }

        if (! $this->settings->isReady()) {
            throw new WoltDriveException(
                'Wolt Drive nije potpuno konfiguriran.',
                'WOLT_NOT_CONFIGURED',
                503
            );
        }
    }

    private function assertWoltOrder(Order $order, bool $validateOrderStatus = true): void
    {
        $shipping = Str::lower(
            (string) $order->shipping_carrier . ' '
            . (string) $order->shipping_code . ' '
            . (string) $order->shipping_method
        );

        if (! Str::contains($shipping, ['wolt_drive', 'wolt drive', 'wolt'])) {
            throw new WoltDriveException(
                'Narudžba nema odabranu Wolt Drive dostavu.',
                'WOLT_WRONG_SHIPPING_METHOD',
                422
            );
        }

        if (! $validateOrderStatus) {
            return;
        }

        $blockedStatuses = array_filter([
            config('settings.order.status.unfinished'),
            config('settings.order.status.declined'),
            config('settings.order.status.canceled'),
        ], function ($status) {
            return $status !== null;
        });

        if (in_array((int) $order->order_status_id, array_map('intval', $blockedStatuses), true)) {
            throw new WoltDriveException(
                'Wolt dostavu nije moguće kreirati za nedovršenu ili otkazanu narudžbu.',
                'WOLT_ORDER_STATUS_NOT_ALLOWED',
                422
            );
        }
    }

    private function existingDelivery(Order $order): ?array
    {
        $carrier = Str::lower(trim((string) $order->shipping_carrier));
        $hasReference = filled($order->shipping_parcel_id)
            || filled($order->tracking_code)
            || filled($order->shipping_tracking_url);

        if (! $hasReference) {
            if ((bool) $order->printed) {
                throw new WoltDriveAmbiguousCreateException(
                    'Narudžba je označena poslanom, ali nema spremljen identifikator pošiljke. Potrebna je ručna provjera.',
                    'WOLT_EXISTING_SHIPMENT_AMBIGUOUS',
                    409
                );
            }

            return null;
        }

        if ($carrier !== '' && $carrier !== self::CARRIER) {
            throw new WoltDriveException(
                'Za narudžbu već postoji pošiljka drugog dostavljača.',
                'WOLT_OTHER_CARRIER_EXISTS',
                409
            );
        }

        return [
            'carrier' => self::CARRIER,
            'parcel_id' => trim((string) $order->shipping_parcel_id),
            'tracking_code' => trim((string) $order->tracking_code),
            'tracking_url' => filled($order->shipping_tracking_url)
                ? (string) $order->shipping_tracking_url
                : null,
            'status_code' => trim((string) $order->shipping_tracking_status_code),
            'status' => trim((string) $order->shipping_tracking_status),
            'tracked_at' => $order->shipping_tracking_updated_at ?: now(),
            'payload' => is_array($order->shipping_tracking_payload)
                ? $order->shipping_tracking_payload
                : [],
            'is_delivered' => (bool) $order->shipped,
        ];
    }

    private function assertNoAmbiguousAttempt(Order $order): void
    {
        $marker = data_get($order->shipping_tracking_payload, '_wolt_create', []);
        $state = (string) data_get($marker, 'state', '');
        $stage = (string) data_get($marker, 'stage', '');

        if ($state === self::CREATE_AMBIGUOUS
            || ($state === self::CREATE_PENDING && $stage === 'delivery_post_started')) {
            throw new WoltDriveAmbiguousCreateException(
                'Prethodni Wolt zahtjev ima nepoznat ishod. Novi zahtjev je blokiran dok se dostava ručno ne provjeri.',
                'WOLT_PREVIOUS_CREATE_OUTCOME_UNKNOWN',
                409
            );
        }
    }

    private function storeCreateMarker(
        Order $order,
        string $attemptId,
        string $state,
        string $stage,
        ?string $errorCode = null
    ): void {
        $payload = is_array($order->shipping_tracking_payload)
            ? $order->shipping_tracking_payload
            : [];
        $payload['_wolt_create'] = array_filter([
            'attempt_id' => $attemptId,
            'state' => $state,
            'stage' => $stage,
            'merchant_order_reference_id' => $this->merchantOrderReference($order),
            'error_code' => $errorCode,
            'updated_at' => now()->toIso8601String(),
        ], function ($value) {
            return $value !== null;
        });

        $order->forceFill([
            'shipping_carrier' => self::CARRIER,
            'shipping_tracking_status_code' => $state === self::CREATE_AMBIGUOUS
                ? 'create_ambiguous'
                : ($state === self::CREATE_FAILED ? 'create_failed' : 'create_pending'),
            'shipping_tracking_status' => $state === self::CREATE_AMBIGUOUS
                ? 'Ishod kreiranja Wolt dostave nije poznat; potrebno je ručno provjeriti Wolt.'
                : ($state === self::CREATE_FAILED
                    ? 'Wolt dostava nije kreirana.'
                    : 'Kreiranje Wolt dostave je u tijeku.'),
            'shipping_tracking_updated_at' => now(),
            'shipping_tracking_payload' => $payload,
        ])->save();
    }

    private function markAmbiguous(Order $order, string $attemptId, string $errorCode): void
    {
        try {
            $this->storeCreateMarker(
                $order,
                $attemptId,
                self::CREATE_AMBIGUOUS,
                'delivery_post_started',
                $errorCode
            );
        } catch (Throwable $exception) {
            Log::critical('Wolt Drive ambiguous create marker could not be persisted.', [
                'order_id' => $order->id,
                'attempt_id' => $attemptId,
                'error_code' => $errorCode,
                'exception' => get_class($exception),
            ]);
        }
    }

    private function requestShipmentPromise(array $payload): Response
    {
        $url = $this->settings->baseUrl()
            . '/v1/venues/' . rawurlencode($this->settings->get()['venue_id'])
            . '/shipment-promises';
        $lastResponse = null;
        $lastException = null;

        for ($attempt = 1; $attempt <= self::PROMISE_MAX_ATTEMPTS; $attempt++) {
            try {
                $response = $this->client()->post($url, $payload);
                $lastResponse = $response;

                if ($response->status() !== 429 && ! $response->serverError()) {
                    return $response;
                }
            } catch (ConnectionException $exception) {
                $lastException = $exception;
            }

            if ($attempt < self::PROMISE_MAX_ATTEMPTS) {
                usleep($attempt * 100000);
            }
        }

        if ($lastResponse instanceof Response) {
            return $lastResponse;
        }

        throw new WoltDriveException(
            'Wolt Drive trenutačno nije dostupan.',
            'WOLT_PROMISE_CONNECTION_FAILED',
            null,
            $lastException
        );
    }

    private function client(): PendingRequest
    {
        $settings = $this->settings->get();
        $timeout = (int) $settings['request_timeout_seconds'];

        return \Illuminate\Support\Facades\Http::withToken($settings['api_key'])
            ->acceptJson()
            ->asJson()
            // Laravel 8's PendingRequest does not expose connectTimeout().
            ->withOptions(['connect_timeout' => min(10, $timeout)])
            ->timeout($timeout);
    }

    private function promisePayload(array $dropoff, array $parcels, ?array $cash): array
    {
        $payload = array_filter([
            'street' => $dropoff['street'],
            'city' => $dropoff['city'],
            'post_code' => $dropoff['post_code'],
            'lat' => $dropoff['lat'] ?? null,
            'lon' => $dropoff['lon'] ?? null,
            'language' => 'hr',
            'min_preparation_time_minutes' => (int) $this->settings->get()['preparation_time_minutes'],
            'parcels' => $parcels,
            'cash' => $cash,
        ], function ($value) {
            return $value !== null && $value !== '';
        });

        return $payload;
    }

    private function deliveryPayload(
        Order $order,
        string $promiseId,
        ?array $coordinates,
        array $parcels,
        ?array $cash
    ): array {
        $dropoff = array_filter([
            'location' => $coordinates && isset($coordinates['lat'], $coordinates['lon'])
                ? ['coordinates' => [
                    'lat' => (float) $coordinates['lat'],
                    'lon' => (float) $coordinates['lon'],
                ]]
                : null,
            'comment' => $this->dropoffComment($order),
            'options' => ['is_no_contact' => false],
        ], function ($value) {
            return $value !== null && $value !== '';
        });

        $payload = [
            'pickup' => [
                'options' => [
                    'min_preparation_time_minutes' => (int) $this->settings->get()['preparation_time_minutes'],
                ],
            ],
            'dropoff' => $dropoff,
            'recipient' => $this->recipient($order),
            'parcels' => $parcels,
            'shipment_promise_id' => $promiseId,
            'merchant_order_reference_id' => $this->merchantOrderReference($order),
            'order_number' => (string) $order->id,
            'language' => 'hr',
        ];

        $support = $this->customerSupport();

        if (! empty($support)) {
            $payload['customer_support'] = $support;
        }

        if ($cash !== null) {
            $payload['cash'] = $cash;
        }

        return $payload;
    }

    private function dropoffFromAddress(array $address): array
    {
        $street = trim((string) ($address['address'] ?? $address['street'] ?? ''));
        $city = trim((string) ($address['city'] ?? ''));
        $postCode = trim((string) ($address['zip'] ?? $address['post_code'] ?? ''));

        if ($street === '' || ($city === '' && $postCode === '')) {
            throw new WoltDriveException(
                'Za Wolt Drive provjeru potrebni su ulica s kućnim brojem i grad ili poštanski broj.',
                'WOLT_INVALID_DROPOFF_ADDRESS',
                422
            );
        }

        $dropoff = [
            'street' => $street,
            'city' => $city,
            'post_code' => $postCode,
        ];
        $lat = $address['lat'] ?? null;
        $lon = $address['lng'] ?? $address['lon'] ?? null;

        if (is_numeric($lat) && is_numeric($lon)) {
            $dropoff['lat'] = (float) $lat;
            $dropoff['lon'] = (float) $lon;
        }

        return $dropoff;
    }

    private function dropoffFromOrder(Order $order): array
    {
        return $this->dropoffFromAddress([
            'address' => $order->shipping_address ?: $order->payment_address,
            'city' => $order->shipping_city ?: $order->payment_city,
            'zip' => $order->shipping_zip ?: $order->payment_zip,
            'lat' => $order->shipping_lat ?? null,
            'lon' => $order->shipping_lon ?? null,
        ]);
    }

    private function recipient(Order $order): array
    {
        $name = trim(
            (string) ($order->shipping_fname ?: $order->payment_fname)
            . ' '
            . (string) ($order->shipping_lname ?: $order->payment_lname)
        );
        $phone = $this->normalizePhone($order->shipping_phone ?: $order->payment_phone);
        $email = trim((string) ($order->shipping_email ?: $order->payment_email));

        if ($name === '' || $phone === '') {
            throw new WoltDriveException(
                'Narudžba nema potpuno ime i telefon primatelja.',
                'WOLT_RECIPIENT_INCOMPLETE',
                422
            );
        }

        return array_filter([
            'name' => $name,
            'phone_number' => $phone,
            'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
        ], function ($value) {
            return $value !== null && $value !== '';
        });
    }

    private function normalizePhone($phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', (string) $phone) ?: '';

        if (Str::startsWith($phone, '00385')) {
            return '+385' . substr($phone, 5);
        }

        if (Str::startsWith($phone, '385')) {
            return '+' . $phone;
        }

        if (Str::startsWith($phone, '0')) {
            return '+385' . substr($phone, 1);
        }

        return $phone;
    }

    private function parcelsFromCart(array $cart): array
    {
        $amount = (float) data_get($cart, 'subtotal', data_get($cart, 'total', 0));

        return [[
            'count' => 1,
            'dimensions' => [
                'weight_gram' => $this->cartWeight($cart),
            ],
            'price' => $this->money($amount),
            'description' => 'Online narudžba',
            'identifier' => 'checkout',
        ]];
    }

    private function parcelsFromOrder(Order $order): array
    {
        $productsValue = (float) $order->products->sum('total');

        return [[
            'count' => 1,
            'dimensions' => [
                'weight_gram' => $this->orderWeight($order),
            ],
            'price' => $this->money($productsValue > 0 ? $productsValue : (float) $order->total),
            'description' => 'Narudžba #' . $order->id,
            'identifier' => (string) $order->id,
        ]];
    }

    private function cartWeight(array $cart): int
    {
        $weight = 0;

        foreach ($this->items($cart['items'] ?? []) as $item) {
            $quantity = max(1, (int) data_get($item, 'quantity', 1));
            $grams = $this->itemWeight($item);

            if ($grams > 0) {
                $weight += $grams * $quantity;
            }
        }

        return $weight > 0 ? $weight : (int) $this->settings->get()['fallback_weight_grams'];
    }

    private function orderWeight(Order $order): int
    {
        $weight = 0;

        foreach ($order->products as $item) {
            $quantity = max(1, (int) $item->quantity);
            $grams = $this->itemWeight($item);

            if ($grams > 0) {
                $weight += $grams * $quantity;
            }
        }

        return $weight > 0 ? $weight : (int) $this->settings->get()['fallback_weight_grams'];
    }

    private function itemWeight($item): int
    {
        foreach ([
            'weight_grams',
            'weight_gram',
            'associatedModel.weight_grams',
            'associatedModel.weight_gram',
            'product.weight_grams',
            'product.weight_gram',
        ] as $path) {
            $value = data_get($item, $path);

            if (is_numeric($value) && (float) $value > 0) {
                return (int) round((float) $value);
            }
        }

        foreach (['weight', 'associatedModel.weight', 'product.weight'] as $path) {
            $value = data_get($item, $path);

            if (is_numeric($value) && (float) $value > 0) {
                return (int) round((float) $value * 1000);
            }
        }

        return 0;
    }

    private function cashFromCart(array $cart): ?array
    {
        $paymentCode = Str::lower((string) (
            data_get($cart, 'payment_code')
            ?: data_get($cart, 'payment.code')
            ?: data_get($cart, 'payment')
        ));

        if ($paymentCode !== 'cod') {
            return null;
        }

        if (! $this->settings->get()['cod_enabled']) {
            return null;
        }

        return ['amount_to_collect' => $this->moneyAmount($this->requestedCashAmount($cart) ?? 0.0)];
    }

    private function cashFromOrder(Order $order): ?array
    {
        if (Str::lower((string) $order->payment_code) !== 'cod') {
            return null;
        }

        if (! $this->settings->get()['cod_enabled']) {
            throw new WoltDriveException(
                'Plaćanje pouzećem nije omogućeno za Wolt Drive.',
                'WOLT_COD_DISABLED',
                422
            );
        }

        return ['amount_to_collect' => $this->moneyAmount((float) $order->total)];
    }

    private function money(float $amount): array
    {
        return [
            'amount' => $this->moneyAmount($amount),
            'currency' => 'EUR',
        ];
    }

    private function moneyAmount(float $amount): int
    {
        return max(0, (int) round($amount * 100));
    }

    private function moneyToFloat(array $price): float
    {
        if (strtoupper(trim((string) ($price['currency'] ?? ''))) !== 'EUR') {
            return 0.0;
        }

        return round(((int) ($price['amount'] ?? 0)) / 100, 2);
    }

    private function customerSupport(): array
    {
        $settings = $this->settings->get();

        return array_filter([
            'url' => $settings['support_url'],
            'email' => $settings['support_email'],
            'phone_number' => $settings['support_phone'],
        ], function ($value) {
            return trim((string) $value) !== '';
        });
    }

    private function dropoffComment(Order $order): ?string
    {
        $comment = trim((string) ($order->napomena ?? ''));

        return $comment !== '' ? Str::limit($comment, 500, '') : null;
    }

    private function normalizeQuote(array $payload): array
    {
        $promiseId = trim((string) data_get($payload, 'id', ''));
        $pricePayload = data_get($payload, 'price');
        $binding = data_get($payload, 'is_binding') === true;

        if (! $binding) {
            return $this->unavailableQuote(
                'WOLT_NON_BINDING_PROMISE',
                'Wolt Drive nije mogao dovoljno precizno potvrditi ovu adresu.'
            );
        }

        if ($promiseId === '' || ! is_array($pricePayload)) {
            return $this->unavailableQuote(
                'WOLT_PROMISE_RESPONSE_INCOMPLETE',
                'Wolt Drive dostavu trenutačno nije moguće potvrditi.'
            );
        }

        $price = $this->moneyToFloat($pricePayload);

        if ($price <= 0 || strtoupper((string) ($pricePayload['currency'] ?? '')) !== 'EUR') {
            return $this->unavailableQuote(
                'WOLT_PROMISE_PRICE_INVALID',
                'Wolt Drive nije vratio valjanu cijenu dostave.'
            );
        }

        $expiresAt = $this->promiseExpiry((string) data_get($payload, 'valid_until', ''));
        $etaMinutes = data_get($payload, 'dropoff.eta_minutes', data_get($payload, 'time_estimate_minutes'));

        return [
            'available' => true,
            'message' => null,
            'error_code' => null,
            'promise_id' => $promiseId,
            'price' => $price,
            'provider_price' => $price,
            'price_payload' => [
                'amount' => (int) ($pricePayload['amount'] ?? 0),
                'currency' => 'EUR',
            ],
            'expires_at' => $expiresAt->toIso8601String(),
            'eta_minutes' => is_numeric($etaMinutes) ? (int) $etaMinutes : null,
            'payload' => $this->safePromisePayload($payload),
        ];
    }

    private function quoteFailure(Response $response): array
    {
        $errorCode = $this->responseErrorCode($response, 'WOLT_AVAILABILITY_CHECK_FAILED');

        Log::warning('Wolt Drive checkout quote was rejected.', [
            'http_status' => $response->status(),
            'error_code' => $errorCode,
        ]);

        if ($errorCode === 'DROPOFF_OUTSIDE_OF_DELIVERY_AREA') {
            return $this->unavailableQuote(
                $errorCode,
                'Wolt Drive nije dostupan za ovu adresu jer je izvan područja dostave.'
            );
        }

        if ($response->status() === 429) {
            return $this->unavailableQuote(
                'WOLT_RATE_LIMITED',
                'Wolt Drive je trenutačno preopterećen. Odaberite drugi način dostave.'
            );
        }

        return $this->unavailableQuote(
            $errorCode,
            'Wolt Drive dostavu trenutačno nije moguće potvrditi. Odaberite drugi način dostave.'
        );
    }

    private function unavailableQuote(string $errorCode, string $message): array
    {
        return [
            'available' => false,
            'message' => $message,
            'error_code' => $errorCode,
            'promise_id' => null,
            'price' => null,
            'provider_price' => null,
            'price_payload' => [],
            'expires_at' => null,
            'eta_minutes' => null,
            'cash_amount_to_collect' => null,
            'payload' => [],
        ];
    }

    private function normalizeCreatedDelivery(
        Order $order,
        array $delivery,
        string $attemptId,
        string $promiseId,
        array $promisePrice
    ): array {
        $reference = trim((string) (
            data_get($delivery, 'wolt_order_reference_id')
            ?: data_get($delivery, 'id', '')
        ));
        $trackingCode = trim((string) (
            data_get($delivery, 'tracking.id')
            ?: $reference
        ));
        $trackingUrl = trim((string) data_get($delivery, 'tracking.url', ''));
        $statusCode = substr(
            strtoupper(trim((string) data_get($delivery, 'status', 'INFO_RECEIVED'))),
            0,
            32
        );

        return [
            'carrier' => self::CARRIER,
            'parcel_id' => $reference,
            'tracking_code' => $trackingCode,
            'tracking_url' => $trackingUrl !== '' ? $trackingUrl : null,
            'status_code' => $statusCode,
            'status' => $this->statusLabel($statusCode),
            'tracked_at' => now(),
            'payload' => [
                '_wolt_create' => [
                    'attempt_id' => $attemptId,
                    'state' => self::CREATE_COMPLETED,
                    'stage' => 'delivery_created',
                    'merchant_order_reference_id' => $this->merchantOrderReference($order),
                    'updated_at' => now()->toIso8601String(),
                ],
                'id' => data_get($delivery, 'id'),
                'wolt_order_reference_id' => $reference,
                'status' => $statusCode,
                'tracking' => [
                    'id' => $trackingCode,
                    'url' => $trackingUrl !== '' ? $trackingUrl : null,
                ],
                'shipment_promise_id' => $promiseId,
                'price' => [
                    'amount' => (int) ($promisePrice['amount'] ?? 0),
                    'currency' => (string) ($promisePrice['currency'] ?? 'EUR'),
                ],
                'pickup_eta' => data_get($delivery, 'pickup.eta'),
                'dropoff_eta' => data_get($delivery, 'dropoff.eta'),
            ],
            'is_delivered' => false,
        ];
    }

    private function persistTracking(Order $order, array $tracking, bool $printed): void
    {
        $order->forceFill([
            'shipping_carrier' => self::CARRIER,
            'shipping_parcel_id' => $tracking['parcel_id'] ?: $order->shipping_parcel_id,
            'tracking_code' => $tracking['tracking_code'] ?: $order->tracking_code,
            'shipping_tracking_url' => $tracking['tracking_url'] ?: $order->shipping_tracking_url,
            'shipping_tracking_status_code' => $tracking['status_code'] ?: null,
            'shipping_tracking_status' => $tracking['status'] ?: null,
            'shipping_tracking_updated_at' => $tracking['tracked_at'] ?: now(),
            'shipping_tracking_payload' => $tracking['payload'] ?: [],
            'printed' => $printed,
        ])->save();

        if (! empty($tracking['is_delivered']) && ! $order->shipped) {
            $order->forceFill(['shipped' => true])->save();
        }
    }

    private function apiFailure(
        Response $response,
        string $message,
        string $fallbackCode
    ): WoltDriveException {
        $errorCode = $this->responseErrorCode($response, $fallbackCode);

        Log::warning('Wolt Drive API request was rejected.', [
            'http_status' => $response->status(),
            'error_code' => $errorCode,
        ]);

        return new WoltDriveException($message, $errorCode, $response->status());
    }

    private function responseErrorCode(Response $response, string $fallback): string
    {
        $payload = $response->json();
        $errorCode = is_array($payload)
            ? trim((string) data_get($payload, 'error_code', ''))
            : '';

        return $errorCode !== '' ? $errorCode : $fallback;
    }

    private function isAmbiguousCreateResponse(Response $response): bool
    {
        $errorCode = strtoupper($this->responseErrorCode($response, ''));

        return in_array($response->status(), [408, 409, 429], true)
            || in_array($errorCode, ['DUPLICATE_ORDER', 'ORDER_ALREADY_EXISTS'], true)
            || $response->serverError();
    }

    private function statusLabel(string $status): string
    {
        $status = strtoupper(trim($status));

        return [
            'INFO_RECEIVED' => 'Wolt je zaprimio podatke o dostavi.',
            'RECEIVED' => 'Wolt je zaprimio dostavu.',
            'ACCEPTED' => 'Wolt je prihvatio dostavu.',
            'PICKUP_STARTED' => 'Wolt dostavljač krenuo je prema trgovini.',
            'PICKED_UP' => 'Wolt dostavljač preuzeo je pošiljku.',
            'DROPOFF_STARTED' => 'Pošiljka je na putu prema kupcu.',
            'DELIVERED' => 'Pošiljka je dostavljena.',
            'REJECTED' => 'Wolt dostava je otkazana ili odbijena.',
        ][$status] ?? ($status !== '' ? 'Wolt status: ' . $status : 'Wolt status nije dostupan.');
    }

    private function webhookStatusCode(string $type): string
    {
        return substr($type, 0, 32);
    }

    private function webhookStatusLabel(string $type): string
    {
        return [
            'order.received' => 'Wolt je zaprimio dostavu.',
            'order.rejected' => 'Wolt dostava je odbijena ili otkazana.',
            'order.pickup_eta_updated' => 'Ažurirano je očekivano vrijeme preuzimanja.',
            'order.pickup_started' => 'Wolt dostavljač krenuo je prema trgovini.',
            'order.picked_up' => 'Wolt dostavljač preuzeo je pošiljku.',
            'order.pickup_arrival' => 'Wolt dostavljač stigao je na lokaciju preuzimanja.',
            'order.dropoff_started' => 'Pošiljka je na putu prema kupcu.',
            'order.dropoff_arrival' => 'Wolt dostavljač stigao je na adresu kupca.',
            'order.dropoff_completed' => 'Dostava na adresu kupca je završena.',
            'order.delivered' => 'Pošiljka je dostavljena.',
            'order.customer_no_show' => 'Kupca nije bilo moguće kontaktirati.',
            'order.dropoff_eta_updated' => 'Ažurirano je očekivano vrijeme dostave.',
            'order.handshake_delivery' => 'Za dostavu je dostupan sigurnosni PIN.',
            'order.location_updated' => 'Ažurirana je lokacija Wolt dostavljača.',
        ][$type] ?? 'Wolt status je ažuriran.';
    }

    private function merchantOrderReference(Order $order): string
    {
        return 'BIBLOS-' . $order->id;
    }

    private function woltOrderReference(Order $order): string
    {
        foreach ([
            data_get($order->shipping_tracking_payload, 'wolt_order_reference_id'),
            data_get($order->shipping_tracking_payload, 'delivery.wolt_order_reference_id'),
            $order->shipping_parcel_id,
        ] as $reference) {
            $reference = trim((string) $reference);

            if ($reference !== '') {
                return $reference;
            }
        }

        return '';
    }

    private function safePromisePayload(array $payload): array
    {
        return [
            'id' => data_get($payload, 'id'),
            'valid_until' => data_get($payload, 'valid_until'),
            'is_binding' => data_get($payload, 'is_binding'),
            'price' => data_get($payload, 'price'),
            'time_estimate_minutes' => data_get($payload, 'time_estimate_minutes'),
            'pickup_eta' => data_get($payload, 'pickup.eta'),
            'dropoff_eta_minutes' => data_get($payload, 'dropoff.eta_minutes'),
        ];
    }

    private function safeWebhookPayload(array $event, array $details): array
    {
        return [
            'type' => $event['type'] ?? null,
            'dispatched_at' => $event['dispatched_at'] ?? null,
            'id' => $details['id'] ?? null,
            'venue_id' => $details['venue_id'] ?? null,
            'wolt_order_reference_id' => $details['wolt_order_reference_id'] ?? null,
            'tracking_reference' => $details['tracking_reference'] ?? $details['tracking_id'] ?? null,
            'merchant_order_reference_id' => $details['merchant_order_reference_id'] ?? null,
            'order_number' => $details['order_number'] ?? null,
            'pickup_eta' => data_get($details, 'pickup.eta'),
            'dropoff_eta' => data_get($details, 'dropoff.eta'),
            'dropoff_completed_at' => data_get($details, 'dropoff.completed_at'),
            'purchase_rejected_reason' => $details['purchase_rejected_reason'] ?? null,
        ];
    }

    private function webhookOrderId(array $details): ?int
    {
        $orderNumber = trim((string) ($details['order_number'] ?? ''));

        if ($orderNumber !== '' && ctype_digit($orderNumber)) {
            return (int) $orderNumber;
        }

        $reference = trim((string) ($details['merchant_order_reference_id'] ?? ''));

        if (preg_match('/(?:^|[-_])(\d+)$/', $reference, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function decodeJwtPart(string $value): array
    {
        $decoded = $this->base64UrlDecode($value);
        $data = json_decode($decoded, true);

        if (! is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            throw new WoltDriveWebhookException(
                'Neispravan Wolt webhook token.',
                'WOLT_WEBHOOK_INVALID_TOKEN',
                401
            );
        }

        return $data;
    }

    private function base64UrlDecode(string $value): string
    {
        if ($value === '' || preg_match('/[^A-Za-z0-9_-]/', $value)) {
            throw new WoltDriveWebhookException(
                'Neispravan Wolt webhook token.',
                'WOLT_WEBHOOK_INVALID_TOKEN',
                401
            );
        }

        $padding = strlen($value) % 4;

        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        if ($decoded === false) {
            throw new WoltDriveWebhookException(
                'Neispravan Wolt webhook token.',
                'WOLT_WEBHOOK_INVALID_TOKEN',
                401
            );
        }

        return $decoded;
    }

    private function validateJwtTimes(array $claims): void
    {
        $now = time();
        $leeway = 60;

        if (isset($claims['exp']) && is_numeric($claims['exp']) && $now > (int) $claims['exp'] + $leeway) {
            throw new WoltDriveWebhookException(
                'Wolt webhook token je istekao.',
                'WOLT_WEBHOOK_TOKEN_EXPIRED',
                401
            );
        }

        if (isset($claims['nbf']) && is_numeric($claims['nbf']) && $now + $leeway < (int) $claims['nbf']) {
            throw new WoltDriveWebhookException(
                'Wolt webhook token još nije valjan.',
                'WOLT_WEBHOOK_TOKEN_NOT_YET_VALID',
                401
            );
        }

        if (isset($claims['iat']) && is_numeric($claims['iat']) && (int) $claims['iat'] > $now + 300) {
            throw new WoltDriveWebhookException(
                'Wolt webhook token ima neispravno vrijeme izdavanja.',
                'WOLT_WEBHOOK_INVALID_ISSUED_AT',
                401
            );
        }
    }

    private function webhookTimestamp(string $value): Carbon
    {
        try {
            return $value !== '' ? Carbon::parse($value) : now();
        } catch (Throwable $exception) {
            return now();
        }
    }

    private function promiseExpiry(string $value): Carbon
    {
        try {
            $expiry = $value !== '' ? Carbon::parse($value) : now()->addMinutes(5);
        } catch (Throwable $exception) {
            $expiry = now()->addMinutes(5);
        }

        return $expiry;
    }

    private function quoteIsUsable(array $quote): bool
    {
        if (($quote['available'] ?? false) !== true
            || trim((string) ($quote['promise_id'] ?? '')) === ''
            || ! is_numeric($quote['price'] ?? null)
            || (float) $quote['price'] <= 0
            || strtoupper(trim((string) data_get($quote, 'price_payload.currency', ''))) !== 'EUR'
            || empty($quote['expires_at'])) {
            return false;
        }

        $maxQuotePrice = (float) $this->settings->get()['max_quote_price'];

        if ($maxQuotePrice > 0 && (float) $quote['price'] > $maxQuotePrice) {
            return false;
        }

        try {
            return Carbon::parse($quote['expires_at'])->gt(now()->addSeconds(15));
        } catch (Throwable $exception) {
            return false;
        }
    }

    private function cacheQuote(string $cacheKey, array $quote): void
    {
        $configuredTtl = (int) $this->settings->get()['availability_cache_seconds'];

        if ($configuredTtl <= 0) {
            return;
        }

        try {
            $validSeconds = max(0, now()->diffInSeconds(Carbon::parse($quote['expires_at']), false) - 15);
            $ttl = min($configuredTtl, $validSeconds);

            if ($ttl > 0) {
                Cache::put($cacheKey, $quote, $ttl);
            }
        } catch (Throwable $exception) {
            // Cache failure must not turn a valid provider response into failure.
        }
    }

    private function quoteCacheKey(array $address, array $cart): string
    {
        $cashAmount = $this->requestedCashAmount($cart);

        return 'wolt-drive-quote:' . hash('sha256', implode('|', [
            $this->checkoutFingerprint($address, $cart),
            $cashAmount === null ? 'no-cash' : number_format($cashAmount, 2, '.', ''),
        ]));
    }

    private function checkoutFingerprint(array $address, array $cart): string
    {
        $canonicalItems = [];

        foreach ($this->items($cart['items'] ?? []) as $item) {
            $canonicalItems[] = [
                'id' => (string) data_get($item, 'id', ''),
                'quantity' => (int) data_get($item, 'quantity', 1),
                'price' => round((float) data_get($item, 'price', 0), 2),
                'total' => round((float) data_get($item, 'total', 0), 2),
            ];
        }

        usort($canonicalItems, function (array $left, array $right) {
            return strcmp($left['id'], $right['id']);
        });

        return hash('sha256', json_encode([
            'environment' => $this->settings->get()['environment'],
            'venue_id' => $this->settings->get()['venue_id'],
            'address' => [
                'street' => Str::lower(trim((string) ($address['address'] ?? $address['street'] ?? ''))),
                'city' => Str::lower(trim((string) ($address['city'] ?? ''))),
                'post_code' => Str::lower(trim((string) ($address['zip'] ?? $address['post_code'] ?? ''))),
                'lat' => isset($address['lat']) && is_numeric($address['lat'])
                    ? round((float) $address['lat'], 6)
                    : null,
                'lon' => isset($address['lng']) && is_numeric($address['lng'])
                    ? round((float) $address['lng'], 6)
                    : (isset($address['lon']) && is_numeric($address['lon'])
                        ? round((float) $address['lon'], 6)
                        : null),
            ],
            'cart' => [
                'items' => $canonicalItems,
                'subtotal' => round((float) data_get($cart, 'subtotal', 0), 2),
                // AgCart::get() includes the currently selected shipping
                // condition in `total`. Including that value here would make
                // selecting Wolt invalidate the very quote used to calculate
                // the Wolt condition and could oscillate the checkout total.
                'coupon' => (string) data_get($cart, 'coupon', ''),
                'payment' => (string) data_get($cart, 'payment_code', data_get($cart, 'payment.code', '')),
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Wolt can price COD differently based on the amount collected. Resolve a
     * quote against the final customer total (including the Wolt charge), not
     * against the temporary total from which the shipping condition was
     * removed while the payment fingerprint changed.
     */
    private function quoteWithFinalCashAmount(array $address, array $cart): array
    {
        if (! $this->settings->get()['cod_enabled']) {
            return $this->unavailableQuote(
                'WOLT_COD_DISABLED',
                'Plaćanje pouzećem nije dostupno za Wolt Drive.'
            );
        }

        $method = (new ShippingMethod())->find(self::CARRIER);

        if (! $method) {
            return $this->unavailableQuote(
                'WOLT_SHIPPING_METHOD_MISSING',
                'Wolt Drive dostava trenutačno nije dostupna.'
            );
        }

        $rules = app(ShippingRuleService::class);
        $subtotal = (float) data_get($cart, 'subtotal', 0);
        $baseTotal = $this->cartTotalWithoutShipping($cart);
        $remembered = $this->checkoutQuote($address, $cart);

        if ($remembered && is_numeric($remembered['price'] ?? null)) {
            $rememberedTotal = round(
                $baseTotal + $rules->priceFor($method, $subtotal, (float) $remembered['price']),
                2
            );

            if (is_numeric($remembered['cash_amount_to_collect'] ?? null)
                && abs((float) $remembered['cash_amount_to_collect'] - $rememberedTotal) < 0.005) {
                return $remembered;
            }
        }

        $amountToCollect = round(
            $baseTotal + $rules->priceFor($method, $subtotal, null),
            2
        );

        for ($attempt = 1; $attempt <= self::COD_QUOTE_MAX_ATTEMPTS; $attempt++) {
            $quoteCart = $cart;
            $quoteCart[self::CASH_AMOUNT_CART_KEY] = $amountToCollect;
            $quote = $this->quote($address, $quoteCart);

            if (! ($quote['available'] ?? false)) {
                return $quote;
            }

            $resolvedTotal = round(
                $baseTotal + $rules->priceFor($method, $subtotal, (float) $quote['price']),
                2
            );

            if (abs($resolvedTotal - $amountToCollect) < 0.005) {
                return $quote;
            }

            $amountToCollect = $resolvedTotal;
        }

        $this->forgetCheckoutQuote();

        return $this->unavailableQuote(
            'WOLT_COD_QUOTE_UNSTABLE',
            'Wolt Drive cijenu za plaćanje pouzećem trenutačno nije moguće potvrditi.'
        );
    }

    private function isCashCart(array $cart): bool
    {
        return Str::lower((string) (
            data_get($cart, 'payment_code')
            ?: data_get($cart, 'payment.code')
            ?: data_get($cart, 'payment')
        )) === 'cod';
    }

    private function requestedCashAmount(array $cart): ?float
    {
        if (! $this->isCashCart($cart)) {
            return null;
        }

        $value = array_key_exists(self::CASH_AMOUNT_CART_KEY, $cart)
            ? $cart[self::CASH_AMOUNT_CART_KEY]
            : data_get($cart, 'total', 0);

        return round(max(0, (float) $value), 2);
    }

    private function cartTotalWithoutShipping(array $cart): float
    {
        $total = (float) data_get($cart, 'total', data_get($cart, 'subtotal', 0));

        foreach ((array) data_get($cart, 'detail_con', []) as $condition) {
            if (Str::lower((string) data_get($condition, 'type')) !== 'shipping') {
                continue;
            }

            $value = str_replace(',', '.', trim((string) data_get($condition, 'value', '')));

            if (preg_match('/^[+-]?\d+(?:\.\d+)?$/', $value)) {
                $total -= (float) $value;
            }
        }

        return round(max(0, $total), 2);
    }

    private function items($items): array
    {
        if ($items instanceof Collection) {
            return array_values($items->all());
        }

        if (is_array($items)) {
            return array_values($items);
        }

        if ($items instanceof \Traversable) {
            return array_values(iterator_to_array($items));
        }

        return [];
    }
}
