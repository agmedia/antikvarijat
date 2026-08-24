@extends('front.layouts.app')

@section('title', __('front.gift_voucher.meta_title'))
@section('description', __('front.gift_voucher.meta_description'))
@section('schema_page_type', 'WebPage')

@push('css_after')
    <link rel="stylesheet" media="screen" href="{{ \App\Helpers\Asset::url('css/gift-vouchers.css') }}">
@endpush

@section('content')
    @php
        $selectedAmount = (int) old('amount', config('gift_vouchers.default_amount', 30));
        $minAmount = (int) min($amounts);
        $maxAmount = (int) max($amounts);
        $step = (int) config('gift_vouchers.amount_step', 10);
    @endphp

    <main id="main-content" class="gift-voucher-page">
        <header class="gift-voucher-hero" style="background-image: url({{ asset('media/img/farmer.png') }});">
            <div class="container py-4 py-lg-5">
                <nav class="mb-4" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-dark mb-0">
                        <li class="breadcrumb-item">
                            <a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}">
                                <i class="fa-solid fa-house me-1" aria-hidden="true"></i>{{ __('front.nav.home') }}
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">{{ __('front.gift_voucher.title') }}</li>
                    </ol>
                </nav>

                <div class="row align-items-center g-4 g-xl-5">
                    <div class="col-lg-7">
                        <p class="gift-voucher-kicker"><i class="fa-solid fa-gift" aria-hidden="true"></i>{{ __('front.gift_voucher.eyebrow') }}</p>
                        <h1>{{ __('front.gift_voucher.title') }}</h1>
                        <p class="gift-voucher-lead">{{ __('front.gift_voucher.intro') }}</p>

                        <div class="gift-voucher-trust-row" aria-label="{{ __('front.gift_voucher.details_title') }}">
                            <span><i class="fa-solid fa-envelope" aria-hidden="true"></i>{{ __('front.gift_voucher.details.delivery') }}</span>
                            <span><i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>{{ __('front.gift_voucher.details.balance') }}</span>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="gift-voucher-preview" aria-hidden="true">
                            <div class="gift-voucher-preview-glow"></div>
                            <div class="gift-voucher-preview-top">
                                <img src="{{ asset('media/img/logobijeli.svg') }}" alt="" width="148" height="45">
                                <span>{{ __('front.gift_voucher.preview_label') }}</span>
                            </div>
                            <div class="gift-voucher-preview-body">
                                <div>
                                    <small>{{ __('front.gift_voucher.preview_for') }}</small>
                                    <strong id="gift-voucher-preview-recipient">{{ old('recipient_name') ?: __('front.gift_voucher.recipient_name') }}</strong>
                                </div>
                                <div class="gift-voucher-preview-amount"><span id="gift-voucher-preview-amount">{{ $selectedAmount }}</span><sup>€</sup></div>
                            </div>
                            <div class="gift-voucher-preview-code">{{ __('front.gift_voucher.preview_code') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <section class="container gift-voucher-content py-5">
            @include('front.layouts.partials.success-session')

            @if (session('error'))
                <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row g-4 g-xl-5 align-items-start">
                <div class="col-lg-7">
                    <div class="gift-voucher-form-card">
                        <div class="gift-voucher-section-heading">
                            <span class="gift-voucher-heading-icon"><i class="fa-solid fa-pen-nib" aria-hidden="true"></i></span>
                            <div>
                                <h2>{{ __('front.gift_voucher.form_title') }}</h2>
                                <p>{{ __('front.gift_voucher.form_intro') }}</p>
                            </div>
                        </div>

                        <form action="{{ \App\Helpers\LocaleHelper::route('poklon-bon.store') }}" method="POST">
                            @csrf

                            <fieldset class="gift-voucher-amount-fieldset">
                                <legend>{{ __('front.gift_voucher.amount') }}</legend>
                                <div class="gift-voucher-amount-readout"><span id="gift-voucher-amount">{{ $selectedAmount }}</span><small>€</small></div>
                                <input
                                    class="form-range gift-voucher-range"
                                    id="gift-voucher-range"
                                    type="range"
                                    min="{{ $minAmount }}"
                                    max="{{ $maxAmount }}"
                                    step="{{ $step }}"
                                    value="{{ $selectedAmount }}"
                                    aria-label="{{ __('front.gift_voucher.amount') }}"
                                >
                                <input id="gift-voucher-amount-input" type="hidden" name="amount" value="{{ $selectedAmount }}">

                                <div class="gift-voucher-range-labels"><span>{{ $minAmount }} €</span><span>{{ $maxAmount }} €</span></div>
                            </fieldset>

                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label" for="recipient-name">{{ __('front.gift_voucher.recipient_name') }} @include('back.layouts.partials.required-star')</label>
                                    <input class="form-control @error('recipient_name') is-invalid @enderror" id="recipient-name" type="text" name="recipient_name" value="{{ old('recipient_name') }}" maxlength="191" placeholder="{{ __('front.gift_voucher.recipient_name_placeholder') }}" autocomplete="name" required>
                                    @error('recipient_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label" for="recipient-email">{{ __('front.gift_voucher.recipient_email') }} @include('back.layouts.partials.required-star')</label>
                                    <input class="form-control @error('recipient_email') is-invalid @enderror" id="recipient-email" type="email" name="recipient_email" value="{{ old('recipient_email') }}" maxlength="191" placeholder="{{ __('front.gift_voucher.recipient_email_placeholder') }}" autocomplete="email" required>
                                    @error('recipient_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="sender-name">{{ __('front.gift_voucher.sender_name') }} @include('back.layouts.partials.required-star')</label>
                                    <input class="form-control @error('sender_name') is-invalid @enderror" id="sender-name" type="text" name="sender_name" value="{{ old('sender_name', $defaults['sender_name']) }}" maxlength="191" placeholder="{{ __('front.gift_voucher.sender_name_placeholder') }}" required>
                                    @error('sender_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="gift-message">{{ __('front.gift_voucher.message') }}</label>
                                    <textarea class="form-control @error('message') is-invalid @enderror" id="gift-message" name="message" rows="5" maxlength="1000" placeholder="{{ __('front.gift_voucher.message_placeholder') }}">{{ old('message') }}</textarea>
                                    <div class="form-text">{{ __('front.gift_voucher.message_help') }}</div>
                                    @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <button class="btn btn-primary btn-shadow gift-voucher-submit" type="submit">
                                <i class="fa-solid fa-bag-shopping me-2" aria-hidden="true"></i>{{ __('front.gift_voucher.submit') }}
                            </button>
                        </form>
                    </div>
                </div>

                <aside class="col-lg-5">
                    <div class="gift-voucher-info-card">
                        <h2>{{ __('front.gift_voucher.how_title') }}</h2>
                        <ol class="gift-voucher-steps">
                            <li>
                                <span>1</span>
                                <div><strong>{{ __('front.gift_voucher.steps.choose_title') }}</strong><p>{{ __('front.gift_voucher.steps.choose_text') }}</p></div>
                            </li>
                            <li>
                                <span>2</span>
                                <div><strong>{{ __('front.gift_voucher.steps.write_title') }}</strong><p>{{ __('front.gift_voucher.steps.write_text') }}</p></div>
                            </li>
                            <li>
                                <span>3</span>
                                <div><strong>{{ __('front.gift_voucher.steps.send_title') }}</strong><p>{{ __('front.gift_voucher.steps.send_text') }}</p></div>
                            </li>
                        </ol>
                    </div>

                    <div class="gift-voucher-details-card">
                        <h3>{{ __('front.gift_voucher.details_title') }}</h3>
                        <ul>
                            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i>{{ __('front.gift_voucher.details.separate') }}</li>
                            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i>{{ __('front.gift_voucher.details.balance') }}</li>
                            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i>{{ __('front.gift_voucher.details.payment') }}</li>
                            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i>{{ __('front.gift_voucher.details.delivery') }}</li>
                        </ul>
                    </div>
                </aside>
            </div>
        </section>
    </main>
@endsection

@push('js_after')
    <script>
        (() => {
            const range = document.getElementById('gift-voucher-range');
            const amountInput = document.getElementById('gift-voucher-amount-input');
            const readout = document.getElementById('gift-voucher-amount');
            const previewAmount = document.getElementById('gift-voucher-preview-amount');
            const recipient = document.getElementById('recipient-name');
            const previewRecipient = document.getElementById('gift-voucher-preview-recipient');
            const recipientFallback = @json(__('front.gift_voucher.recipient_name'));

            function setAmount(value) {
                const amount = String(value);
                range.value = amount;
                amountInput.value = amount;
                readout.textContent = amount;
                previewAmount.textContent = amount;
            }

            range.addEventListener('input', event => setAmount(event.target.value));
            recipient.addEventListener('input', event => {
                previewRecipient.textContent = event.target.value.trim() || recipientFallback;
            });
        })();
    </script>
@endpush
