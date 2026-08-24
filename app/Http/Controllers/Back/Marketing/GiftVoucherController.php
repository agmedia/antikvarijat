<?php

namespace App\Http\Controllers\Back\Marketing;

use App\Http\Controllers\Controller;
use App\Models\GiftVoucher;
use App\Services\GiftVoucherService;
use Illuminate\Http\Request;

class GiftVoucherController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $status = trim((string) $request->input('status'));
        $orderSearch = ltrim($search, '#');
        $purchaseOrderId = ctype_digit($orderSearch) ? (int) $orderSearch : null;
        $query = GiftVoucher::query()->with([
            'purchaseOrder:id,order_status_id,payment_fname,payment_lname,payment_email,total,created_at',
            'redemptions.order:id,order_status_id,total,created_at',
        ]);

        if ($search !== '') {
            $normalized = app(GiftVoucherService::class)->normalizeCode($search);
            $query->where(function ($builder) use ($search, $normalized, $purchaseOrderId) {
                $builder->where('recipient_name', 'like', '%' . $search . '%')
                    ->orWhere('recipient_email', 'like', '%' . $search . '%')
                    ->orWhere('buyer_name', 'like', '%' . $search . '%')
                    ->orWhere('buyer_email', 'like', '%' . $search . '%')
                    ->orWhere('code_suffix', 'like', '%' . $search . '%');

                if ($purchaseOrderId !== null) {
                    $builder->orWhere('purchase_order_id', $purchaseOrderId);
                }

                if ($normalized !== '') {
                    $builder->orWhere('code_hash', hash('sha256', $normalized));
                }
            });
        }

        if (in_array($status, [
            GiftVoucher::STATUS_PENDING,
            GiftVoucher::STATUS_ACTIVE,
            GiftVoucher::STATUS_EXHAUSTED,
            GiftVoucher::STATUS_DISABLED,
            GiftVoucher::STATUS_CANCELLED,
        ], true)) {
            $query->where('status', $status);
        }

        $giftVouchers = $query
            ->latest('id')
            ->paginate((int) config('settings.pagination.back', 30))
            ->appends($request->query());

        $stats = [
            'sold' => (float) GiftVoucher::query()->whereNotNull('issued_at')->sum('initial_amount'),
            'balance' => (float) GiftVoucher::query()->whereIn('status', [GiftVoucher::STATUS_ACTIVE, GiftVoucher::STATUS_DISABLED])->sum('balance'),
            'active' => GiftVoucher::query()->where('status', GiftVoucher::STATUS_ACTIVE)->count(),
            'pending' => GiftVoucher::query()->where('status', GiftVoucher::STATUS_PENDING)->count(),
        ];

        return view('back.marketing.gift-vouchers.index', compact('giftVouchers', 'stats', 'search', 'status'));
    }

    public function resend(GiftVoucher $giftVoucher, GiftVoucherService $giftVouchers)
    {
        if (! $giftVoucher->issued_at) {
            return back()->with('error', 'Bon još nije izdan jer narudžba nije plaćena.');
        }

        if ($giftVouchers->sendEmail($giftVoucher, true)) {
            return back()->with('success', 'Poklon bon ponovno je poslan na ' . $giftVoucher->recipient_email . '.');
        }

        return back()->with('error', config('gift_vouchers.emails_enabled')
            ? 'E-mail nije poslan. Provjerite zapis greške uz bon.'
            : 'Slanje e-mailova poklon bonova isključeno je u konfiguraciji.');
    }

    public function toggle(GiftVoucher $giftVoucher, GiftVoucherService $giftVouchers)
    {
        if (! in_array($giftVoucher->status, [
            GiftVoucher::STATUS_ACTIVE,
            GiftVoucher::STATUS_DISABLED,
        ], true)) {
            return back()->with('error', 'Status ovog poklon bona nije moguće ručno promijeniti.');
        }

        $enable = $giftVoucher->status === GiftVoucher::STATUS_DISABLED;
        $updated = $giftVouchers->setEnabled($giftVoucher, $enable);

        return back()->with(
            'success',
            $updated->status === GiftVoucher::STATUS_DISABLED
                ? 'Poklon bon je onemogućen.'
                : 'Poklon bon je ponovno aktiviran.'
        );
    }
}
