@extends('emails.layouts.base')

@section('content')
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td class="ag-mail-tableset">
                <h2 style="margin-top: 0;">{{ __('front.reviews.request_title') }}</h2>
                <p>{{ __('front.general.hello', ['name' => $invitation->recipient_name]) }}</p>
                <p>{{ __('front.reviews.mail_intro') }}</p>
                <p>{{ __('front.reviews.request_intro', ['order' => $invitation->order_id]) }}</p>
                <p style="margin: 28px 0;">
                    <a href="{{ $reviewUrl }}" style="display:inline-block;background:#314837;color:#ffffff;padding:13px 22px;border-radius:5px;font-weight:bold;">
                        {{ __('front.reviews.mail_button') }}
                    </a>
                </p>
                <p style="font-size: 12px; color: #6c757d;">{{ __('front.reviews.mail_note') }}</p>
                <p>{{ __('front.general.regards') }},<br>Antikvarijat Biblos</p>
            </td>
        </tr>
    </table>
@endsection
