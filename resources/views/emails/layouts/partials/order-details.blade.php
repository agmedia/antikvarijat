<div style="margin:0 0 10px;font-size:11px;line-height:16px;font-weight:bold;letter-spacing:1.2px;text-transform:uppercase;color:#a17436;">
    {{ __('front.email.customer_details') }}
</div>
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0;background-color:#f8f6f0;border:1px solid #e6dfd2;border-radius:9px;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:19px;">
    <tr>
        <td style="width:36%;padding:14px 16px 7px;color:#7a817b;">{{ __('front.email.full_name') }}</td>
        <td style="padding:14px 16px 7px;color:#25342b;font-weight:bold;">{{ $order->payment_fname . ' ' . $order->payment_lname }}</td>
    </tr>
    <tr>
        <td style="padding:7px 16px;color:#7a817b;">{{ __('front.checkout.address') }}</td>
        <td style="padding:7px 16px;color:#25342b;font-weight:bold;">{{ $order->payment_address }}</td>
    </tr>
    <tr>
        <td style="padding:7px 16px;color:#7a817b;">{{ __('front.email.city') }}</td>
        <td style="padding:7px 16px;color:#25342b;font-weight:bold;">{{ $order->payment_zip . ' ' . $order->payment_city }}</td>
    </tr>
    <tr>
        <td style="padding:7px 16px;color:#7a817b;">{{ __('front.general.email_address') }}</td>
        <td style="padding:7px 16px;color:#25342b;font-weight:bold;word-break:break-word;">{{ $order->payment_email }}</td>
    </tr>
    <tr>
        <td style="padding:7px 16px 14px;color:#7a817b;">{{ __('front.checkout.phone') }}</td>
        <td style="padding:7px 16px 14px;color:#25342b;font-weight:bold;">{{ $order->payment_phone ?: '—' }}</td>
    </tr>
    @if(! empty($order->company) || ! empty($order->oib))
        <tr>
            <td style="padding:10px 16px 7px;border-top:1px solid #e6dfd2;color:#7a817b;">{{ __('front.email.company') }}</td>
            <td style="padding:10px 16px 7px;border-top:1px solid #e6dfd2;color:#25342b;font-weight:bold;">{{ $order->company ?: '—' }}</td>
        </tr>
        <tr>
            <td style="padding:7px 16px 14px;color:#7a817b;">{{ __('front.email.vat_id') }}</td>
            <td style="padding:7px 16px 14px;color:#25342b;font-weight:bold;">{{ $order->oib ?: '—' }}</td>
        </tr>
    @endif
</table>
