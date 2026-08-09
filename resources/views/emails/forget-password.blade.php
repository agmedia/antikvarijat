@extends('emails.layouts.customer-notification')

@section('email_title', __('front.email.password_heading'))
@section('preheader', __('front.email.password_preheader'))

@section('content')
    @php($resetUrl = route('reset.password.get', $token))

    <div style="margin:0 0 14px;font-size:11px;line-height:16px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;color:#a17436;">
        {{ __('front.email.password_badge') }}
    </div>
    <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-size:31px;line-height:39px;font-weight:normal;color:#193827;">
        {{ __('front.email.password_heading') }}
    </h1>
    <p style="margin:0 0 26px;font-size:16px;line-height:26px;color:#5e685f;">
        {{ __('front.email.password_reset_text') }}
    </p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
        <tr>
            <td bgcolor="#193827" style="border-radius:6px;">
                <a href="{{ $resetUrl }}" target="_blank" class="mail-button" style="display:inline-block;padding:14px 25px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:20px;font-weight:bold;color:#ffffff;background-color:#193827;border:1px solid #193827;border-radius:6px;">
                    {{ __('front.email.password_button') }} &nbsp;→
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px;font-size:12px;line-height:19px;color:#7a817b;">{{ __('front.email.password_fallback') }}</p>
    <p style="margin:0 0 22px;font-size:11px;line-height:17px;word-break:break-all;">
        <a href="{{ $resetUrl }}" style="color:#39744f;text-decoration:underline;">{{ $resetUrl }}</a>
    </p>
    <p style="margin:0;padding-top:20px;border-top:1px solid #ece7de;font-size:12px;line-height:19px;color:#7a817b;">
        {{ __('front.email.password_ignore') }}
    </p>
@endsection
