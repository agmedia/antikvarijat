@extends('emails.layouts.customer-notification')

@section('email_title', __('front.email.book_purchase_subject'))
@section('preheader', __('front.email.book_purchase_new'))

@section('content')
    <div style="margin:0 0 14px;font-size:11px;line-height:16px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;color:#a17436;">Otkup knjiga</div>
    <h1 style="margin:0 0 25px;font-family:Georgia,'Times New Roman',serif;font-size:31px;line-height:39px;font-weight:normal;color:#193827;">{{ __('front.email.book_purchase_new') }}</h1>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f8f6f0;border:1px solid #e6dfd2;border-radius:9px;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:20px;">
        @foreach([
            __('front.email.full_name') => $requestData['full_name'],
            __('front.book_purchase.postal_code') => $requestData['postal_code'],
            'E-mail' => $requestData['email'],
            __('front.book_purchase.phone') => $requestData['phone'],
            __('front.email.submission_id') => $requestData['submission_id'],
            __('front.email.submitted_at') => $requestData['submitted_at'],
        ] as $label => $value)
            <tr>
                <td style="width:36%;padding:{{ $loop->first ? '14px 16px 7px' : ($loop->last ? '7px 16px 14px' : '7px 16px') }};color:#7a817b;">{{ $label }}</td>
                <td style="padding:{{ $loop->first ? '14px 16px 7px' : ($loop->last ? '7px 16px 14px' : '7px 16px') }};color:#25342b;font-weight:bold;word-break:break-word;">{{ $value }}</td>
            </tr>
        @endforeach
    </table>

    <div style="margin:25px 0 10px;font-size:11px;line-height:16px;font-weight:bold;letter-spacing:1.2px;text-transform:uppercase;color:#a17436;">{{ __('front.email.photos') }}</div>
    @forelse($requestData['photos'] as $photo)
        <p style="margin:0 0 8px;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:20px;">
            <a href="{{ $photo['url'] }}" target="_blank" rel="noopener" style="color:#193827;text-decoration:underline;">{{ $photo['name'] }}</a>
        </p>
    @empty
        <p style="margin:0;font-size:13px;line-height:20px;color:#7a817b;">—</p>
    @endforelse
@endsection
