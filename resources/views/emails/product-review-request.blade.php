@extends('emails.layouts.customer-notification')

@section('email_title', __('front.reviews.mail_subject', ['order' => $invitation->order_id]))
@section('preheader', __('front.reviews.mail_preheader', ['order' => $invitation->order_id]))

@section('content')
    <div style="margin:0 0 14px;font-size:11px;line-height:16px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;color:#a17436;">
        {{ __('front.reviews.mail_badge') }}
    </div>
    <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-size:31px;line-height:39px;font-weight:normal;color:#193827;">
        {{ __('front.reviews.request_title') }}
    </h1>
    <p style="margin:0 0 12px;font-size:16px;line-height:26px;color:#36463c;">
        {{ __('front.general.hello', ['name' => $invitation->recipient_name]) }}
    </p>
    <p style="margin:0 0 25px;font-size:16px;line-height:26px;color:#5e685f;">
        {{ __('front.reviews.mail_intro') }}
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 25px;background-color:#f8f6f0;border:1px solid #e6dfd2;border-radius:10px;">
        <tr>
            <td style="padding:21px 23px;font-family:Arial,Helvetica,sans-serif;">
                <div style="margin:0 0 5px;font-size:11px;line-height:16px;letter-spacing:1px;text-transform:uppercase;color:#8b8f89;">
                    {{ __('front.reviews.mail_order_label') }}
                </div>
                <div style="font-family:Georgia,'Times New Roman',serif;font-size:24px;line-height:30px;color:#193827;">
                    #{{ $invitation->order_id }}
                </div>
            </td>
            <td width="150" align="right" style="padding:21px 23px;font-family:Arial,Helvetica,sans-serif;font-size:18px;line-height:24px;letter-spacing:2px;color:#bd9456;white-space:nowrap;">
                ★ ★ ★ ★ ★
            </td>
        </tr>
    </table>

    <p style="margin:0 0 27px;font-size:15px;line-height:24px;color:#5e685f;">
        {{ __('front.reviews.request_intro', ['order' => $invitation->order_id]) }}
    </p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 23px;">
        <tr>
            <td bgcolor="#193827" style="border-radius:6px;">
                <a href="{{ $reviewUrl }}" target="_blank" class="mail-button" style="display:inline-block;padding:14px 25px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:20px;font-weight:bold;color:#ffffff;background-color:#193827;border:1px solid #193827;border-radius:6px;">
                    {{ __('front.reviews.mail_button') }} &nbsp;→
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 22px;font-size:12px;line-height:19px;color:#7a817b;">
        {{ __('front.reviews.mail_note') }}
        {{ __('front.reviews.mail_link_valid', ['days' => config('reviews.request_link_days', 180)]) }}
    </p>

    <p style="margin:0;padding-top:21px;border-top:1px solid #ece7de;font-size:14px;line-height:22px;color:#36463c;">
        {{ __('front.general.regards') }},<br>
        <strong>Antikvarijat Biblos</strong>
    </p>
@endsection
