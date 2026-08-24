<div style="margin:26px 0 10px;font-size:11px;line-height:16px;font-weight:bold;letter-spacing:1.2px;text-transform:uppercase;color:#a17436;">
    {{ __('front.email.order_items') }}
</div>
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:18px;border:1px solid #e1ddd4;">
    <tr>
        <th align="left" style="padding:11px 10px;background-color:#193827;color:#ffffff;font-size:12px;">{{ __('front.general.product') }}</th>
        <th align="center" width="12%" style="padding:11px 7px;background-color:#193827;color:#ffffff;font-size:12px;">{{ __('front.general.quantity_short') }}</th>
        <th align="right" width="18%" style="padding:11px 7px;background-color:#193827;color:#ffffff;font-size:12px;">{{ __('front.general.price') }}</th>
        <th align="right" width="20%" style="padding:11px 10px;background-color:#193827;color:#ffffff;font-size:12px;">{{ __('front.general.total') }}</th>
    </tr>
    @foreach ($order->products as $product)
        @php
            $catalogProduct = $product->product;
            $rawProductImage = $catalogProduct ? trim((string) $catalogProduct->getRawOriginal('image')) : '';
            $productImage = null;
            if ($rawProductImage !== '') {
                if (preg_match('#^https?://#i', $rawProductImage)) {
                    $productImage = $rawProductImage;
                } else {
                    $imageHost = rtrim((string) (config('settings.images_domain') ?: config('app.url')), '/');
                    $imagePath = preg_replace('/\.jpg$/i', '.webp', ltrim($rawProductImage, '/'));
                    $productImage = $imageHost . '/' . $imagePath;
                }
            }
        @endphp
        <tr>
            <td style="padding:11px 10px;border-bottom:1px solid #e8e3da;color:#25342b;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        @if($productImage)
                            <td width="42" valign="middle" style="width:42px;padding-right:9px;">
                                <img src="{{ $productImage }}" width="36" alt="" style="display:block;width:36px;max-width:36px;height:auto;border-radius:3px;">
                            </td>
                        @endif
                        <td valign="middle">
                            <strong>{{ $product->name }}</strong>
                            @if($catalogProduct && $catalogProduct->sku)
                                <br><span style="font-size:10px;color:#7a817b;">{{ $catalogProduct->sku }}</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
            <td align="center" style="padding:11px 7px;border-bottom:1px solid #e8e3da;color:#4f5e54;">{{ $product->quantity }}</td>
            <td align="right" style="padding:11px 7px;border-bottom:1px solid #e8e3da;color:#4f5e54;white-space:nowrap;">€ {{ number_format($product->price, 2, ',', '.') }}</td>
            <td align="right" style="padding:11px 10px;border-bottom:1px solid #e8e3da;color:#25342b;font-weight:bold;white-space:nowrap;">€ {{ number_format($product->total, 2, ',', '.') }}</td>
        </tr>
    @endforeach
    @php($emailGiftVouchers = \Illuminate\Support\Facades\Schema::hasTable('gift_vouchers') ? $order->giftVouchers : collect())
    @foreach ($emailGiftVouchers as $voucher)
        <tr>
            <td style="padding:11px 10px;border-bottom:1px solid #e8e3da;color:#25342b;">
                <strong>{{ $voucher->locale === 'en' ? 'Gift voucher' : 'Poklon bon' }}</strong>
                <br><span style="font-size:10px;color:#7a817b;">{{ $voucher->recipient_name }} · {{ $voucher->recipient_email }}</span>
            </td>
            <td align="center" style="padding:11px 7px;border-bottom:1px solid #e8e3da;color:#4f5e54;">1</td>
            <td align="right" style="padding:11px 7px;border-bottom:1px solid #e8e3da;color:#4f5e54;white-space:nowrap;">€ {{ number_format($voucher->initial_amount, 2, ',', '.') }}</td>
            <td align="right" style="padding:11px 10px;border-bottom:1px solid #e8e3da;color:#25342b;font-weight:bold;white-space:nowrap;">€ {{ number_format($voucher->initial_amount, 2, ',', '.') }}</td>
        </tr>
    @endforeach
</table>

<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:18px;">
    @foreach ($order->totals as $total)
        @php($totalKey = 'front.email.total_' . $total->code)
        <tr>
            <td align="right" style="padding:7px 10px;color:#667068;{{ $loop->last ? 'border-top:2px solid #bd9456;font-size:14px;font-weight:bold;color:#193827;' : '' }}">
                {{ trans($totalKey) !== $totalKey ? trans($totalKey) : $total->title }}
            </td>
            <td align="right" width="24%" style="padding:7px 10px;white-space:nowrap;color:#25342b;{{ $loop->last ? 'border-top:2px solid #bd9456;font-size:14px;font-weight:bold;' : '' }}">
                @if ($order->shipping_state != 'Croatia' && $total->code == 'shipping')
                    {{ __('front.email.shipping_cost_later') }}
                @else
                    € {{ number_format($total->value, 2, ',', '.') }}
                @endif
            </td>
        </tr>
    @endforeach
</table>

<p style="margin:8px 0 0;text-align:right;font-family:Arial,Helvetica,sans-serif;font-size:10px;line-height:16px;color:#8a8f89;">
    {{ __('front.email.vat_included') }}
    @foreach ($order->totals as $total)
        @if($total->code == 'subtotal')
            € <strong>{{ number_format($total->value - ($total->value / 1.05), 2, ',', '.') }}</strong> {{ __('front.email.book_vat') }}
        @elseif($total->code == 'shipping')
            · € <strong>{{ number_format($total->value - ($total->value / 1.25), 2, ',', '.') }}</strong> {{ __('front.email.shipping_vat') }}
        @endif
    @endforeach
</p>
