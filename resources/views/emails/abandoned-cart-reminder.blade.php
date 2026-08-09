@extends('emails.layouts.customer-notification')

@section('email_title', __('front.email.abandoned_cart_subject_' . $sequence))
@section('preheader', __('front.email.abandoned_cart_preheader_' . $sequence))

@section('content')
    <div style="margin:0 0 14px;font-size:11px;line-height:16px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;color:#a17436;">
        {{ __('front.email.abandoned_cart_badge_' . $sequence) }}
    </div>

    <h1 style="margin:0 0 14px;font-family:Georgia,'Times New Roman',serif;font-size:31px;line-height:39px;font-weight:normal;color:#193827;">
        {{ __('front.email.abandoned_cart_heading_' . $sequence) }}
    </h1>

    <p style="margin:0 0 9px;font-size:16px;line-height:25px;color:#36463c;">
        {{ __('front.general.hello', ['name' => $order->payment_fname]) }}
    </p>
    <p style="margin:0 0 23px;font-size:15px;line-height:24px;color:#5e685f;">
        {{ __('front.email.abandoned_cart_intro_' . $sequence) }}
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;background:#193827;border-radius:10px;color:#ffffff;">
        <tr>
            <td style="padding:22px 24px;font-family:Georgia,'Times New Roman',serif;font-size:21px;line-height:30px;font-style:italic;text-align:center;">
                “{{ __('front.email.abandoned_cart_quote_' . $sequence) }}”
            </td>
        </tr>
    </table>

    @include('emails.layouts.partials.order-price-table', ['order' => $order])

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:30px 0 20px;">
        <tr>
            <td align="center" bgcolor="#193827" style="border-radius:7px;">
                <a href="{{ $recoveryUrl }}" target="_blank" class="mail-button" style="display:block;padding:15px 24px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:20px;font-weight:bold;text-align:center;color:#ffffff;background-color:#193827;border:1px solid #193827;border-radius:7px;">
                    {{ __('front.email.abandoned_cart_button') }} &nbsp;→
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 9px;font-size:12px;line-height:19px;color:#7a817b;">
        {{ __('front.email.abandoned_cart_stock_note') }}
    </p>
    <p style="margin:0;padding-top:17px;border-top:1px solid #ece7de;font-size:11px;line-height:18px;color:#8a8f89;word-break:break-word;">
        {{ __('front.email.abandoned_cart_link_note', ['days' => config('abandoned_cart.recovery_link_days', 7)]) }}<br>
        <a href="{{ $recoveryUrl }}" style="color:#193827;">{{ $recoveryUrl }}</a>
    </p>
@endsection
