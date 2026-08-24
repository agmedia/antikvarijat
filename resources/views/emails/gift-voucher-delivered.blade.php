@extends('emails.layouts.base')

@section('content')
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td class="ag-mail-tableset" style="padding-bottom: 4px;">
                <h1 style="margin:0;color:#2a6248;font-size:25px;line-height:1.3;">{{ __('front.gift_voucher.email.heading') }}</h1>
            </td>
        </tr>
        <tr>
            <td class="ag-mail-tableset">
                {{ __('front.gift_voucher.email.greeting', ['name' => $giftVoucher->recipient_name]) }}
                {{ __('front.gift_voucher.email.intro', ['amount' => number_format($giftVoucher->initial_amount, 2, ',', '.')]) }}
            </td>
        </tr>
        @if($giftVoucher->message)
            <tr>
                <td class="ag-mail-tableset" style="padding-top:0;">
                    <div style="padding:16px 18px;border-left:4px solid #bfa76a;background:#f7f5ee;color:#3f4943;">
                        {!! nl2br(e($giftVoucher->message)) !!}
                        <div style="margin-top:10px;font-weight:700;">— {{ $giftVoucher->sender_name ?: $giftVoucher->buyer_name }}</div>
                    </div>
                </td>
            </tr>
        @endif
        <tr>
            <td class="ag-mail-tableset" style="text-align:center;">
                <div style="color:#68726c;font-size:12px;text-transform:uppercase;letter-spacing:1.4px;">{{ __('front.gift_voucher.email.code_label') }}</div>
                <div style="display:inline-block;margin-top:10px;padding:14px 20px;border:2px dashed #bfa76a;border-radius:8px;color:#2a6248;background:#fbfaf6;font-size:21px;font-weight:700;letter-spacing:1.2px;">
                    {{ $giftVoucher->code }}
                </div>
                <div style="margin-top:12px;color:#555;font-size:14px;">{{ __('front.gift_voucher.email.balance', ['amount' => number_format($giftVoucher->balance, 2, ',', '.')]) }}</div>
            </td>
        </tr>
        <tr>
            <td class="ag-mail-tableset" style="padding-top:0;">
                {{ __('front.gift_voucher.email.instructions') }}
            </td>
        </tr>
    </table>
@endsection
