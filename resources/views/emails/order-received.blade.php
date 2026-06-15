@extends('emails.layouts.base')

@section('content')
    @php
        $paymentTitle = \App\Helpers\LocaleHelper::paymentTitle($order->payment_code, $order->payment_method);
        $shippingTitle = \App\Helpers\LocaleHelper::shippingTitle($order->shipping_code, $order->shipping_method);
    @endphp
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td class="ag-mail-tableset">{{ __('front.email.new_order') }} - {{ $order->created_at }}</td>
        </tr>
        <tr>
            <td class="ag-mail-tableset"> <h3>{{ __('front.email.order_number', ['order_id' => $order->id]) }} </h3></td>
        </tr>
        <tr>
            <td class="ag-mail-tableset">
                @include('emails.layouts.partials.order-details', ['order' => $order])
            </td>
        </tr>
        <tr>
            <td class="ag-mail-tableset">
                @include('emails.layouts.partials.order-price-table', ['order' => $order])
            </td>
        </tr>
        <tr>
            <td class="ag-mail-tableset">
                {{ __('front.email.payment_method') }}:
                <b>{{ $paymentTitle }}</b>
                <br>
                {{ __('front.email.shipping_method') }}: {{ $shippingTitle }}<br> {{ $order->napomena }}
                <br><br>

                {{ __('front.general.regards') }},<br>Antikvarijat Biblos
            </td>
        </tr>


    </table>
@endsection
