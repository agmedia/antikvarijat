<?php

namespace App\Http\Controllers\Back\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Back\Catalog\Product\Product as CatalogProduct;
use App\Models\Back\Marketing\NewsletterSubscriber;
use App\Models\Back\Orders\Order;
use App\Services\MailchimpEcommerceService;
use App\Services\MailchimpNewsletterService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Throwable;

class NewsletterSubscriberController extends Controller
{
    public function index(Request $request)
    {
        $query = NewsletterSubscriber::query()
            ->with(['user.details'])
            ->orderByDesc('created_at');

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', '%' . $search . '%')
                    ->orWhere('order_id', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhereHas('details', function ($dq) use ($search) {
                                $dq->where('fname', 'like', '%' . $search . '%')
                                   ->orWhere('lname', 'like', '%' . $search . '%');
                            });
                    });
            });
        }

        $subscribers = $query->paginate(30);
        $pendingSyncCount = NewsletterSubscriber::query()
            ->where('status', 1)
            ->where('gdpr', 1)
            ->whereNull('mailchimp_synced_at')
            ->count();

        return view('back.marketing.newsletter.index', compact('subscribers', 'pendingSyncCount'));
    }

    public function syncMailchimp(Request $request, MailchimpNewsletterService $service)
    {
        $subscribers = NewsletterSubscriber::query()
            ->where('status', 1)
            ->where('gdpr', 1)
            ->whereNull('mailchimp_synced_at')
            ->orderBy('id')
            ->get();

        if ($subscribers->isEmpty()) {
            return back()->with('status', 'Nema novih newsletter prijava za Mailchimp import.');
        }

        $ok = 0;
        $failed = 0;
        $errorSamples = [];

        foreach ($subscribers as $subscriber) {
            $result = $service->syncSubscriber($subscriber);

            if ($result['ok']) {
                $subscriber->update([
                    'mailchimp_synced_at' => now(),
                    'mailchimp_last_error' => null,
                ]);
                $ok++;
            } else {
                $subscriber->update([
                    'mailchimp_last_error' => $result['error'],
                ]);
                if (count($errorSamples) < 3 && ! empty($result['error'])) {
                    $errorSamples[] = $result['error'];
                }
                $failed++;
            }
        }

        $message = 'Mailchimp import završen. Uspješno: ' . $ok . ', neuspješno: ' . $failed . '.';
        if (! empty($errorSamples)) {
            $message .= ' Primjeri grešaka: ' . implode(' | ', array_unique($errorSamples));
        }

        return back()->with(
            'status',
            $message
        );
    }

    public function syncProducts(Request $request, MailchimpEcommerceService $mailchimp)
    {
        @set_time_limit(0);

        $result = $this->performProductSyncBatch(
            (int) $request->input('last_id', 0),
            min(max((int) $request->input('batch', 25), 5), 100),
            $mailchimp
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($result);
        }

        return back()->with('status', $this->formatBatchResult('Artikli', $result));
    }

    public function syncOrders(Request $request, MailchimpEcommerceService $mailchimp, MailchimpNewsletterService $newsletter)
    {
        @set_time_limit(0);

        $result = $this->performOrderSyncBatch(
            (int) $request->input('last_id', 0),
            min(max((int) $request->input('batch', 10), 5), 50),
            $mailchimp,
            $newsletter
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($result);
        }

        return back()->with('status', $this->formatBatchResult('Orderi', $result));
    }

    public function syncCustomerData(Request $request, MailchimpNewsletterService $newsletter)
    {
        @set_time_limit(0);

        $result = $this->performCustomerSyncBatch(
            (int) $request->input('last_id', 0),
            min(max((int) $request->input('batch', 25), 5), 100),
            $newsletter
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($result);
        }

        return back()->with('status', $this->formatBatchResult('Customer podaci', $result));
    }

    public function clearCaches()
    {
        @set_time_limit(0);

        Artisan::call('optimize:clear');
        Cache::store('file')->flush();

        return back()->with('status', trim(Artisan::output()) ?: 'Laravel cache je očišćen.');
    }

    public function syncSelectedProducts(Request $request, MailchimpEcommerceService $mailchimp)
    {
        $request->validate([
            'product_refs' => 'required|string',
        ], [
            'product_refs.required' => 'Upiši barem jedan ID ili SKU artikla.',
        ]);

        $refs = $this->parseProductRefs((string) $request->input('product_refs'));

        if ($refs->isEmpty()) {
            return back()->with('status', 'Nisam pronašao nijedan valjan ID ili SKU za sync.');
        }

        if (! $mailchimp->isConfigured()) {
            return back()->with('status', 'Mailchimp e-commerce nije konfiguriran.');
        }

        $products = CatalogProduct::query()
            ->where(function ($query) use ($refs) {
                $numericRefs = $refs->filter(fn ($ref) => ctype_digit($ref))->values();
                $skuRefs = $refs->filter(fn ($ref) => ! ctype_digit($ref))->values();

                if ($numericRefs->isNotEmpty()) {
                    $query->orWhereIn('id', $numericRefs->map(fn ($ref) => (int) $ref)->all());
                }

                if ($skuRefs->isNotEmpty()) {
                    $query->orWhereIn('sku', $skuRefs->all());
                }
            })
            ->orderBy('id')
            ->get();

        if ($products->isEmpty()) {
            return back()->with('status', 'Nijedan odabrani artikal nije pronađen po ID-u ili SKU-u.');
        }

        $synced = 0;
        $failed = 0;
        $missing = $refs->reject(function ($ref) use ($products) {
            if (ctype_digit($ref)) {
                return $products->contains('id', (int) $ref);
            }

            return $products->contains('sku', $ref);
        })->values();
        $errorSamples = [];

        foreach ($products as $product) {
            try {
                $result = $mailchimp->syncCatalogProduct($product);
            } catch (Throwable $e) {
                $result = [
                    'ok' => false,
                    'error' => $e->getMessage(),
                ];
            }

            if ($result['ok']) {
                $synced++;
                continue;
            }

            if (count($errorSamples) < 3 && ! empty($result['error'])) {
                $errorSamples[] = 'Artikl ' . $product->id . ' / ' . $product->sku . ': ' . $result['error'];
            }

            $failed++;
        }

        $message = 'Sync odabranih artikala gotov. Traženo: ' . $refs->count()
            . ', pronađeno: ' . $products->count()
            . ', uspješno: ' . $synced
            . ', greške: ' . $failed . '.';

        if ($missing->isNotEmpty()) {
            $message .= ' Nije pronađeno: ' . $missing->implode(', ') . '.';
        }

        if (! empty($errorSamples)) {
            $message .= ' Primjeri: ' . implode(' | ', array_unique($errorSamples));
        }

        return back()->with('status', $message);
    }

    private function performProductSyncBatch(int $lastId, int $batch, MailchimpEcommerceService $mailchimp): array
    {
        if (! $mailchimp->isConfigured()) {
            return [
                'ok' => false,
                'message' => 'Mailchimp e-commerce nije konfiguriran.',
                'finished' => true,
                'processed' => 0,
                'synced' => 0,
                'failed' => 0,
                'last_id' => $lastId,
                'batch' => $batch,
                'total' => 0,
            ];
        }

        $query = CatalogProduct::query()
            ->where('status', 1)
            ->where('price', '>', 0)
            ->where('quantity', '>', 0);

        $total = (clone $query)->count();

        $products = (clone $query)
            ->where('id', '>', $lastId)
            ->orderBy('id')
            ->limit($batch)
            ->get();

        if ($products->isEmpty()) {
            return [
                'ok' => true,
                'message' => 'Sync artikala je završen.',
                'finished' => true,
                'processed' => 0,
                'synced' => 0,
                'failed' => 0,
                'last_id' => $lastId,
                'batch' => $batch,
                'total' => $total,
            ];
        }

        $synced = 0;
        $failed = 0;
        $errorSamples = [];
        $newLastId = $lastId;

        foreach ($products as $product) {
            $newLastId = (int) $product->id;
            try {
                $result = $mailchimp->syncCatalogProduct($product);
            } catch (Throwable $e) {
                $result = [
                    'ok' => false,
                    'error' => $e->getMessage(),
                ];
            }

            if ($result['ok']) {
                $synced++;
                continue;
            }

            if (count($errorSamples) < 3 && ! empty($result['error'])) {
                $errorSamples[] = 'Artikl ' . $product->id . ': ' . $result['error'];
            }

            $failed++;
        }

        $finished = $products->count() < $batch;
        $message = 'Batch artikala gotov. Obradjeno: ' . $products->count()
            . ', uspješno: ' . $synced
            . ', greške: ' . $failed
            . ', zadnji ID: ' . $newLastId . '.';

        if ($finished) {
            $message .= ' Sync artikala je završen.';
        }

        if (! empty($errorSamples)) {
            $message .= ' Primjeri: ' . implode(' | ', array_unique($errorSamples));
        }

        return [
            'ok' => $failed === 0,
            'message' => $message,
            'finished' => $finished,
            'processed' => $products->count(),
            'synced' => $synced,
            'failed' => $failed,
            'last_id' => $newLastId,
            'batch' => $batch,
            'total' => $total,
        ];
    }

    private function performOrderSyncBatch(
        int $lastId,
        int $batch,
        MailchimpEcommerceService $mailchimp,
        MailchimpNewsletterService $newsletter
    ): array {
        if (! $mailchimp->isConfigured()) {
            return [
                'ok' => false,
                'message' => 'Mailchimp e-commerce nije konfiguriran.',
                'finished' => true,
                'processed' => 0,
                'synced' => 0,
                'failed' => 0,
                'last_id' => $lastId,
                'batch' => $batch,
                'total' => 0,
            ];
        }

        $query = Order::query()
            ->whereIn('order_status_id', [3, 4])
            ->whereNotNull('payment_email');

        $total = (clone $query)->count();

        $ordersQuery = (clone $query)->orderByDesc('id');

        if ($lastId > 0) {
            $ordersQuery->where('id', '<', $lastId);
        }

        $orders = $ordersQuery
            ->limit($batch)
            ->get();

        if ($orders->isEmpty()) {
            return [
                'ok' => true,
                'message' => 'Sync ordera je završen.',
                'finished' => true,
                'processed' => 0,
                'synced' => 0,
                'failed' => 0,
                'last_id' => $lastId,
                'batch' => $batch,
                'total' => $total,
            ];
        }

        $synced = 0;
        $failed = 0;
        $errorSamples = [];
        $newLastId = $lastId;

        foreach ($orders as $order) {
            $newLastId = (int) $order->id;
            try {
                $result = $mailchimp->syncOrder($order);
            } catch (Throwable $e) {
                $result = [
                    'ok' => false,
                    'error' => $e->getMessage(),
                ];
            }

            if ($result['ok']) {
                try {
                    $customerResult = $newsletter->syncCustomerFromOrder($order);
                } catch (Throwable $e) {
                    $customerResult = [
                        'ok' => false,
                        'error' => $e->getMessage(),
                    ];
                }

                if ($customerResult['ok']) {
                    $synced++;
                    continue;
                }

                if (count($errorSamples) < 3 && ! empty($customerResult['error'])) {
                    $errorSamples[] = 'Order ' . $order->id . ': ' . $customerResult['error'];
                }

                $failed++;
                continue;
            }

            if (count($errorSamples) < 3 && ! empty($result['error'])) {
                $errorSamples[] = 'Order ' . $order->id . ': ' . $result['error'];
            }

            $failed++;
        }

        $finished = $orders->count() < $batch;
        $message = 'Batch ordera gotov. Obradjeno: ' . $orders->count()
            . ', uspješno: ' . $synced
            . ', greške: ' . $failed
            . ', zadnji ID: ' . $newLastId . '.';

        if ($finished) {
            $message .= ' Sync ordera je završen.';
        }

        if (! empty($errorSamples)) {
            $message .= ' Primjeri: ' . implode(' | ', array_unique($errorSamples));
        }

        return [
            'ok' => $failed === 0,
            'message' => $message,
            'finished' => $finished,
            'processed' => $orders->count(),
            'synced' => $synced,
            'failed' => $failed,
            'last_id' => $newLastId,
            'batch' => $batch,
            'total' => $total,
        ];
    }

    private function performCustomerSyncBatch(int $lastId, int $batch, MailchimpNewsletterService $newsletter): array
    {
        if (! $newsletter->isConfigured()) {
            return [
                'ok' => false,
                'message' => 'Mailchimp newsletter nije konfiguriran.',
                'finished' => true,
                'processed' => 0,
                'synced' => 0,
                'failed' => 0,
                'last_id' => $lastId,
                'batch' => $batch,
                'total' => 0,
            ];
        }

        $query = Order::query()
            ->whereIn('order_status_id', [3, 4])
            ->whereNotNull('payment_email');

        $total = (clone $query)->count();

        $ordersQuery = (clone $query)->orderByDesc('id');

        if ($lastId > 0) {
            $ordersQuery->where('id', '<', $lastId);
        }

        $orders = $ordersQuery
            ->limit($batch)
            ->get();

        if ($orders->isEmpty()) {
            return [
                'ok' => true,
                'message' => 'Sync customer podataka je završen.',
                'finished' => true,
                'processed' => 0,
                'synced' => 0,
                'failed' => 0,
                'last_id' => $lastId,
                'batch' => $batch,
                'total' => $total,
            ];
        }

        $synced = 0;
        $failed = 0;
        $errorSamples = [];
        $newLastId = $lastId;

        foreach ($orders as $order) {
            $newLastId = (int) $order->id;
            try {
                $result = $newsletter->syncCustomerFromOrder($order);
            } catch (Throwable $e) {
                $result = [
                    'ok' => false,
                    'error' => $e->getMessage(),
                ];
            }

            if ($result['ok']) {
                $synced++;
                continue;
            }

            if (count($errorSamples) < 3 && ! empty($result['error'])) {
                $errorSamples[] = 'Order ' . $order->id . ': ' . $result['error'];
            }

            $failed++;
        }

        $finished = $orders->count() < $batch;
        $message = 'Batch customer podataka gotov. Obradjeno: ' . $orders->count()
            . ', uspješno: ' . $synced
            . ', greške: ' . $failed
            . ', zadnji ID: ' . $newLastId . '.';

        if ($finished) {
            $message .= ' Sync customer podataka je završen.';
        }

        if (! empty($errorSamples)) {
            $message .= ' Primjeri: ' . implode(' | ', array_unique($errorSamples));
        }

        return [
            'ok' => $failed === 0,
            'message' => $message,
            'finished' => $finished,
            'processed' => $orders->count(),
            'synced' => $synced,
            'failed' => $failed,
            'last_id' => $newLastId,
            'batch' => $batch,
            'total' => $total,
        ];
    }

    private function formatBatchResult(string $label, array $result): string
    {
        return $label . ': ' . ($result['message'] ?? 'Batch je pokrenut.');
    }

    private function parseProductRefs(string $refs): Collection
    {
        return collect(preg_split('/[\s,;]+/', $refs) ?: [])
            ->map(fn ($ref) => trim((string) $ref))
            ->filter()
            ->unique()
            ->values();
    }
}
