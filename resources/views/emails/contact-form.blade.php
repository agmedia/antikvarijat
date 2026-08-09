@extends('emails.layouts.customer-notification')

@section('email_title', __('front.email.contact_subject'))
@section('preheader', __('front.email.contact_form_received'))

@section('content')
    <div style="margin:0 0 14px;font-size:11px;line-height:16px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;color:#a17436;">Web kontakt</div>
    <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-size:31px;line-height:39px;font-weight:normal;color:#193827;">{{ __('front.email.contact_form_message') }}</h1>
    <p style="margin:0 0 25px;font-size:15px;line-height:24px;color:#5e685f;">{{ __('front.email.contact_form_received') }}</p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f8f6f0;border:1px solid #e6dfd2;border-radius:9px;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:20px;">
        <tr><td style="width:30%;padding:14px 16px 7px;color:#7a817b;">{{ __('front.checkout.first_name') }}</td><td style="padding:14px 16px 7px;color:#25342b;font-weight:bold;">{{ $contact['name'] }}</td></tr>
        <tr><td style="padding:7px 16px;color:#7a817b;">E-mail</td><td style="padding:7px 16px;color:#25342b;font-weight:bold;word-break:break-word;">{{ $contact['email'] }}</td></tr>
        @if(! empty($contact['phone']))
            <tr><td style="padding:7px 16px 14px;color:#7a817b;">{{ __('front.checkout.phone') }}</td><td style="padding:7px 16px 14px;color:#25342b;font-weight:bold;">{{ $contact['phone'] }}</td></tr>
        @endif
    </table>

    <div style="margin:22px 0;padding:19px 20px;border-left:4px solid #bd9456;background:#fbfaf7;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:23px;color:#36463c;white-space:pre-wrap;">{{ $contact['message'] }}</div>

    <a href="mailto:{{ $contact['email'] }}" class="mail-button" style="display:inline-block;padding:13px 23px;background:#193827;border-radius:6px;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:bold;">{{ __('front.email.reply_to_message') }} &nbsp;→</a>
@endsection
