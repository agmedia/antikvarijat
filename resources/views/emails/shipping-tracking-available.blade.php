@extends('emails.layouts.customer-notification')

@section('email_title', 'Vaša pošiljka je poslana')
@section('preheader', 'Narudžba #' . $order->id . ' predana je dostavnoj službi ' . $carrierLabel . '.')

@section('content')
    @php
        $customerName = trim((string) $order->payment_fname);
        $trackingCode = $order->tracking_code ?: $order->shipping_parcel_id;
        $trackingUrl = app(\App\Services\Shipping\OrderTrackingService::class)->trackingUrlForOrder($order);
    @endphp

    <div style="margin:0 0 14px;font-size:11px;line-height:16px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;color:#39744f;">
        &#10003; Narudžba je poslana
    </div>
    <h1 style="margin:0 0 14px;font-family:Georgia,'Times New Roman',serif;font-size:31px;line-height:39px;font-weight:normal;color:#193827;">
        Vaša pošiljka je na putu
    </h1>
    <p style="margin:0 0 8px;font-size:16px;line-height:25px;color:#36463c;">
        Poštovani{{ $customerName ? ' ' . $customerName : '' }},
    </p>
    <p style="margin:0 0 25px;font-size:15px;line-height:24px;color:#5e685f;">
        Vaša narudžba <strong style="color:#193827;">#{{ $order->id }}</strong> predana je dostavnoj službi {{ $carrierLabel }}.
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 25px;background:#eaf3ec;border:1px solid #cfe2d3;border-radius:9px;font-family:Arial,Helvetica,sans-serif;">
        <tr>
            <td style="padding:18px 21px;">
                <div style="font-size:10px;line-height:15px;letter-spacing:1px;text-transform:uppercase;color:#6f776f;">Narudžba</div>
                <div style="font-family:Georgia,'Times New Roman',serif;font-size:23px;line-height:30px;color:#193827;">#{{ $order->id }}</div>
            </td>
            <td align="right" style="padding:18px 21px;font-size:13px;line-height:20px;color:#39744f;font-weight:bold;">
                {{ $carrierLabel }}
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 25px;background-color:#f8f6f0;border:1px solid #e6dfd2;border-radius:9px;font-family:Arial,Helvetica,sans-serif;">
        <tr>
            <td style="padding:20px 22px;font-size:14px;line-height:22px;color:#4f5e54;">
                <p style="margin:0 0 9px;">
                    <strong style="color:#193827;">Broj pošiljke:</strong> {{ $trackingCode }}
                </p>

                @if($order->shipping_tracking_status)
                    <p style="margin:0 0 9px;">
                        <strong style="color:#193827;">Trenutni status:</strong> {{ $order->shipping_tracking_status }}
                    </p>
                @endif

                <p style="margin:0;">
                    <strong style="color:#193827;">Način dostave:</strong> {{ $order->shipping_method }}
                </p>
            </td>
        </tr>
    </table>

    @if($trackingUrl)
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 23px;">
            <tr>
                <td bgcolor="#193827" style="border-radius:6px;">
                    <a href="{{ $trackingUrl }}" target="_blank" class="mail-button" style="display:inline-block;padding:14px 25px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:20px;font-weight:bold;color:#ffffff;background-color:#193827;border:1px solid #193827;border-radius:6px;">
                        Prati pošiljku &nbsp;&rarr;
                    </a>
                </td>
            </tr>
        </table>
    @endif

    <p style="margin:0 0 22px;font-size:12px;line-height:19px;color:#7a817b;">
        Status se može promijeniti tek nakon što dostavna služba obradi pošiljku. Ako poveznica ne prikaže novi status odmah, pokušajte ponovno malo kasnije.
    </p>

    <p style="margin:0;padding-top:21px;border-top:1px solid #ece7de;font-size:14px;line-height:22px;color:#36463c;">
        Lijep pozdrav,<br><strong>Antikvarijat Biblos</strong>
    </p>
@endsection
