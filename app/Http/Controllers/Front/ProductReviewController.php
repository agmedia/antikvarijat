<?php

namespace App\Http\Controllers\Front;

use App\Helpers\Recaptcha;
use App\Http\Controllers\Controller;
use App\Models\Back\Orders\Order;
use App\Models\Back\Orders\OrderProduct;
use App\Models\ProductReview;
use App\Models\ProductReviewInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;

class ProductReviewController extends Controller
{
    public function index()
    {
        $approved = ProductReview::query()->approved();
        $summary = (clone $approved)
            ->selectRaw('COUNT(*) as review_count, COALESCE(AVG(rating), 0) as average_rating')
            ->first();
        $reviews = (clone $approved)
            ->with('product')
            ->orderByDesc('approved_at')
            ->orderByDesc('id')
            ->paginate(24);

        return view('front.reviews.index', [
            'reviews' => $reviews,
            'reviewCount' => (int) ($summary->review_count ?? 0),
            'averageRating' => round((float) ($summary->average_rating ?? 0), 1),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'reviewer_name' => ['required', 'string', 'max:191'],
            'reviewer_email' => ['required', 'email:rfc', 'max:191'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:191'],
            'body' => ['required', 'string', 'min:10', 'max:5000'],
            'website' => ['nullable', 'max:0'],
            'recaptcha' => ['nullable', 'string', 'max:4096'],
        ]);

        $recaptcha = (new Recaptcha())->check($request->toArray());
        if (! $recaptcha->ok()) {
            return back()
                ->withErrors(['recaptcha' => __('front.messages.recaptcha_failed')])
                ->withInput()
                ->withFragment('reviews');
        }

        $email = mb_strtolower(trim($validated['reviewer_email']));
        $alreadyExists = ProductReview::query()
            ->where('product_id', $validated['product_id'])
            ->whereRaw('LOWER(reviewer_email) = ?', [$email])
            ->exists();

        if ($alreadyExists) {
            return back()
                ->withErrors(['reviewer_email' => __('front.reviews.already_submitted')])
                ->withInput()
                ->withFragment('reviews');
        }

        ProductReview::query()->create([
            'product_id' => $validated['product_id'],
            'user_id' => optional($request->user())->id,
            'reviewer_name' => trim($validated['reviewer_name']),
            'reviewer_email' => $email,
            'rating' => $validated['rating'],
            'title' => trim((string) ($validated['title'] ?? '')) ?: null,
            'body' => trim($validated['body']),
            'locale' => app()->getLocale(),
            'status' => ProductReview::STATUS_PENDING,
            'is_verified_purchase' => false,
        ]);
        Cache::forget('admin.notification_counts');

        return back()
            ->with('success', __('front.reviews.submitted'))
            ->withFragment('reviews');
    }

    public function invitation(Request $request, string $token)
    {
        $invitation = $this->resolveInvitation($token);
        $this->setInvitationLocale($invitation);
        $this->ensureOrderIsEligible($invitation->order);

        $invitation->load([
            'order.orderProducts.product:id,name,name_en,slug,slug_en,url,url_en,image,status',
            'reviews:id,invitation_id,order_product_id,status',
        ]);

        $reviewedOrderProductIds = $invitation->reviews->pluck('order_product_id')->map(fn ($id) => (int) $id);
        $items = $invitation->order->orderProducts
            ->filter(fn (OrderProduct $item) => $item->product_id > 0 && $item->product)
            ->map(function (OrderProduct $item) use ($reviewedOrderProductIds) {
                $item->setAttribute('review_submitted', $reviewedOrderProductIds->contains((int) $item->id));

                return $item;
            })
            ->values();

        return view('front.reviews.invitation', [
            'invitation' => $invitation,
            'items' => $items,
            'formAction' => $request->fullUrl(),
        ]);
    }

    public function storeInvitation(Request $request, string $token)
    {
        $invitation = $this->resolveInvitation($token);
        $this->setInvitationLocale($invitation);
        $this->ensureOrderIsEligible($invitation->order);

        $validated = $request->validate([
            'order_product_id' => [
                'required',
                'integer',
                Rule::exists('order_products', 'id')->where('order_id', $invitation->order_id),
            ],
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:191'],
            'body' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $item = OrderProduct::query()
            ->whereKey($validated['order_product_id'])
            ->where('order_id', $invitation->order_id)
            ->where('product_id', '>', 0)
            ->firstOrFail();

        ProductReview::query()->firstOrCreate(
            ['order_product_id' => $item->id],
            [
                'product_id' => $item->product_id,
                'order_id' => $invitation->order_id,
                'invitation_id' => $invitation->id,
                'user_id' => $invitation->order->user_id ?: null,
                'reviewer_name' => $invitation->recipient_name,
                'reviewer_email' => mb_strtolower($invitation->recipient_email),
                'rating' => $validated['rating'],
                'title' => trim((string) ($validated['title'] ?? '')) ?: null,
                'body' => trim($validated['body']),
                'locale' => $invitation->locale,
                'status' => ProductReview::STATUS_PENDING,
                'is_verified_purchase' => true,
            ]
        );
        Cache::forget('admin.notification_counts');

        $remaining = OrderProduct::query()
            ->where('order_id', $invitation->order_id)
            ->where('product_id', '>', 0)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('products')
                    ->whereColumn('products.id', 'order_products.product_id');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('product_reviews')
                    ->whereColumn('product_reviews.order_product_id', 'order_products.id');
            })
            ->exists();

        if (! $remaining && ! $invitation->completed_at) {
            $invitation->forceFill(['completed_at' => now()])->save();
        }

        return redirect($request->fullUrl())
            ->with('success', __('front.reviews.submitted'));
    }

    private function resolveInvitation(string $token): ProductReviewInvitation
    {
        return ProductReviewInvitation::query()
            ->with('order')
            ->where('token_hash', ProductReviewInvitation::hashToken($token))
            ->firstOrFail();
    }

    private function setInvitationLocale(ProductReviewInvitation $invitation): void
    {
        $locale = in_array($invitation->locale, ['hr', 'en'], true) ? $invitation->locale : 'hr';
        app()->setLocale($locale);
        config(['app.locale' => $locale]);
    }

    private function ensureOrderIsEligible(?Order $order): void
    {
        abort_unless($order && in_array((int) $order->order_status_id, Order::reviewEligibleStatusIds(), true), 410);
    }
}
