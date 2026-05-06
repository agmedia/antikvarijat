<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Front\AgCart;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Checkout\Order;
use App\Models\Product\ProductAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CartController extends Controller
{

    /**
     * @var Auth
     */
    protected $user;

    /**
     * @var
     */
    protected $cart;

    /**
     * @var string
     */
    protected $key = 'cart_key';


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->key = config('session.cart');

            if (session()->has($this->key)) {
                $this->cart = new AgCart(session($this->key));

                Cart::checkLogged($this->cart, session($this->key));
            } else {
                $this->resolveSession();
            }

            return $next($request);
        });
    }


    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function get()
    {
        $response = $this->cart->get();

        $this->resolveDB($response);

        return response()->json($response);
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(Request $request)
    {
        $message = $this->cart->check($request->all());

        // uvijek pošalji i aktualni cart
        $cart = $this->cart->get();

        $this->cart->resolveDB();

        return response()->json([
            'message' => $message,
            'cart'    => $cart,
        ]);
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Request $request)
    {
        $response = $this->cart->add($request);

        $this->resolveDB($response);

        return response()->json($response);
    }


    /**
     * @param Request $request
     * @param         $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $response = $this->cart->add($request, $id);

        $this->resolveDB($response);

        return response()->json($response);
    }


    /**
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove($id)
    {
        $response = $this->cart->remove($id);

        $this->resolveDB($response);

        return response()->json($response);
    }


    /**
     * @param $coupon
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function coupon($coupon)
    {
        session([$this->key . '_coupon' => $coupon]);

        return response()->json($this->cart->coupon($coupon));
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function provjeriStanje(Request $request)
    {
        $orderId = $request->input('order_id');

        // Ovdje provjeriš artikle i količine
        $order = Order::query()->where('id', $orderId)->with('products')->first();

        if ($order->count() && $order->products()->count()) {
            foreach ($order->products()->get() as $item) {
                $product = Product::query()->where('id', $item->product_id)->first();

                if ($product->count() && ($product->quantity < $item->quantity)) {
                    return response()->json(['status' => 'error']);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }


    /**
     * @param $loyalty
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function loyalty($loyalty)
    {
        session([$this->key . '_loyalty' => $loyalty]);

        return response()->json($this->cart->hasLoyalty());
    }

    /**
     * Resolve new cart session.
     * If user is logged, check the DB for cart session entries.
     */
    private function resolveSession(): void
    {
        $cart_id = Str::random(8);
        $this->cart = new AgCart($cart_id);
        session([$this->key => $cart_id]);

        Cart::checkLogged($this->cart, $cart_id);
    }


    /**
     * If user is logged store or update the DB session.
     *
     * @param $response
     */
    private function resolveDB($response): void
    {
        if (Auth::user()) {
            // Queue the storage of cart data.
            dispatch(function () use ($response) {
                $has_cart = Cart::where('user_id', Auth::user()->id)->first();

                if ($has_cart) {
                    Cart::edit($response);
                } else {
                    Cart::store($response);
                }
            });
        }
    }
}
