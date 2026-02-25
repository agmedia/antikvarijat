<?php

namespace App\Http\Controllers\Back\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Back\Marketing\BookPurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class BookPurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = BookPurchaseRequest::query()->orderByDesc('submitted_at');

        if ($name = trim((string) $request->input('name'))) {
            $query->where('full_name', 'like', '%' . $name . '%');
        }

        if ($email = trim((string) $request->input('email'))) {
            $query->where('email', 'like', '%' . $email . '%');
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('submitted_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('submitted_at', '<=', $dateTo);
        }

        $purchases = $query->paginate(20)->appends($request->query());

        return view('back.marketing.book-purchase.index', compact('purchases'));
    }

    public function show(BookPurchaseRequest $purchase)
    {
        $previous = BookPurchaseRequest::query()
            ->where(function ($query) use ($purchase) {
                $query->where('submitted_at', '>', $purchase->submitted_at)
                    ->orWhere(function ($sub) use ($purchase) {
                        $sub->where('submitted_at', '=', $purchase->submitted_at)
                            ->where('id', '>', $purchase->id);
                    });
            })
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->first();

        $next = BookPurchaseRequest::query()
            ->where(function ($query) use ($purchase) {
                $query->where('submitted_at', '<', $purchase->submitted_at)
                    ->orWhere(function ($sub) use ($purchase) {
                        $sub->where('submitted_at', '=', $purchase->submitted_at)
                            ->where('id', '<', $purchase->id);
                    });
            })
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->first();

        return view('back.marketing.book-purchase.show', compact('purchase', 'previous', 'next'));
    }

    public function destroy(Request $request, BookPurchaseRequest $purchase)
    {
        $storagePath = $purchase->storage_path
            ? public_path($purchase->storage_path)
            : public_path('uploads/otkup-knjiga/' . $purchase->submission_id);

        if (File::exists($storagePath)) {
            File::deleteDirectory($storagePath);
        }

        $purchase->delete();

        $redirectTo = (string) $request->input('redirect_to', '');

        if ($redirectTo !== '' && str_starts_with($redirectTo, url('/'))) {
            return redirect($redirectTo)
                ->with('status', 'Prijava je obrisana, a fotografije su uklonjene sa servera.');
        }

        return redirect()->route('book.purchases')
            ->with('status', 'Prijava je obrisana, a fotografije su uklonjene sa servera.');
    }
}
