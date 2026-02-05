<?php

namespace App\Models\Front\Checkout\Payment;

use App\Models\Back\Orders\Order;
use App\Models\Back\Orders\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Corvus
{
    /**
     * @var Order
     */
    private $order;

    /**
     * Wallet Form Service URLs
     */
    private array $walletUrl = [
        'test' => 'https://test-wallet.corvuspay.com/checkout/',
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
     * Create the checkout form data for Blade.
     * Your Blade expects:
     *  - $data['action'], $data['total'], $data['order_id'], $data['currency'], $data['lang'], $data['merchant'], $data['md5'] ...
     */
    public function resolveFormView(Collection $payment_method = null, array $options = null)
    {
        if (! $payment_method) {
            return '';
        }

        $pm = $payment_method->first();

        $action = $this->isTestMode($pm) ? $this->walletUrl['test'] : $this->walletUrl['live'];

        $total = number_format($this->order->total, 2, '.', '');

        $data = [];
        $data['currency']  = $options['currency'] ?? 'EUR';
        $data['action']    = $action;
        $data['merchant']  = data_get($pm, 'data.shop_id');                // store_id in Corvus terms
        $data['order_id']  = $options['order_number'] ?? $this->order->id; // order_number in Corvus terms
        $data['total']     = $options['total'] ?? $total;

        $data['firstname'] = $this->order->payment_fname;
        $data['lastname']  = $this->order->payment_lname;
        $data['telephone'] = $this->order->payment_phone;
        $data['email']     = $this->order->payment_email;

        $data['lang']      = 'hr';
        $data['return']    = $options['return_url'] ?? data_get($pm, 'data.callback'); // success URL
        $data['cancel']    = $options['cancel_url'] ?? route('kosarica');
        $data['method']    = 'POST';

        $data['number_of_installments'] = 'Y0299';

        // EXACTLY the same fields you POST in Blade:
        $fields = [
            'amount'             => $data['total'],
            'cart'               => 'Web shop kupnja ' . $data['order_id'],
            'currency'           => $data['currency'],
            'language'           => $data['lang'],
            'order_number'       => $data['order_id'],
            'require_complete'   => 'false',
            'store_id'           => $data['merchant'],
            'signature'          => '', // placeholder, excluded in signature build
            'cardholder_name'    => $data['firstname'],
            'cardholder_surname' => $data['lastname'],
            'cardholder_phone'   => $data['telephone'],
            'cardholder_email'   => $data['email'],
            'payment_all'        => $data['number_of_installments'],
            'version'            => '1.3',
        ];

        $secretKey = (string) data_get($pm, 'data.secret_key');
        $data['md5'] = $this->buildWalletSignature($fields, $secretKey);

        return view('front.checkout.payment.corvus', compact('data'));
    }

    /**
     * Handle return (GET) from Corvus success_url.
     * This MUST be tolerant, because Corvus can send keys in different casing depending on flow.
     */
    public function finishOrder(Order $order, Request $request): bool
    {
        $orderNumber = $request->input('order_number') ?? $request->input('OrderNumber') ?? $order->id;

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
        Transaction::updateOrCreate(
            ['order_id' => $orderNumber],
            [
                'success'    => $isSuccess ? 1 : 0,
                'updated_at' => Carbon::now(),
                'created_at' => Carbon::now(),
            ]
        );

        Log::info('Corvus return', [
            'order_number' => $orderNumber,
            'success' => $isSuccess,
            'response_code' => $responseCode,
            'approval_code' => $approvalCode,
            'query' => $request->query(),
        ]);

        return $isSuccess;
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

        $storeId = (string) data_get($pm, 'data.shop_id');
        $key     = (string) data_get($pm, 'data.secret_key'); // per your docs, this is the key used in sha1()

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
