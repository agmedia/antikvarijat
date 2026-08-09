@extends('emails.layouts.customer-notification')

@section('email_title', __('front.email.order_subject_customer', ['order_id' => $order->id]))
@section('preheader', __('front.email.order_customer_preheader', ['order_id' => $order->id]))

@section('content')
    @php
        $paymentTitle = \App\Helpers\LocaleHelper::paymentTitle($order->payment_code, $order->payment_method);
        $shippingTitle = \App\Helpers\LocaleHelper::shippingTitle($order->shipping_code, $order->shipping_method);
    @endphp

    <div style="margin:0 0 14px;font-size:11px;line-height:16px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;color:#a17436;">
        {{ __('front.email.order_customer_badge') }}
    </div>
    <h1 style="margin:0 0 14px;font-family:Georgia,'Times New Roman',serif;font-size:31px;line-height:39px;font-weight:normal;color:#193827;">
        {{ __('front.email.order_customer_title') }}
    </h1>
    <p style="margin:0 0 8px;font-size:16px;line-height:25px;color:#36463c;">
        {{ __('front.general.hello', ['name' => $order->payment_fname]) }}
    </p>
    <p style="margin:0 0 25px;font-size:15px;line-height:24px;color:#5e685f;">
        {{ __('front.email.order_customer_intro') }}
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

    @if ($order->payment_code === 'bank')
        <div style="margin-top:20px;padding:20px;border:1px solid #e6dfd2;border-radius:9px;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:21px;color:#4f5e54;">
            <strong style="display:block;margin-bottom:8px;color:#193827;">{{ __('front.checkout.bank_instructions') }}</strong>
            <p style="margin:0 0 8px;">{{ __('front.checkout.bank_deadline') }}</p>
            <p style="margin:0 0 8px;">{{ __('front.checkout.bank_cancel') }}</p>
            <p style="margin:0 0 8px;">{{ __('front.checkout.bank_pay_amount', ['amount' => number_format($order->total, 2)]) }}</p>
            <p style="margin:0;">
                {{ __('front.checkout.bank_iban') }}: <strong>HR3123600001101595832</strong><br>
                {{ __('front.checkout.bank_model') }}: <strong>{{ $order->id }}-{{ date('ym') }}</strong>
            </p>
            <p style="margin:16px 0 8px;">{{ __('front.checkout.bank_scan') }}</p>
            <img src="{{ asset('media/img/qr/' . $order->id) }}.jpg" alt="QR kod" style="display:block;max-width:220px;width:100%;height:auto;border:1px solid #e6dfd2;">
        </div>
    @endif

    <p style="margin:25px 0 0;padding-top:21px;border-top:1px solid #ece7de;font-size:14px;line-height:22px;color:#36463c;">
        {{ __('front.general.regards') }},<br><strong>Antikvarijat Biblos</strong>
    </p>
@endsection
