<h3>{{ __('front.email.customer_details') }}</h3>
<table cellspacing="0" cellpadding="0" border="0" width="100%">
    <tr>
        <td style="width: 40%">{{ __('front.email.full_name') }}:</td>
        <td style="width: 60%"><b>{{ $order->payment_fname . ' ' . $order->payment_lname }}</b></td>
    </tr>
    <tr>
        <td>{{ __('front.checkout.address') }}:</td>
        <td><b>{{ $order->payment_address }}</b></td>
    </tr>
    <tr>
        <td>{{ __('front.email.city') }}:</td>
        <td><b>{{ $order->payment_zip . ' ' . $order->payment_city }}</b></td>
    </tr>
    <tr>
        <td>{{ __('front.general.email_address') }}:</td>
        <td><b>{{ $order->payment_email }}</b></td>
    </tr>
    <tr>
        <td>{{ __('front.checkout.phone') }}:</td>
        <td><b>{{ ($order->payment_phone) ? $order->payment_phone : '' }}</b></td>
    </tr>
    @if( ! empty($order->company) || ! empty($order->oib))
        <tr><td></td><td></td></tr>
        <tr>
            <td>{{ __('front.email.company') }}:</td>
            <td><b>{{ ($order->company) ? $order->company : '' }}</b></td>
        </tr>
        <tr>
            <td>{{ __('front.email.vat_id') }}:</td>
            <td><b>{{ ($order->oib) ? $order->oib : '' }}</b></td>
        </tr>
    @endif
</table>
