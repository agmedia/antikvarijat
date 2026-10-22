<?php

namespace App\Services;

use App\Exceptions\StockUnavailableException;
use Illuminate\Support\Facades\DB;

class CheckoutStockService
{
    public function assertCart(array $cart): void
    {
        $lines = collect($cart['items'] ?? [])->reject(function ($item) {
            return app(GiftVoucherService::class)->isGiftVoucherItem($item);
        })->map(function ($item) {
            return ['product_id' => data_get($item, 'id'), 'quantity' => data_get($item, 'quantity')];
        });

        $this->assertLines($lines, false);
    }

    public function assertOrder(int $orderId, bool $lock = false): void
    {
        $this->assertLines(DB::table('order_products')->where('order_id', $orderId)->get(), $lock);
    }

    private function assertLines($lines, bool $lock): void
    {
        $quantities = [];
        foreach ($lines as $line) {
            $id = (int) data_get($line, 'product_id');
            $quantity = (int) data_get($line, 'quantity');
            if ($id < 1 || $quantity < 1) {
                throw new StockUnavailableException(__('front.checkout.stock_error'));
            }
            $quantities[$id] = ($quantities[$id] ?? 0) + $quantity;
        }

        $query = DB::table('products')->whereIn('id', array_keys($quantities))->orderBy('id');
        if ($lock) {
            $query->lockForUpdate();
        }
        $products = $query->get(['id', 'quantity', 'status'])->keyBy('id');
        foreach ($quantities as $id => $quantity) {
            $product = $products->get($id);
            if (! $product || (int) $product->status !== 1 || (int) $product->quantity < $quantity) {
                throw new StockUnavailableException(__('front.checkout.stock_error'));
            }
        }
    }
}
