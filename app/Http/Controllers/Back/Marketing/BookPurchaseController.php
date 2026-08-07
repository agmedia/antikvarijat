<?php

namespace App\Http\Controllers\Back\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Back\Marketing\BookPurchaseRequest;
use App\Services\BookPurchaseContentService;
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

    public function editContent(BookPurchaseContentService $content)
    {
        return view('back.marketing.book-purchase.edit-content', [
            'content' => $content->get(),
        ]);
    }

    public function updateContent(Request $request, BookPurchaseContentService $content)
    {
        $validated = $request->validate([
            'hr.title' => ['required', 'string', 'max:120'],
            'hr.meta_description' => ['required', 'string', 'max:255'],
            'hr.section_title' => ['required', 'string', 'max:191'],
            'hr.intro_1' => ['required', 'string', 'max:5000'],
            'hr.intro_2' => ['required', 'string', 'max:5000'],
            'hr.form_title' => ['required', 'string', 'max:191'],
            'en.title' => ['required', 'string', 'max:120'],
            'en.meta_description' => ['required', 'string', 'max:255'],
            'en.section_title' => ['required', 'string', 'max:191'],
            'en.intro_1' => ['required', 'string', 'max:5000'],
            'en.intro_2' => ['required', 'string', 'max:5000'],
            'en.form_title' => ['required', 'string', 'max:191'],
        ]);

        if ($content->save($validated)) {
            return redirect()
                ->route('book.purchases.content.edit')
                ->with('success', 'Tekstovi stranice Otkup knjiga su spremljeni.');
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'Tekstove nije moguće spremiti.');
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
