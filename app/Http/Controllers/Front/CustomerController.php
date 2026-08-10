<?php

namespace App\Http\Controllers\Front;

use App\Helpers\Country;
use App\Helpers\LocaleHelper;
use App\Helpers\Session\CheckoutSession;
use App\Http\Controllers\Controller;
use App\Models\Back\Orders\Order;
use App\Models\Back\Orders\OrderProduct;
use App\Models\ProductReview;
use App\Models\User;
use App\Services\ProductRecommendationService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (session()->has(config('session.cart'))) {
            //dd($request->session()->previousUrl());
            /*if ($request->session()->previousUrl() == config('app.url') . 'login') {
                $cart = new AgCart(session(config('session.cart')));

                if ($cart->get()['count'] > 0) {
                    return redirect()->route('kosarica');
                }
            }*/
        }

        $user = auth()->user();
        $countries = Country::list();

        CheckoutSession::forgetAddress();

        return view('front.customer.index', compact('user', 'countries'));
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function orders(Request $request)
    {
        $user = auth()->user();
        $orders = $this->ordersForUserQuery($user)
            ->with(['products.real', 'products.product', 'totals'])
            ->latest('created_at')
            ->paginate(config('settings.pagination.front'));

        return view('front.customer.moje-narudzbe', compact('user', 'orders'));
    }

    public function reviews(Request $request)
    {
        $user = auth()->user();
        $reviewQuery = $this->reviewsForUserQuery($user);

        $approvedReviewsCount = (clone $reviewQuery)->where('status', ProductReview::STATUS_APPROVED)->count();
        $pendingReviewsCount = (clone $reviewQuery)->where('status', ProductReview::STATUS_PENDING)->count();
        $rejectedReviewsCount = (clone $reviewQuery)->where('status', ProductReview::STATUS_REJECTED)->count();
        $reviewedProductIds = (clone $reviewQuery)
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $reviews = (clone $reviewQuery)
            ->with('product.author')
            ->latest('created_at')
            ->paginate(8);

        $pendingProducts = $this->pendingReviewProductsForUser($user, $reviewedProductIds->all());

        return view('front.customer.dojmovi', compact(
            'user',
            'reviews',
            'approvedReviewsCount',
            'pendingReviewsCount',
            'rejectedReviewsCount',
            'pendingProducts'
        ));
    }

    public function recommendations(Request $request, ProductRecommendationService $recommendations)
    {
        $user = auth()->user();
        $products = $recommendations->forUser($user);

        return view('front.customer.preporuke', compact('user', 'products'));
    }


    /**
     * @param Request $request
     * @param User    $user
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(Request $request, User $user)
    {
        abort_unless($request->user()->is($user), 403);

        $updated = $user->validateFrontRequest($request)->edit();

        if ($updated) {
            return redirect(LocaleHelper::route('moj-racun', ['user' => $updated]))->with(['success' => __('front.account.saved')]);
        }

        return redirect()->back()->with(['error' => __('front.account.save_error')]);
    }

    private function ordersForUserQuery(User $user)
    {
        $email = mb_strtolower(trim((string) $user->email));

        return Order::query()->where(function ($query) use ($user, $email) {
            $query->where('user_id', $user->id);

            if ($email !== '') {
                $query->orWhereRaw('LOWER(payment_email) = ?', [$email]);
            }
        });
    }

    private function reviewsForUserQuery(User $user)
    {
        $email = mb_strtolower(trim((string) $user->email));

        return ProductReview::query()->where(function ($query) use ($user, $email) {
            $query->where('user_id', $user->id);

            if ($email !== '') {
                $query->orWhereRaw('LOWER(reviewer_email) = ?', [$email]);
            }
        });
    }

    private function pendingReviewProductsForUser(User $user, array $reviewedProductIds)
    {
        $orderIds = $this->ordersForUserQuery($user)
            ->whereIn('order_status_id', Order::reviewEligibleStatusIds())
            ->pluck('id');

        return OrderProduct::query()
            ->with(['product', 'real'])
            ->whereIn('order_id', $orderIds)
            ->where('product_id', '>', 0)
            ->when(! empty($reviewedProductIds), fn ($query) => $query->whereNotIn('product_id', $reviewedProductIds))
            ->latest('created_at')
            ->get()
            ->filter(fn (OrderProduct $orderProduct) => $orderProduct->real && filled($orderProduct->real->url))
            ->unique('product_id')
            ->take(6)
            ->values();
    }

}
