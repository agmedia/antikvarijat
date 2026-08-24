<?php

namespace App\Services;

use App\Exceptions\GiftVoucherUnavailableException;
use App\Helpers\Currency;
use App\Helpers\LocaleHelper;
use App\Helpers\Session\CheckoutSession;
use App\Mail\GiftVoucherDelivered;
use App\Models\Back\Orders\Order;
use App\Models\Back\Settings\Settings;
use App\Models\GiftVoucher;
use App\Models\GiftVoucherRedemption;
use Darryldecode\Cart\CartCondition;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GiftVoucherService
{
    public const CART_ITEM_TYPE = 'gift_voucher';
    public const CART_ITEM_ID = 'gift-voucher';
    public const SHIPPING_CODE = 'gift_voucher_email';
    public const PAYMENT_CODE = 'gift_voucher';

    public function availableAmounts(): array
    {
        return range(
            (int) config('gift_vouchers.min_amount', 10),
            (int) config('gift_vouchers.max_amount', 300),
            (int) config('gift_vouchers.amount_step', 10)
        );
    }

    public function normalizeAmount($amount): int
    {
        $amount = (int) $amount;

        return in_array($amount, $this->availableAmounts(), true)
            ? $amount
            : (int) config('gift_vouchers.default_amount', 30);
    }

    public function normalizeCode(?string $code): string
    {
        return Str::upper(trim((string) $code));
    }

    public function formatName(int $amount, ?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $label = $locale === LocaleHelper::ENGLISH_LOCALE ? 'Gift voucher' : 'Poklon bon';

        return $label . ' – ' . number_format($amount, 2, ',', '.') . ' €';
    }

    public function buildCartItemRequest(array $payload): array
    {
        return [
            'item' => [
                'id' => self::CART_ITEM_ID,
                'type' => self::CART_ITEM_TYPE,
                'quantity' => 1,
                'amount' => $this->normalizeAmount($payload['amount'] ?? 0),
                'recipient_name' => trim((string) ($payload['recipient_name'] ?? '')),
                'recipient_email' => Str::lower(trim((string) ($payload['recipient_email'] ?? ''))),
                'sender_name' => trim((string) ($payload['sender_name'] ?? '')),
                'message' => trim((string) ($payload['message'] ?? '')),
                'locale' => in_array(($payload['locale'] ?? app()->getLocale()), ['hr', 'en'], true)
                    ? ($payload['locale'] ?? app()->getLocale())
                    : 'hr',
            ],
        ];
    }

    public function buildCartItem(array $payload): array
    {
        $amount = $this->normalizeAmount($payload['amount'] ?? 0);
        $locale = in_array(($payload['locale'] ?? app()->getLocale()), ['hr', 'en'], true)
            ? ($payload['locale'] ?? app()->getLocale())
            : 'hr';
        $secondaryRate = Currency::secondary() ? Currency::secondary()->value : null;
        $secondaryPrice = $secondaryRate ? number_format($amount * $secondaryRate, 2, ',', '.') . ' kn' : null;

        $associatedModel = (object) [
            'image' => asset('media/img/faviconbiblos.png'),
            'quantity' => 1,
            'secondary_price' => $secondaryPrice,
            'main_price_text' => '€ ' . number_format($amount, 2, ',', '.'),
            'main_special_text' => '€ ' . number_format($amount, 2, ',', '.'),
            'secondary_price_text' => $secondaryPrice,
            'secondary_special_text' => $secondaryPrice,
            'dataLayer' => [
                'item_id' => 'POKLON-BON',
                'item_name' => $this->formatName($amount, $locale),
                'price' => number_format($amount, 2, '.', ''),
                'currency' => 'EUR',
                'discount' => 0,
                'item_category' => $locale === 'en' ? 'Gift voucher' : 'Poklon bon',
                'item_category2' => $locale === 'en' ? 'Digital gift' : 'Digitalni poklon',
                'quantity' => 1,
            ],
        ];

        return [
            'id' => self::CART_ITEM_ID,
            'name' => $this->formatName($amount, $locale),
            'price' => $amount,
            'sec_price' => $secondaryRate ? $amount * $secondaryRate : null,
            'quantity' => 1,
            'associatedModel' => $associatedModel,
            'attributes' => [
                'path' => $locale === 'en' ? 'en/gift-voucher' : 'poklon-bon',
                'tax' => ['rate' => 0],
                'item_type' => self::CART_ITEM_TYPE,
                'is_editable_quantity' => false,
                'gift_voucher' => [
                    'amount' => $amount,
                    'recipient_name' => trim((string) ($payload['recipient_name'] ?? '')),
                    'recipient_email' => Str::lower(trim((string) ($payload['recipient_email'] ?? ''))),
                    'sender_name' => trim((string) ($payload['sender_name'] ?? '')),
                    'message' => trim((string) ($payload['message'] ?? '')),
                    'locale' => $locale,
                ],
            ],
        ];
    }

    public function isGiftVoucherItem($item): bool
    {
        return data_get($this->extractAttributes($item), 'item_type') === self::CART_ITEM_TYPE;
    }

    public function extractVoucherData($item): array
    {
        return data_get($this->extractAttributes($item), 'gift_voucher', []) ?: [];
    }

    public function cartContainsGiftVoucher(array $cart): bool
    {
        return collect($cart['items'] ?? [])->contains(fn ($item) => $this->isGiftVoucherItem($item));
    }

    public function cartContainsOnlyGiftVoucher(array $cart): bool
    {
        $items = collect($cart['items'] ?? []);

        return $items->isNotEmpty() && $items->every(fn ($item) => $this->isGiftVoucherItem($item));
    }

    public function cartHasRegularItems(array $cart): bool
    {
        return collect($cart['items'] ?? [])->contains(fn ($item) => ! $this->isGiftVoucherItem($item));
    }

    public function currentCartItems(): Collection
    {
        $cartId = session(config('session.cart'));

        return $cartId ? collect(Cart::session($cartId)->getContent()) : collect();
    }

    public function currentCartContainsGiftVoucher(): bool
    {
        return $this->currentCartItems()->contains(fn ($item) => $this->isGiftVoucherItem($item));
    }

    public function currentCartContainsOnlyGiftVoucher(): bool
    {
        $items = $this->currentCartItems();

        return $items->isNotEmpty() && $items->every(fn ($item) => $this->isGiftVoucherItem($item));
    }

    public function shippingMethod(): object
    {
        $english = LocaleHelper::isEnglish();

        return (object) [
            'id' => 0,
            'code' => self::SHIPPING_CODE,
            'title' => $english ? 'Gift voucher delivery by email' : 'Dostava poklon bona e-mailom',
            'title_en' => 'Gift voucher delivery by email',
            'geo_zone' => 0,
            'data' => (object) [
                'price' => 0,
                'short_description' => $english
                    ? 'The recipient receives the voucher after successful card payment.'
                    : 'Primatelj dobiva bon nakon uspješnog kartičnog plaćanja.',
                'short_description_en' => 'The recipient receives the voucher after successful card payment.',
                'description' => $english
                    ? 'A digital voucher with your message and a unique code is delivered by email.'
                    : 'Digitalni bon s vašom porukom i jedinstvenim kodom dostavlja se e-mailom.',
                'description_en' => 'A digital voucher with your message and a unique code is delivered by email.',
                'time' => $english ? 'After payment confirmation' : 'Nakon potvrde plaćanja',
            ],
        ];
    }

    public function isGiftVoucherShipping(?string $code): bool
    {
        return (string) $code === self::SHIPPING_CODE;
    }

    public function giftVoucherPaymentMethod(): object
    {
        return (object) [
            'id' => 0,
            'code' => self::PAYMENT_CODE,
            'title' => LocaleHelper::isEnglish() ? 'Gift voucher' : 'Poklon bon',
            'title_en' => 'Gift voucher',
            'geo_zone' => 0,
            'data' => (object) [
                'price' => 0,
                'short_description' => LocaleHelper::isEnglish()
                    ? 'The full amount is covered by your gift voucher.'
                    : 'Cijeli iznos pokriven je poklon bonom.',
                'short_description_en' => 'The full amount is covered by your gift voucher.',
                'description' => LocaleHelper::isEnglish()
                    ? 'No additional payment is required.'
                    : 'Dodatno plaćanje nije potrebno.',
                'description_en' => 'No additional payment is required.',
            ],
        ];
    }

    public function allowedPurchasePaymentCodes(): array
    {
        $configured = collect(config('gift_vouchers.payment_codes', ['corvus', 'corvus_wallets']));
        $active = Settings::getList('payment')->pluck('code');

        return $configured->intersect($active)->values()->all();
    }

    public function firstAllowedPurchasePaymentCode(): ?string
    {
        return $this->allowedPurchasePaymentCodes()[0] ?? null;
    }

    public function isAllowedPurchasePaymentCode(?string $code): bool
    {
        return in_array((string) $code, $this->allowedPurchasePaymentCodes(), true);
    }

    public function findByCode(?string $code): ?GiftVoucher
    {
        if (! $this->tablesAvailable()) {
            return null;
        }

        $code = $this->normalizeCode($code);

        if ($code === '') {
            return null;
        }

        return GiftVoucher::query()->where('code_hash', hash('sha256', $code))->first();
    }

    public function isValidCodeForCurrentCart(?string $code): bool
    {
        $voucher = $this->findByCode($code);

        if (! $voucher || $voucher->status !== GiftVoucher::STATUS_ACTIVE || $voucher->isExpired()) {
            return false;
        }

        return $this->availableAmountForCurrentOrder($voucher) > 0;
    }

    public function cartCondition($cart, ?string $code)
    {
        $voucher = $this->findByCode($code);

        if (! $voucher || $voucher->status !== GiftVoucher::STATUS_ACTIVE || $voucher->isExpired()) {
            return false;
        }

        $available = $this->availableAmountForCurrentOrder($voucher);
        $cartTotal = max(0, round((float) $cart->getTotal(), 2));
        $discount = min($available, $cartTotal);

        if ($discount <= 0) {
            return false;
        }

        return new CartCondition([
            'name' => LocaleHelper::isEnglish() ? 'Gift voucher' : 'Poklon bon',
            'type' => 'gift_voucher',
            'target' => 'total',
            'value' => '-' . number_format($discount, 2, '.', ''),
            'attributes' => [
                'type' => 'gift_voucher',
                'description' => $this->normalizeCode($code),
                'gift_voucher_id' => $voucher->id,
                'balance_before' => $available,
                'balance_after' => max(0, round($available - $discount, 2)),
            ],
        ]);
    }

    public function currentCartIsFullyCovered(): bool
    {
        $cartId = session(config('session.cart'));

        if (! $cartId) {
            return false;
        }

        $cart = Cart::session($cartId);
        $hasVoucherCondition = collect($cart->getConditions())->contains(function ($condition) {
            return data_get($this->conditionAttributes($condition), 'type') === 'gift_voucher';
        });

        return $hasVoucherCondition && (float) $cart->getTotal() <= 0.005;
    }

    public function syncOrder(int $orderId, array $orderData): void
    {
        if (! $this->tablesAvailable()) {
            $cart = $orderData['cart'] ?? [];

            if ($this->cartContainsGiftVoucher($cart) || $this->extractGiftVoucherCondition($cart)) {
                throw new GiftVoucherUnavailableException(__('front.gift_voucher.errors.unavailable'));
            }

            return;
        }

        $this->syncPurchasedVouchers($orderId, $orderData);
        $this->reserveRedemption($orderId, $orderData['cart'] ?? []);
    }

    public function completeCheckout(Order $order): void
    {
        if (! $this->tablesAvailable()) {
            return;
        }

        if (! in_array((int) $order->order_status_id, [
            (int) config('settings.order.status.new'),
            (int) config('settings.order.status.paid'),
            (int) config('settings.order.status.send'),
        ], true)) {
            return;
        }

        $this->completeRedemption($order);
        $this->fulfillPurchasedVouchers($order);
    }

    public function handleStatusChange(Order $order, int $statusId): void
    {
        if (! $this->tablesAvailable()) {
            return;
        }

        if (in_array($statusId, [
            (int) config('settings.order.status.new'),
            (int) config('settings.order.status.paid'),
            (int) config('settings.order.status.send'),
        ], true)) {
            $this->completeRedemption($order);
            $this->fulfillPurchasedVouchers($order);

            return;
        }

        if (in_array($statusId, [
            (int) config('settings.order.status.canceled'),
            (int) config('settings.order.status.declined'),
        ], true)) {
            $this->releaseRedemption($order, 'order_status_' . $statusId);
            $this->cancelPurchasedVouchers($order);
        }
    }

    public function hasCoveringRedemption(Order $order): bool
    {
        if (! $this->tablesAvailable()) {
            return false;
        }

        $redemption = GiftVoucherRedemption::query()
            ->where('order_id', $order->id)
            ->whereIn('status', [GiftVoucherRedemption::STATUS_RESERVED, GiftVoucherRedemption::STATUS_REDEEMED])
            ->first();

        return $redemption && (float) $order->total <= 0.005 && (float) $redemption->amount > 0;
    }

    public function fulfillPurchasedVouchers(Order $order): void
    {
        if (! $this->tablesAvailable()) {
            return;
        }

        if ((int) $order->order_status_id !== (int) config('settings.order.status.paid')) {
            return;
        }

        $voucherIds = GiftVoucher::query()
            ->where('purchase_order_id', $order->id)
            ->pluck('id');

        foreach ($voucherIds as $voucherId) {
            $voucher = DB::transaction(function () use ($voucherId) {
                $locked = GiftVoucher::query()->lockForUpdate()->find($voucherId);

                if (! $locked || $locked->status === GiftVoucher::STATUS_CANCELLED) {
                    return null;
                }

                if (! $locked->code_hash) {
                    $locked->storeCode($this->generateUniqueCode());
                }

                $locked->status = (float) $locked->balance > 0
                    ? GiftVoucher::STATUS_ACTIVE
                    : GiftVoucher::STATUS_EXHAUSTED;
                $locked->issued_at = $locked->issued_at ?: now();
                $locked->save();

                return $locked->fresh();
            }, 3);

            if ($voucher && ! $voucher->email_sent_at) {
                $this->sendEmail($voucher);
            }
        }
    }

    public function sendEmail(GiftVoucher $voucher, bool $force = false): bool
    {
        if (! config('gift_vouchers.emails_enabled', true)) {
            return false;
        }

        if (! $voucher->issued_at || ! $voucher->code || ! filter_var($voucher->recipient_email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if ($voucher->email_sent_at && ! $force) {
            return true;
        }

        if (! $force) {
            $claimed = DB::transaction(function () use ($voucher) {
                $locked = GiftVoucher::query()->lockForUpdate()->find($voucher->id);

                if (! $locked || $locked->email_sent_at) {
                    return null;
                }

                if ($locked->last_email_sent_at
                    && $locked->last_email_sent_at->isAfter(now()->subMinutes(10))
                    && ! $locked->email_error) {
                    return null;
                }

                $locked->forceFill([
                    'last_email_sent_at' => now(),
                    'email_error' => null,
                ])->save();

                return $locked->fresh();
            }, 3);

            if (! $claimed) {
                return true;
            }

            $voucher = $claimed;
        }

        try {
            Mail::to($voucher->recipient_email)->send(
                (new GiftVoucherDelivered($voucher))->locale($voucher->locale ?: 'hr')
            );

            $voucher->forceFill([
                'email_sent_at' => $voucher->email_sent_at ?: now(),
                'last_email_sent_at' => now(),
                'email_error' => null,
            ])->save();

            return true;
        } catch (\Throwable $exception) {
            $voucher->forceFill(['email_error' => Str::limit($exception->getMessage(), 2000)])->save();

            Log::error('Gift voucher email delivery failed.', [
                'gift_voucher_id' => $voucher->id,
                'purchase_order_id' => $voucher->purchase_order_id,
                'recipient_email' => $voucher->recipient_email,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function setEnabled(GiftVoucher $voucher, bool $enabled): GiftVoucher
    {
        return DB::transaction(function () use ($voucher, $enabled) {
            $locked = GiftVoucher::query()->lockForUpdate()->findOrFail($voucher->id);

            if ($locked->status === GiftVoucher::STATUS_CANCELLED || $locked->status === GiftVoucher::STATUS_PENDING) {
                return $locked;
            }

            $locked->status = $enabled
                ? ((float) $locked->balance > 0 ? GiftVoucher::STATUS_ACTIVE : GiftVoucher::STATUS_EXHAUSTED)
                : GiftVoucher::STATUS_DISABLED;
            $locked->disabled_at = $enabled ? null : now();
            $locked->save();

            return $locked;
        }, 3);
    }

    public function releaseRedemption(Order $order, string $reason = 'released'): void
    {
        if (! $this->tablesAvailable()) {
            return;
        }

        DB::transaction(function () use ($order, $reason) {
            $redemptions = GiftVoucherRedemption::query()
                ->where('order_id', $order->id)
                ->whereIn('status', [GiftVoucherRedemption::STATUS_RESERVED, GiftVoucherRedemption::STATUS_REDEEMED])
                ->lockForUpdate()
                ->get();

            foreach ($redemptions as $redemption) {
                $voucher = GiftVoucher::query()->lockForUpdate()->find($redemption->gift_voucher_id);

                if (! $voucher) {
                    continue;
                }

                $voucher->balance = round((float) $voucher->balance + (float) $redemption->amount, 2);

                if (! in_array($voucher->status, [GiftVoucher::STATUS_DISABLED, GiftVoucher::STATUS_CANCELLED], true)) {
                    $voucher->status = GiftVoucher::STATUS_ACTIVE;
                }

                $voucher->save();

                $redemption->forceFill([
                    'status' => GiftVoucherRedemption::STATUS_RELEASED,
                    'released_at' => now(),
                    'release_reason' => Str::limit($reason, 191),
                ])->save();
            }
        }, 3);
    }

    private function syncPurchasedVouchers(int $orderId, array $orderData): void
    {
        $items = collect(data_get($orderData, 'cart.items', []));
        $giftItems = $items->filter(fn ($item) => $this->isGiftVoucherItem($item));

        if ($giftItems->isEmpty()) {
            GiftVoucher::query()
                ->where('purchase_order_id', $orderId)
                ->where('status', GiftVoucher::STATUS_PENDING)
                ->delete();

            return;
        }

        $buyerName = trim((string) data_get($orderData, 'address.fname') . ' ' . (string) data_get($orderData, 'address.lname'));
        $buyerEmail = Str::lower(trim((string) data_get($orderData, 'address.email')));
        $keptKeys = [];

        foreach ($giftItems as $item) {
            $data = $this->extractVoucherData($item);
            $cartItemKey = (string) data_get($item, 'id', self::CART_ITEM_ID);
            $amount = round((float) ($data['amount'] ?? data_get($item, 'price', 0)), 2);
            $keptKeys[] = $cartItemKey;

            $existing = GiftVoucher::query()
                ->where('purchase_order_id', $orderId)
                ->where('cart_item_key', $cartItemKey)
                ->first();

            if ($existing && $existing->status !== GiftVoucher::STATUS_PENDING) {
                continue;
            }

            GiftVoucher::query()->updateOrCreate(
                ['purchase_order_id' => $orderId, 'cart_item_key' => $cartItemKey],
                [
                    'initial_amount' => $amount,
                    'balance' => $amount,
                    'currency' => 'EUR',
                    'buyer_name' => $buyerName,
                    'buyer_email' => $buyerEmail,
                    'recipient_name' => trim((string) ($data['recipient_name'] ?? '')),
                    'recipient_email' => Str::lower(trim((string) ($data['recipient_email'] ?? ''))),
                    'sender_name' => trim((string) ($data['sender_name'] ?? '')),
                    'message' => trim((string) ($data['message'] ?? '')),
                    'locale' => in_array(($data['locale'] ?? 'hr'), ['hr', 'en'], true) ? $data['locale'] : 'hr',
                    'status' => GiftVoucher::STATUS_PENDING,
                ]
            );
        }

        GiftVoucher::query()
            ->where('purchase_order_id', $orderId)
            ->where('status', GiftVoucher::STATUS_PENDING)
            ->whereNotIn('cart_item_key', $keptKeys)
            ->delete();
    }

    private function reserveRedemption(int $orderId, array $cart): void
    {
        $condition = $this->extractGiftVoucherCondition($cart);

        if (! $condition) {
            $order = Order::query()->find($orderId);
            if ($order) {
                $this->releaseReservedRedemptionOnly($order, 'voucher_removed_from_order');
            }

            return;
        }

        $voucherId = (int) data_get($condition, 'attributes.gift_voucher_id');
        $amount = abs(round((float) ($condition['value'] ?? 0), 2));

        if ($voucherId <= 0 || $amount <= 0) {
            throw new GiftVoucherUnavailableException(__('front.gift_voucher.errors.unavailable'));
        }

        DB::transaction(function () use ($orderId, $voucherId, $amount) {
            $voucher = GiftVoucher::query()->lockForUpdate()->find($voucherId);

            if (! $voucher || $voucher->status !== GiftVoucher::STATUS_ACTIVE || $voucher->isExpired()) {
                throw new GiftVoucherUnavailableException(__('front.gift_voucher.errors.unavailable'));
            }

            $this->releaseExpiredReservationsLocked($voucher, $orderId);
            $redemption = GiftVoucherRedemption::query()
                ->where('gift_voucher_id', $voucher->id)
                ->where('order_id', $orderId)
                ->lockForUpdate()
                ->first();

            if ($redemption && $redemption->status === GiftVoucherRedemption::STATUS_REDEEMED) {
                return;
            }

            $availableForOrder = round((float) $voucher->balance, 2);
            if ($redemption && $redemption->status === GiftVoucherRedemption::STATUS_RESERVED) {
                $availableForOrder += round((float) $redemption->amount, 2);
            }

            if ($amount > $availableForOrder + 0.005) {
                throw new GiftVoucherUnavailableException(__('front.gift_voucher.errors.balance_changed'));
            }

            $voucher->balance = max(0, round($availableForOrder - $amount, 2));
            $voucher->save();

            GiftVoucherRedemption::query()->updateOrCreate(
                ['gift_voucher_id' => $voucher->id, 'order_id' => $orderId],
                [
                    'amount' => $amount,
                    'status' => GiftVoucherRedemption::STATUS_RESERVED,
                    'reserved_until' => now()->addMinutes((int) config('gift_vouchers.reservation_minutes', 180)),
                    'redeemed_at' => null,
                    'released_at' => null,
                    'release_reason' => null,
                ]
            );
        }, 3);
    }

    private function completeRedemption(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $redemption = GiftVoucherRedemption::query()
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->first();

            if (! $redemption || $redemption->status === GiftVoucherRedemption::STATUS_REDEEMED) {
                return;
            }

            $voucher = GiftVoucher::query()->lockForUpdate()->find($redemption->gift_voucher_id);

            if (! $voucher || $voucher->status === GiftVoucher::STATUS_CANCELLED) {
                throw new GiftVoucherUnavailableException(__('front.gift_voucher.errors.unavailable'));
            }

            if ($redemption->status === GiftVoucherRedemption::STATUS_RELEASED) {
                if ($voucher->status === GiftVoucher::STATUS_DISABLED) {
                    throw new GiftVoucherUnavailableException(__('front.gift_voucher.errors.unavailable'));
                }

                if ((float) $voucher->balance + 0.005 < (float) $redemption->amount) {
                    throw new GiftVoucherUnavailableException(__('front.gift_voucher.errors.balance_changed'));
                }

                $voucher->balance = max(0, round((float) $voucher->balance - (float) $redemption->amount, 2));
            }

            $redemption->forceFill([
                'status' => GiftVoucherRedemption::STATUS_REDEEMED,
                'redeemed_at' => now(),
                'released_at' => null,
                'release_reason' => null,
            ])->save();

            if ($voucher->status !== GiftVoucher::STATUS_DISABLED) {
                $voucher->status = (float) $voucher->balance <= 0.005
                    ? GiftVoucher::STATUS_EXHAUSTED
                    : GiftVoucher::STATUS_ACTIVE;
            }
            $voucher->save();
        }, 3);
    }

    private function releaseReservedRedemptionOnly(Order $order, string $reason): void
    {
        DB::transaction(function () use ($order, $reason) {
            $redemption = GiftVoucherRedemption::query()
                ->where('order_id', $order->id)
                ->where('status', GiftVoucherRedemption::STATUS_RESERVED)
                ->lockForUpdate()
                ->first();

            if (! $redemption) {
                return;
            }

            $voucher = GiftVoucher::query()->lockForUpdate()->find($redemption->gift_voucher_id);
            if ($voucher) {
                $voucher->balance = round((float) $voucher->balance + (float) $redemption->amount, 2);
                $voucher->save();
            }

            $redemption->forceFill([
                'status' => GiftVoucherRedemption::STATUS_RELEASED,
                'released_at' => now(),
                'release_reason' => Str::limit($reason, 191),
            ])->save();
        }, 3);
    }

    private function cancelPurchasedVouchers(Order $order): void
    {
        GiftVoucher::query()
            ->where('purchase_order_id', $order->id)
            ->whereNotIn('status', [GiftVoucher::STATUS_CANCELLED, GiftVoucher::STATUS_EXHAUSTED])
            ->update([
                'status' => GiftVoucher::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function availableAmountForCurrentOrder(GiftVoucher $voucher): float
    {
        $orderId = (int) data_get(CheckoutSession::getOrder(), 'id', 0);

        return DB::transaction(function () use ($voucher, $orderId) {
            $locked = GiftVoucher::query()->lockForUpdate()->find($voucher->id);

            if (! $locked || $locked->status !== GiftVoucher::STATUS_ACTIVE || $locked->isExpired()) {
                return 0.0;
            }

            $this->releaseExpiredReservationsLocked($locked, $orderId ?: null);
            $available = round((float) $locked->balance, 2);

            if ($orderId > 0) {
                $ownReservation = GiftVoucherRedemption::query()
                    ->where('gift_voucher_id', $locked->id)
                    ->where('order_id', $orderId)
                    ->where('status', GiftVoucherRedemption::STATUS_RESERVED)
                    ->first();

                if ($ownReservation) {
                    $available += round((float) $ownReservation->amount, 2);
                }
            }

            return max(0, round($available, 2));
        }, 3);
    }

    private function tablesAvailable(): bool
    {
        return Schema::hasTable('gift_vouchers')
            && Schema::hasTable('gift_voucher_redemptions');
    }

    private function releaseExpiredReservationsLocked(GiftVoucher $voucher, ?int $excludedOrderId = null): void
    {
        $expired = GiftVoucherRedemption::query()
            ->where('gift_voucher_id', $voucher->id)
            ->where('status', GiftVoucherRedemption::STATUS_RESERVED)
            ->whereNotNull('reserved_until')
            ->where('reserved_until', '<', now())
            ->when($excludedOrderId, fn ($query) => $query->where('order_id', '!=', $excludedOrderId))
            ->lockForUpdate()
            ->get();

        if ($expired->isEmpty()) {
            return;
        }

        $voucher->balance = round((float) $voucher->balance + (float) $expired->sum('amount'), 2);
        $voucher->save();

        GiftVoucherRedemption::query()
            ->whereIn('id', $expired->pluck('id'))
            ->update([
                'status' => GiftVoucherRedemption::STATUS_RELEASED,
                'released_at' => now(),
                'release_reason' => 'reservation_expired',
                'updated_at' => now(),
            ]);
    }

    private function extractGiftVoucherCondition(array $cart): ?array
    {
        foreach ($cart['detail_con'] ?? [] as $condition) {
            $array = json_decode(json_encode($condition), true) ?: [];
            if (data_get($array, 'attributes.type') === 'gift_voucher') {
                return $array;
            }
        }

        return null;
    }

    private function extractAttributes($item): array
    {
        $attributes = data_get($item, 'attributes', []);

        return json_decode(json_encode($attributes), true) ?: [];
    }

    private function conditionAttributes($condition): array
    {
        if (is_object($condition) && method_exists($condition, 'getAttributes')) {
            return json_decode(json_encode($condition->getAttributes()), true) ?: [];
        }

        return json_decode(json_encode(data_get($condition, 'attributes', [])), true) ?: [];
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = $this->normalizeCode(
                (string) config('gift_vouchers.code_prefix', 'BIBLOS-')
                . Str::random((int) config('gift_vouchers.code_length', 10))
            );
        } while (GiftVoucher::query()->where('code_hash', hash('sha256', $code))->exists());

        return $code;
    }
}
