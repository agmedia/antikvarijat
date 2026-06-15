@extends('emails.layouts.base')

@section('content')
    @php
        $paymentTitle = \App\Helpers\LocaleHelper::paymentTitle($order->payment_code, $order->payment_method);
    @endphp
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr><td class="ag-mail-tableset"><h3>{{ __('front.email.paid_title') }}</h3></td></tr>

        <tr>
            <td class="ag-mail-tableset">
                {{ __('front.email.order_id') }}: <strong>{{ $order->id }}</strong><br>
                {{ __('front.email.date') }}: <strong>{{ now()->format('d.m.Y') }}</strong><br>
                {{ __('front.email.status') }}: <strong>{{ __('front.email.paid_status') }}</strong> ({{ __('front.email.paid_status_description') }})
            </td>
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
                <br><br>{{ __('front.general.regards') }},<br>Antikvarijat Biblos
            </td>
        </tr>

    </table>
@endsection
