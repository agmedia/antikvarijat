@extends('emails.layouts.customer-notification')

@section('content')
    <div class="ag-mail-tableset" style="padding:0;">
        <h1 style="margin:0 0 18px;font-family:Georgia,'Times New Roman',serif;color:#193827;font-size:29px;line-height:1.35;font-weight:normal;">
            {{ __('contract_withdrawal.email.title') }}
        </h1>

        <p style="margin: 0 0 18px; color: #596273; font-size: 15px; line-height: 1.65;">
            {{ __('contract_withdrawal.email.intro') }}
        </p>

        <div style="margin-bottom: 20px; padding: 14px 16px; border: 1px solid #dacead; background: #fbf8ef; line-height: 1.65;">
            <strong>{{ __('contract_withdrawal.email.reference') }}:</strong> {{ $withdrawal->reference }}<br>
            <strong>{{ __('contract_withdrawal.email.submitted_at') }}:</strong> {{ optional($withdrawal->submitted_at)->format('d.m.Y. H:i:s T') }}<br>
            <strong>{{ __('contract_withdrawal.email.confirmation_method') }}:</strong> E-mail — {{ $withdrawal->email }}
        </div>

        <div style="margin: 20px 0; padding: 16px; border-left: 4px solid #bfa76a; background: #f8f9fb; color: #2b3445; font-size: 15px; font-weight: 700; line-height: 1.6;">
            {{ $withdrawal->declaration }}
        </div>

        <table role="presentation" style="width: 100%; margin: 0 !important; table-layout: auto !important; font-size: 14px;">
            <tr><td style="width: 38%; padding: 8px; border-bottom: 1px solid #e5e9f0; color: #747d8c;">{{ __('contract_withdrawal.full_name') }}</td><td style="padding: 8px; border-bottom: 1px solid #e5e9f0;">{{ $withdrawal->full_name }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e5e9f0; color: #747d8c;">E-mail</td><td style="padding: 8px; border-bottom: 1px solid #e5e9f0;">{{ $withdrawal->email }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e5e9f0; color: #747d8c;">{{ __('contract_withdrawal.phone') }}</td><td style="padding: 8px; border-bottom: 1px solid #e5e9f0;">{{ $withdrawal->phone ?: '—' }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e5e9f0; color: #747d8c;">{{ __('contract_withdrawal.review.address') }}</td><td style="padding: 8px; border-bottom: 1px solid #e5e9f0;">{{ $withdrawal->address_line }}, {{ $withdrawal->postal_code }} {{ $withdrawal->city }}, {{ $withdrawal->country_code }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e5e9f0; color: #747d8c;">{{ __('contract_withdrawal.order_number') }}</td><td style="padding: 8px; border-bottom: 1px solid #e5e9f0;">{{ $withdrawal->order_number }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e5e9f0; color: #747d8c;">{{ __('contract_withdrawal.contract_date') }}</td><td style="padding: 8px; border-bottom: 1px solid #e5e9f0;">{{ optional($withdrawal->contract_date)->format('d.m.Y.') ?: '—' }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e5e9f0; color: #747d8c;">{{ __('contract_withdrawal.received_date') }}</td><td style="padding: 8px; border-bottom: 1px solid #e5e9f0;">{{ optional($withdrawal->received_date)->format('d.m.Y.') ?: '—' }}</td></tr>
        </table>

        <div style="margin-top: 22px;">
            <strong>{{ __('contract_withdrawal.review.contract_items') }}</strong>
            <p style="margin: 7px 0 0; white-space: pre-line; line-height: 1.65;">{{ $withdrawal->items }}</p>
        </div>

        @if ($withdrawal->note)
            <div style="margin-top: 20px;">
                <strong>{{ __('contract_withdrawal.note') }}</strong>
                <p style="margin: 7px 0 0; white-space: pre-line; line-height: 1.65;">{{ $withdrawal->note }}</p>
            </div>
        @endif

        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #e5e9f0;">
            <strong>{{ __('contract_withdrawal.return_address') }}</strong>
            <p style="margin: 7px 0 0; white-space: pre-line; line-height: 1.65;">{{ $withdrawalSettings['return_address'] }}</p>
            <p style="margin: 12px 0 0; line-height: 1.65;">{{ $returnCostText }}</p>
            @if (($withdrawalSettings['instructions'] ?? '') !== '')
                <p style="margin: 12px 0 0; white-space: pre-line; line-height: 1.65;">{{ $withdrawalSettings['instructions'] }}</p>
            @endif
        </div>
    </div>
@endsection
