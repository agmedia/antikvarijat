@extends('emails.layouts.customer-notification')

@section('email_title', __('front.email.wishlist_subject', ['product' => $product['name']]))
@section('preheader', __('front.email.wishlist_preheader', ['product' => $product['name']]))

@section('content')
    @php
        $productUrl = url($product['url']);
        $productImage = method_exists($product, 'getRawOriginal') && $product->getRawOriginal('image')
            ? $product['image']
            : null;
        $productPrice = ! empty($product['special'])
            ? $product->main_special_text
            : $product->main_price_text;
    @endphp

    <div style="margin:0 0 14px;font-size:11px;line-height:16px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;color:#a17436;">
        {{ __('front.email.wishlist_badge') }}
    </div>
    <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-size:31px;line-height:39px;font-weight:normal;color:#193827;">
        {{ __('front.email.wishlist_heading') }}
    </h1>
    <p style="margin:0 0 28px;font-size:16px;line-height:26px;color:#5e685f;">
        {{ __('front.email.wishlist_intro') }}
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f8f6f0;border:1px solid #e6dfd2;border-radius:10px;">
        <tr>
            @if ($productImage)
                <td width="132" valign="middle" align="center" class="mail-product-image" style="width:132px;padding:18px;">
                    <a href="{{ $productUrl }}" target="_blank">
                        <img src="{{ $productImage }}" width="96" alt="{{ $product['name'] }}" style="display:block;width:96px;max-width:96px;height:auto;margin:0 auto;border-radius:4px;box-shadow:0 4px 12px rgba(25,56,39,.14);">
                    </a>
                </td>
            @endif
            <td valign="middle" class="mail-product-details" style="padding:22px 24px 22px {{ $productImage ? '0' : '24px' }};font-family:Arial,Helvetica,sans-serif;">
                <div style="margin:0 0 7px;font-size:11px;line-height:16px;letter-spacing:1px;text-transform:uppercase;color:#8b8f89;">
                    {{ __('front.email.item_name') }}
                </div>
                <div style="margin:0 0 11px;font-family:Georgia,'Times New Roman',serif;font-size:21px;line-height:28px;color:#193827;">
                    {{ $product['name'] }}
                </div>
                @if ($productPrice)
                    <div style="font-size:15px;line-height:22px;font-weight:bold;color:#a17436;">
                        {{ __('front.email.price') }}: {{ $productPrice }}
                    </div>
                @endif
            </td>
        </tr>
    </table>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:30px 0 22px;">
        <tr>
            <td bgcolor="#193827" style="border-radius:6px;">
                <a href="{{ $productUrl }}" target="_blank" class="mail-button" style="display:inline-block;padding:14px 25px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:20px;font-weight:bold;color:#ffffff;background-color:#193827;border:1px solid #193827;border-radius:6px;">
                    {{ __('front.email.view_wishlist_item') }} &nbsp;→
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0;padding-top:20px;border-top:1px solid #ece7de;font-size:12px;line-height:19px;color:#7a817b;">
        {{ __('front.email.wishlist_stock_note') }}
    </p>
@endsection
