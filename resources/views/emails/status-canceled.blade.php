@extends('emails.layouts.customer-notification')

@section('email_title', __('front.email.canceled_title'))
@section('preheader', __('front.email.canceled_preheader', ['order_id' => $order->id]))

@section('content')
    @php($paymentTitle = \App\Helpers\LocaleHelper::paymentTitle($order->payment_code, $order->payment_method))

    <div style="margin:0 0 14px;font-size:11px;line-height:16px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;color:#a84e3a;">× {{ __('front.email.canceled_status') }}</div>
    <h1 style="margin:0 0 14px;font-family:Georgia,'Times New Roman',serif;font-size:31px;line-height:39px;font-weight:normal;color:#193827;">{{ __('front.email.canceled_title') }}</h1>
    <p style="margin:0 0 25px;font-size:15px;line-height:24px;color:#5e685f;">{{ __('front.email.canceled_status_description') }}</p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 26px;background:#fbefec;border:1px solid #efd4ce;border-radius:9px;font-family:Arial,Helvetica,sans-serif;">
        <tr>
            <td style="padding:18px 21px;color:#193827;"><strong>#{{ $order->id }}</strong></td>
            <td align="right" style="padding:18px 21px;color:#a84e3a;font-weight:bold;">{{ now()->format('d.m.Y.') }}</td>
        </tr>
    </table>

    @include('emails.layouts.partials.order-details', ['order' => $order])
    @include('emails.layouts.partials.order-price-table', ['order' => $order])

    <p style="margin:23px 0 0;font-size:13px;line-height:21px;color:#4f5e54;">{{ __('front.email.payment_method') }}: <strong>{{ $paymentTitle }}</strong></p>
@endsection
