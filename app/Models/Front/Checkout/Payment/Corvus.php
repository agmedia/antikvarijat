<?php

namespace App\Models\Front\Checkout\Payment;

use App\Models\Back\Orders\Order;
use App\Models\Back\Orders\Transaction;
use App\Models\Back\Settings\Settings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Corvus
{
    private const CARD_PAYMENT_CODE = 'corvus';

    private const WALLET_PAYMENT_CODE = 'corvus_wallets';

    /**
     * CorvusPay calls its card tab "checkout" and its own quick-wallet tab "wallet".
     * Apple Pay and Google Pay are separate tabs.
     */
    private const HIDDEN_TABS = [
        self::CARD_PAYMENT_CODE => 'pis,wallet,paysafecard,googlepay,applepay,ips,crypto',
        self::WALLET_PAYMENT_CODE => 'checkout,pis,wallet,paysafecard,ips,crypto',
    ];

    /**
     * @var Order
     */
    private $order;

    /**
     * Wallet Form Service URLs
     */
    private array $walletUrl = [
        'test' => 'https://wallet.test.corvuspay.com/checkout/',
        'live' => 'https://wallet.corvuspay.com/checkout/',
    ];

    /**
     * CPS API URLs (status)
     */
    private array $cpsUrl = [
        'test' => 'https://testcps.corvus.hr/status',
        'live' => 'https://cps.corvus.hr/status',
    ];

    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * Parse "test" flag from settings (your JSON stores "0"/"1" as string)
     */
    private function isTestMode($paymentMethod): bool
    {
        return ((string) data_get($paymentMethod, 'data.test', '0')) === '1';
    }

    /**
     * Corvus provides separate merchant credentials for its test and live systems.
     * Older settings without the dedicated test fields keep their previous behavior.
     */
    private function effectiveCredentials($paymentMethod): array
    {
        if ($this->isTestMode($paymentMethod)) {
            $testShopId = data_get($paymentMethod, 'data.test_shop_id');
            $testSecretKey = data_get($paymentMethod, 'data.test_secret_key');

            if ($testShopId && $testSecretKey) {
                return [
                    'shop_id' => (string) $testShopId,
                    'secret_key' => (string) $testSecretKey,
                ];
            }
        }

        return [
            'shop_id' => (string) data_get($paymentMethod, 'data.shop_id'),
            'secret_key' => (string) data_get($paymentMethod, 'data.secret_key'),
        ];
    }

    /**
     * Build wallet (checkout) signature (HMAC-SHA256) from the actual POST fields
     * that you submit to Corvus.
     *
     * IMPORTANT: This must match exactly what you send in Blade.
     */
    private function buildWalletSignature(array $fields, string $secretKey): string
    {
        unset($fields['signature']);

        ksort($fields);

        $payload = '';
        foreach ($fields as $k => $v) {
            $payload .= $k . (string) $v;
        }

        return hash_hmac('sha256', $payload, $secretKey);
    }

    /**
     * The Apple Pay / Google Pay option is a second presentation of the same
     * Corvus merchant account, so credentials stay in one place.
     */
    private function credentialSettings($paymentMethod)
    {
        $hasOwnCredentials = data_get($paymentMethod, 'data.shop_id')
            && data_get($paymentMethod, 'data.secret_key');

        if (data_get($paymentMethod, 'code') !== self::WALLET_PAYMENT_CODE || $hasOwnCredentials) {
            return $paymentMethod;
        }

        return Settings::get('payment', 'list.' . self::CARD_PAYMENT_CODE)->first() ?: $paymentMethod;
    }

    private function paymentSettingsForOrder(Order $order)
    {
        $code = (string) $order->payment_code;
        $settingsCode = $code === self::WALLET_PAYMENT_CODE ? self::CARD_PAYMENT_CODE : $code;

        return Settings::get('payment', 'list.' . $settingsCode)->first();
    }

    private function hiddenTabsFor(string $paymentCode): string
    {
        return self::HIDDEN_TABS[$paymentCode] ?? self::HIDDEN_TABS[self::CARD_PAYMENT_CODE];
    }

    /**
     * Create the checkout form data for Blade.
     * Your Blade expects:
     *  - $data['action'], $data['total'], $data['order_id'], $data['currency'], $data['lang'], $data['merchant'], $data['md5'] ...
     */
    public function resolveFormView(?Collection $payment_method = null, ?array $options = null)
    {
        if (! $payment_method) {
            return '';
        }

        $pm = $payment_method->first();
        $credentials = $this->credentialSettings($pm);
        $effectiveCredentials = $this->effectiveCredentials($credentials);
        $paymentCode = (string) data_get($pm, 'code', self::CARD_PAYMENT_CODE);

        $action = $this->isTestMode($credentials) ? $this->walletUrl['test'] : $this->walletUrl['live'];

        $total = number_format($this->order->total, 2, '.', '');

        $currency = $options['currency'] ?? 'EUR';
        $orderNumber = $options['order_number'] ?? $this->order->id;
        $amount = $options['total'] ?? $total;

        // This is the single source of truth for both POST rendering and HMAC signing.
        $fields = [
            'amount'             => $amount,
            'cart'               => 'Web shop kupnja ' . $orderNumber,
            'currency'           => $currency,
            'language'           => 'hr',
            'order_number'       => $orderNumber,
            'require_complete'   => 'false',
            'store_id'           => $effectiveCredentials['shop_id'],
            'cardholder_name'    => $this->order->payment_fname,
            'cardholder_surname' => $this->order->payment_lname,
            'cardholder_phone'   => $this->order->payment_phone,
            'cardholder_email'   => $this->order->payment_email,
            'hide_tabs'          => $this->hiddenTabsFor($paymentCode),
            'version'            => '1.3',
        ];

        if ($paymentCode === self::CARD_PAYMENT_CODE) {
            $fields['payment_all'] = 'Y0299';
        }

        $secretKey = $effectiveCredentials['secret_key'];
        $fields['signature'] = $this->buildWalletSignature($fields, $secretKey);

        $data = [
            'action' => $action,
            'fields' => $fields,
        ];

        return view('front.checkout.payment.corvus', compact('data'));
    }

    /**
     * Handle return (GET) from Corvus success_url.
     * This MUST be tolerant, because Corvus can send keys in different casing depending on flow.
     */
    public function finishOrder(Order $order, Request $request): bool
    {
        $orderNumber = $request->input('order_number') ?? $request->input('OrderNumber') ?? $order->id;

        if ((string) $orderNumber !== (string) $order->id) {
            Log::warning('Corvus return order mismatch', [
                'loaded_order' => $order->id,
                'returned_order' => $orderNumber,
            ]);

            return false;
        }

        $settings = $this->paymentSettingsForOrder($order);
        $secretKey = $this->effectiveCredentials($settings)['secret_key'];
        $signature = $this->pullSignature($request);

        if (! $secretKey || ! $signature || ! $this->hasValidReturnSignature($request, $signature, $secretKey)) {
            Log::warning('Corvus return signature rejected', [
                'order_number' => $orderNumber,
                'has_secret_key' => (bool) $secretKey,
                'has_signature' => (bool) $signature,
                'parameters' => array_keys($request->all()),
            ]);

            return false;
        }

        // Prefer response_code when present
        $responseCode = $request->input('response_code')
            ?? $request->input('response-code')
            ?? $request->input('ResponseCode');

        $approvalCode = $request->input('approval_code')
            ?? $request->input('ApprovalCode');

        $isSuccess = ($responseCode !== null)
            ? ((string) $responseCode === '0')
            : (! empty($approvalCode));

        $statusId = $isSuccess
            ? config('settings.order.status.paid')
            : config('settings.order.status.declined');

        $order->update(['order_status_id' => $statusId]);

        // Idempotent transaction log (avoid duplicates on refresh)
        // If you have a better unique key (pg id), add it.
        $transaction = Transaction::firstOrNew(['order_id' => $orderNumber]);
        $transaction->success = $isSuccess ? 1 : 0;
        $transaction->amount = $order->total;
        $transaction->signature = $signature;
        $transaction->datetime = $order->created_at ?: Carbon::now();
        $transaction->approval_code = $approvalCode;
        $transaction->pg_order_id = $request->input('corvus_order_id')
            ?? $request->input('CorvusOrderId')
            ?? $request->input('reference_number');
        $transaction->lang = $request->input('language') ?? $request->input('Lang') ?? 'hr';
        $transaction->error = $request->input('response_message') ?? $request->input('ResponseMessage');

        $paymentType = $request->input('payment_type')
            ?? $request->input('PaymentType')
            ?? $request->input('transaction_type')
            ?? $request->input('TransactionType');

        if ($paymentType !== null) {
            $transaction->payment_type = $paymentType;
        }

        $transaction->save();

        Log::info('Corvus return', [
            'order_number' => $orderNumber,
            'success' => $isSuccess,
            'response_code' => $responseCode,
            'approval_code' => $approvalCode,
            'parameters' => array_keys($request->all()),
        ]);

        return $isSuccess;
    }

    private function pullSignature(Request $request): ?string
    {
        foreach ($request->all() as $key => $value) {
            if (strtolower((string) $key) === 'signature' && is_scalar($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    private function hasValidReturnSignature(Request $request, string $providedSignature, string $secretKey): bool
    {
        $fields = [];

        foreach ($request->all() as $key => $value) {
            if (strtolower((string) $key) === 'signature') {
                continue;
            }

            if (! is_scalar($value) && $value !== null) {
                return false;
            }

            $fields[(string) $key] = $value === null ? '' : (string) $value;
        }

        $expectedSignature = $this->buildWalletSignature($fields, $secretKey);

        return hash_equals(strtolower($expectedSignature), strtolower($providedSignature));
    }

    /**
     * Fallback: check transaction status via CPS /status (for "missing" when customer doesn't return).
     * Uses SHA1 hash with secret_key as "key" per your docs.
     *
     * Returns:
     *  - ['ok' => true, 'xml' => SimpleXMLElement, 'raw' => string]
     *  - or ['ok' => false, 'error' => '...']
     */
    public function checkStatus(string $orderNumber, Collection $payment_method): array
    {
        $pm = $payment_method->first();

        $isTest = $this->isTestMode($pm);
        $url = $isTest ? $this->cpsUrl['test'] : $this->cpsUrl['live'];

        $credentials = $this->effectiveCredentials($pm);
        $storeId = $credentials['shop_id'];
        $key = $credentials['secret_key']; // per your docs, this is the key used in sha1()

        // EUR numeric currency_code
        $currencyCode = '978';
        $timestamp = (string) time();
        $version = '1.0';

        if (! $storeId || ! $key) {
            return ['ok' => false, 'error' => 'Missing store_id or key'];
        }

        // hash = sha1(key + order_number + store_id + currency_code + timestamp + version)
        $hash = sha1($key . $orderNumber . $storeId . $currencyCode . $timestamp . $version);

        $payload = [
            'store_id'      => $storeId,
            'order_number'  => $orderNumber,
            'currency_code' => $currencyCode,
            'timestamp'     => $timestamp,
            'version'       => $version,
            'hash'          => $hash,
        ];

        try {
            $resp = Http::asForm()->timeout(10)->post($url, $payload);

            if (! $resp->ok()) {
                Log::warning('Corvus status HTTP error', [
                    'status' => $resp->status(),
                    'body' => $resp->body(),
                    'order_number' => $orderNumber,
                ]);

                return ['ok' => false, 'error' => 'HTTP ' . $resp->status()];
            }

            $raw = $resp->body();

            // parse XML
            $xml = @simplexml_load_string($raw);
            if ($xml === false) {
                Log::warning('Corvus status XML parse failed', ['raw' => $raw]);
                return ['ok' => false, 'error' => 'Invalid XML'];
            }

            return ['ok' => true, 'xml' => $xml, 'raw' => $raw];
        } catch (\Throwable $e) {
            Log::error('Corvus status exception', [
                'order_number' => $orderNumber,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
