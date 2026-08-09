<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;

class ProductReviewController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $status = (string) $request->input('status');

        $reviews = ProductReview::query()
            ->with(['product:id,name,sku,image', 'order:id', 'approver:id,name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('reviewer_name', 'like', "%{$search}%")
                        ->orWhere('reviewer_email', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%")
                        ->orWhereHas('product', function ($product) use ($search) {
                            $product->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%");
                        });
                });
            })
            ->when(array_key_exists($status, ProductReview::statuses()), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(30)
            ->appends($request->query());

        return view('back.product-reviews.index', [
            'reviews' => $reviews,
            'statuses' => ProductReview::statuses(),
            'selectedStatus' => $status,
            'search' => $search,
        ]);
    }

    public function update(Request $request, ProductReview $review)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(ProductReview::statuses()))],
        ]);

        $approved = $validated['status'] === ProductReview::STATUS_APPROVED;

        $review->forceFill([
            'status' => $validated['status'],
            'approved_at' => $approved ? ($review->approved_at ?: now()) : null,
            'approved_by' => $approved ? optional($request->user())->id : null,
        ])->save();
        Cache::forget('admin.notification_counts');

        return back()->with('success', 'Status recenzije je spremljen.');
    }
}
