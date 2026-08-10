<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\ProductReviewBackfill;
use App\Services\ProductReviewBackfillService;
use App\Services\ProductReviewRequestService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductReviewBackfillController extends Controller
{
    public function index(
        Request $request,
        ProductReviewBackfillService $backfills,
        ProductReviewRequestService $requests
    ) {
        $delayDays = max(1, (int) config('reviews.request_delay_days', 30));
        $latestDate = now()->startOfDay()->subDays($delayDays + 1)->toDateString();
        $maxOrders = max(1, (int) config('reviews.backfill_max_orders', 5000));
        $intervalOptions = config('reviews.backfill_interval_options', [5]);
        $preview = null;

        if ($request->boolean('preview')) {
            $validated = $request->validate($this->rules($latestDate, $maxOrders, $intervalOptions));
            $from = Carbon::createFromFormat('Y-m-d', $validated['date_from'])->startOfDay();
            $to = Carbon::createFromFormat('Y-m-d', $validated['date_to'])->endOfDay();
            $selection = $backfills->selectCandidates($from, $to, min(10, (int) $validated['limit']));
            $eligibleCount = $selection['eligible_count'];
            $selectedCount = min($eligibleCount, (int) $validated['limit']);

            $preview = [
                'values' => $validated,
                'eligible_count' => $eligibleCount,
                'selected_count' => $selectedCount,
                'estimated_seconds' => $selectedCount * (int) $validated['interval_seconds'],
                'orders' => $selection['orders']
                    ->map(function ($order) use ($requests) {
                        $order->eligible_date = $requests->eligibleAt($order);
                        $order->masked_email = $this->maskedEmail((string) $order->payment_email);

                        return $order;
                    }),
            ];
        }

        $batches = $backfills->isAvailable()
            ? ProductReviewBackfill::query()->with('creator:id,name')->latest()->limit(15)->get()
            : collect();

        return view('back.product-review-backfills.index', [
            'available' => $backfills->isAvailable(),
            'enabled' => (bool) config('reviews.request_emails_enabled'),
            'batches' => $batches,
            'preview' => $preview,
            'latestDate' => $latestDate,
            'maxOrders' => $maxOrders,
            'intervalOptions' => $intervalOptions,
            'defaultInterval' => (int) config('reviews.backfill_default_interval_seconds', 5),
            'statuses' => ProductReviewBackfill::statuses(),
        ]);
    }

    public function store(Request $request, ProductReviewBackfillService $backfills)
    {
        $delayDays = max(1, (int) config('reviews.request_delay_days', 30));
        $latestDate = now()->startOfDay()->subDays($delayDays + 1)->toDateString();
        $maxOrders = max(1, (int) config('reviews.backfill_max_orders', 5000));
        $intervalOptions = config('reviews.backfill_interval_options', [5]);
        $validated = $request->validate(array_merge(
            $this->rules($latestDate, $maxOrders, $intervalOptions),
            ['confirmed' => ['accepted']]
        ));

        if (! config('reviews.request_emails_enabled')) {
            return back()->with('error', 'Slanje poziva za recenzije je isključeno u konfiguraciji.');
        }

        if (! $backfills->isAvailable()) {
            return back()->with('error', 'Nedostaju tablice za povijesno slanje. Primijenite migraciju ili SQL 035.');
        }

        $batch = $backfills->create(
            Carbon::createFromFormat('Y-m-d', $validated['date_from'])->startOfDay(),
            Carbon::createFromFormat('Y-m-d', $validated['date_to'])->endOfDay(),
            (int) $validated['limit'],
            (int) $validated['interval_seconds'],
            optional($request->user())->id
        );

        if ($batch->total_count === 0) {
            return redirect()->route('product-review-backfills.index')
                ->with('warning', 'U međuvremenu više nije bilo kvalificiranih narudžbi; ništa nije poslano.');
        }

        return redirect()->route('product-review-backfills.index')
            ->with('success', "Batch #{$batch->id} je pokrenut za {$batch->total_count} poruka, s razmakom {$batch->interval_seconds} s.");
    }

    public function cancel(ProductReviewBackfill $backfill)
    {
        if (! $backfill->isActive()) {
            return back()->with('warning', 'Ovaj batch više nije aktivan.');
        }

        $backfill->forceFill([
            'status' => ProductReviewBackfill::STATUS_CANCELLED,
            'finished_at' => now(),
        ])->save();

        return back()->with('success', "Batch #{$backfill->id} je zaustavljen. Već poslane poruke nije moguće povući.");
    }

    private function rules(string $latestDate, int $maxOrders, array $intervalOptions): array
    {
        return [
            'date_from' => ['required', 'date_format:Y-m-d', 'before_or_equal:date_to'],
            'date_to' => ['required', 'date_format:Y-m-d', 'before_or_equal:' . $latestDate],
            'limit' => ['required', 'integer', 'min:1', 'max:' . $maxOrders],
            'interval_seconds' => ['required', 'integer', Rule::in($intervalOptions)],
        ];
    }

    private function maskedEmail(string $email): string
    {
        return preg_replace('/^(.).*(@.+)$/', '$1***$2', trim($email)) ?: '***';
    }
}
