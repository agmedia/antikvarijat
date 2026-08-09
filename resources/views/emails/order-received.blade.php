@extends('emails.layouts.customer-notification')

@section('email_title', __('front.email.order_subject_admin', ['order_id' => $order->id]))
@section('preheader', __('front.email.order_admin_preheader', ['order_id' => $order->id]))

@section('content')
    @php
        $paymentTitle = \App\Helpers\LocaleHelper::paymentTitle($order->payment_code, $order->payment_method);
        $shippingTitle = \App\Helpers\LocaleHelper::shippingTitle($order->shipping_code, $order->shipping_method);
    @endphp

    <div style="margin:0 0 14px;font-size:11px;line-height:16px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;color:#a17436;">
        {{ __('front.email.order_admin_badge') }}
    </div>
    <h1 style="margin:0 0 11px;font-family:Georgia,'Times New Roman',serif;font-size:31px;line-height:39px;font-weight:normal;color:#193827;">
        {{ __('front.email.new_order') }}
    </h1>
    <p style="margin:0 0 25px;font-size:15px;line-height:24px;color:#5e685f;">
        {{ __('front.email.order_admin_intro') }}
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 26px;background:#193827;border-radius:9px;color:#fff;font-family:Arial,Helvetica,sans-serif;">
        <tr>
            <td style="padding:18px 21px;">
                <div style="font-size:10px;line-height:15px;letter-spacing:1px;text-transform:uppercase;color:#dfc79f;">{{ __('front.email.order_id') }}</div>
                <div style="font-family:Georgia,'Times New Roman',serif;font-size:23px;line-height:30px;">#{{ $order->id }}</div>
            </td>
            <td align="right" style="padding:18px 21px;font-size:13px;line-height:20px;color:#edf3ee;">
                {{ optional($order->created_at)->format('d.m.Y. H:i') }}
            </td>
        </tr>
    </table>

    @include('emails.layouts.partials.order-details', ['order' => $order])
    @include('emails.layouts.partials.order-price-table', ['order' => $order])

    <div style="margin-top:24px;padding:18px 20px;background-color:#f8f6f0;border-left:4px solid #bd9456;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:21px;color:#4f5e54;">
        <strong style="color:#193827;">{{ __('front.email.payment_method') }}:</strong> {{ $paymentTitle }}<br>
        <strong style="color:#193827;">{{ __('front.email.shipping_method') }}:</strong> {{ $shippingTitle }}
        @if($order->napomena)
            <br><strong style="color:#193827;">{{ __('front.email.customer_note') }}:</strong> {{ $order->napomena }}
        @endif
    </div>
@endsection
