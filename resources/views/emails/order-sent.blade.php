@extends('emails.layouts.base')

@section('content')
    @php
        $paymentTitle = \App\Helpers\LocaleHelper::paymentTitle($order->payment_code, $order->payment_method);
        $shippingTitle = \App\Helpers\LocaleHelper::shippingTitle($order->shipping_code, $order->shipping_method);
    @endphp
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td class="ag-mail-tableset">{{ __('front.general.hello', ['name' => $order->payment_fname]) }}</td>
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
                @if ($order->payment_code == 'bank')
                    <b>{{ $paymentTitle }}</b>

                    <p style="font-size:12px">{{ __('front.checkout.bank_received', ['order_id' => $order->id]) }}</p><p style="font-size:12px">{{ __('front.checkout.bank_instructions') }}</p>

                    <p style="font-size:12px">{{ __('front.checkout.bank_deadline') }}</p>

                    <p style="font-size:12px">{{ __('front.checkout.bank_cancel') }}</p>

                    <p style="font-size:12px">{{ __('front.checkout.bank_pay_amount', ['amount' => number_format($order->total, 2)]) }}</p>


                    <p style="font-size:12px">{{ __('front.checkout.bank_iban') }}: HR3123600001101595832<br>
                        {{ __('front.checkout.bank_model') }}: {{ $order->id }}-{{date('ym')}}</p>


                    <p style="font-size:12px">{{ __('front.checkout.bank_scan') }}</p>

                    <p><img src="{{ asset('media/img/qr/'.$order->id) }}.jpg" style="max-width:80%; border:1px solid #ccc; height:auto"></p>

                @elseif ($order->payment_code == 'cod')
                    <b>{{ $paymentTitle }}</b>
                    <p style="font-size:12px">{{ __('front.checkout.bank_received', ['order_id' => $order->id]) }}</p>
                @elseif ($order->payment_code == 'corvus')
                    <b>{{ $paymentTitle }}</b>
                    <p style="font-size:12px">{{ __('front.checkout.bank_received', ['order_id' => $order->id]) }}</p>
                @else
                    <b>{{ $paymentTitle }}</b>
                    <p style="font-size:12px">{{ __('front.checkout.bank_received', ['order_id' => $order->id]) }}</p>
                @endif
                <br>
                {{ __('front.email.shipping_method') }}: {{ $shippingTitle }}<br> {{ $order->napomena }}
                <br><br>

                {{ __('front.general.regards') }},<br>Antikvarijat Biblos
            </td>
        </tr>

    </table>
@endsection
