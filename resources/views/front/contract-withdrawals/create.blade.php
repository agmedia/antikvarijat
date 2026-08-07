@extends('front.layouts.app')

@section('title', __('contract_withdrawal.meta_title'))
@section('description', __('contract_withdrawal.meta_description'))

@push('css_after')
    @include('front.contract-withdrawals.partials.styles')
@endpush

@section('content')
    <div class="container withdrawal-page">
        <nav class="mb-4" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item">
                    <a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}"><i class="ci-home"></i> {{ __('front.nav.home') }}</a>
                </li>
                <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ __('contract_withdrawal.breadcrumb') }}</li>
            </ol>
        </nav>

        <section class="d-md-flex justify-content-between align-items-center mb-4 pb-2">
            <div>
                <h1 class="h2 mb-2">{{ __('contract_withdrawal.page_title') }}</h1>
                <p class="withdrawal-page__intro">{{ __('contract_withdrawal.intro') }}</p>
            </div>
        </section>

        @if (session('success'))
            <div class="alert alert-success mb-4" role="status">
                <i class="ci-check-circle me-2"></i>{{ session('success') }}
            </div>
        @endif
        @if (session('warning'))
            <div class="alert alert-warning mb-4" role="alert">
                <i class="ci-security-announcement me-2"></i>{{ session('warning') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger mb-4" role="alert">
                <strong>{{ __('contract_withdrawal.validation_heading') }}</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="withdrawal-grid">
            <div class="withdrawal-card">
                <div class="withdrawal-card__body">
                    <div class="withdrawal-scope-note">
                        {{ __('contract_withdrawal.scope_before') }}
                        <a href="mailto:info@antikvarijat-biblos.hr">info@antikvarijat-biblos.hr</a>.
                    </div>

                    <form
                        method="POST"
                        action="{{ \App\Helpers\LocaleHelper::route('contract-withdrawal.review') }}"
                        novalidate
                        data-withdrawal-form
                        data-recaptcha-enabled="{{ $captchaEnabled ? '1' : '0' }}"
                    >
                        @csrf
                        <input type="hidden" name="recaptcha" value="" data-withdrawal-recaptcha>

                        <section class="withdrawal-section" aria-labelledby="withdrawal-consumer-title">
                            <h2 class="withdrawal-section__title" id="withdrawal-consumer-title">
                                <span class="withdrawal-section__number">1</span>
                                {{ __('contract_withdrawal.consumer_details') }}
                            </h2>

                            <div class="withdrawal-form-grid">
                                <div class="withdrawal-form-grid__full">
                                    <label class="form-label" for="withdrawal-full-name">{{ __('contract_withdrawal.full_name') }} *</label>
                                    <input
                                        class="form-control @error('full_name') is-invalid @enderror"
                                        id="withdrawal-full-name"
                                        type="text"
                                        name="full_name"
                                        value="{{ old('full_name', $prefill['full_name'] ?? '') }}"
                                        autocomplete="name"
                                        required
                                        maxlength="191"
                                    >
                                    @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div>
                                    <label class="form-label" for="withdrawal-email">{{ __('contract_withdrawal.confirmation_email') }} *</label>
                                    <input
                                        class="form-control @error('email') is-invalid @enderror"
                                        id="withdrawal-email"
                                        type="email"
                                        name="email"
                                        value="{{ old('email', $prefill['email'] ?? '') }}"
                                        autocomplete="email"
                                        required
                                        maxlength="191"
                                    >
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <div class="withdrawal-field-help">{{ __('contract_withdrawal.confirmation_email_help') }}</div>
                                </div>

                                <div>
                                    <label class="form-label" for="withdrawal-phone">{{ __('contract_withdrawal.phone') }} <span class="text-muted">({{ __('contract_withdrawal.optional') }})</span></label>
                                    <input
                                        class="form-control @error('phone') is-invalid @enderror"
                                        id="withdrawal-phone"
                                        type="text"
                                        name="phone"
                                        value="{{ old('phone', $prefill['phone'] ?? '') }}"
                                        autocomplete="tel"
                                        maxlength="80"
                                    >
                                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="withdrawal-form-grid__full">
                                    <label class="form-label" for="withdrawal-address">{{ __('contract_withdrawal.address_line') }} *</label>
                                    <input
                                        class="form-control @error('address_line') is-invalid @enderror"
                                        id="withdrawal-address"
                                        type="text"
                                        name="address_line"
                                        value="{{ old('address_line', $prefill['address_line'] ?? '') }}"
                                        autocomplete="street-address"
                                        required
                                        maxlength="255"
                                    >
                                    @error('address_line') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div>
                                    <label class="form-label" for="withdrawal-postal-code">{{ __('contract_withdrawal.postal_code') }} *</label>
                                    <input
                                        class="form-control @error('postal_code') is-invalid @enderror"
                                        id="withdrawal-postal-code"
                                        type="text"
                                        name="postal_code"
                                        value="{{ old('postal_code', $prefill['postal_code'] ?? '') }}"
                                        autocomplete="postal-code"
                                        required
                                        maxlength="32"
                                    >
                                    @error('postal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div>
                                    <label class="form-label" for="withdrawal-city">{{ __('contract_withdrawal.city') }} *</label>
                                    <input
                                        class="form-control @error('city') is-invalid @enderror"
                                        id="withdrawal-city"
                                        type="text"
                                        name="city"
                                        value="{{ old('city', $prefill['city'] ?? '') }}"
                                        autocomplete="address-level2"
                                        required
                                        maxlength="120"
                                    >
                                    @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div>
                                    <label class="form-label" for="withdrawal-country">{{ __('contract_withdrawal.country_code') }} *</label>
                                    <input
                                        class="form-control text-uppercase @error('country_code') is-invalid @enderror"
                                        id="withdrawal-country"
                                        type="text"
                                        name="country_code"
                                        value="{{ old('country_code', $prefill['country_code'] ?? 'HR') }}"
                                        autocomplete="country"
                                        required
                                        minlength="2"
                                        maxlength="2"
                                        pattern="[A-Za-z]{2}"
                                    >
                                    @error('country_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </section>

                        <section class="withdrawal-section" aria-labelledby="withdrawal-contract-title">
                            <h2 class="withdrawal-section__title" id="withdrawal-contract-title">
                                <span class="withdrawal-section__number">2</span>
                                {{ __('contract_withdrawal.contract_goods') }}
                            </h2>

                            <div class="withdrawal-form-grid">
                                <div class="withdrawal-form-grid__full">
                                    <label class="form-label" for="withdrawal-order-number">{{ __('contract_withdrawal.order_number') }} *</label>
                                    <input
                                        class="form-control @error('order_number') is-invalid @enderror"
                                        id="withdrawal-order-number"
                                        type="text"
                                        name="order_number"
                                        value="{{ old('order_number') }}"
                                        required
                                        maxlength="80"
                                    >
                                    @error('order_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div>
                                    <label class="form-label" for="withdrawal-contract-date">{{ __('contract_withdrawal.contract_date') }} <span class="text-muted">({{ __('contract_withdrawal.optional') }})</span></label>
                                    <input
                                        class="form-control @error('contract_date') is-invalid @enderror"
                                        id="withdrawal-contract-date"
                                        type="date"
                                        name="contract_date"
                                        value="{{ old('contract_date') }}"
                                        max="{{ now()->toDateString() }}"
                                    >
                                    @error('contract_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div>
                                    <label class="form-label" for="withdrawal-received-date">{{ __('contract_withdrawal.received_date') }} <span class="text-muted">({{ __('contract_withdrawal.optional') }})</span></label>
                                    <input
                                        class="form-control @error('received_date') is-invalid @enderror"
                                        id="withdrawal-received-date"
                                        type="date"
                                        name="received_date"
                                        value="{{ old('received_date') }}"
                                        max="{{ now()->toDateString() }}"
                                    >
                                    @error('received_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="withdrawal-form-grid__full">
                                    <label class="form-label" for="withdrawal-items">{{ __('contract_withdrawal.items') }} *</label>
                                    <textarea
                                        class="form-control @error('items') is-invalid @enderror"
                                        id="withdrawal-items"
                                        name="items"
                                        rows="6"
                                        placeholder="{{ __('contract_withdrawal.items_placeholder') }}"
                                        required
                                        maxlength="5000"
                                    >{{ old('items') }}</textarea>
                                    @error('items') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="withdrawal-form-grid__full">
                                    <label class="form-label" for="withdrawal-note">{{ __('contract_withdrawal.note') }} <span class="text-muted">({{ __('contract_withdrawal.optional') }})</span></label>
                                    <textarea
                                        class="form-control @error('note') is-invalid @enderror"
                                        id="withdrawal-note"
                                        name="note"
                                        rows="4"
                                        placeholder="{{ __('contract_withdrawal.note_placeholder') }}"
                                        maxlength="5000"
                                    >{{ old('note') }}</textarea>
                                    @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </section>

                        <p class="withdrawal-field-help mt-4">{{ __('contract_withdrawal.privacy_text') }}</p>
                        @error('recaptcha') <div class="text-danger small mt-2">{{ $message }}</div> @enderror

                        <button class="withdrawal-submit mt-3" type="submit" data-withdrawal-submit>
                            {{ __('contract_withdrawal.submit') }}
                            <i class="ci-arrow-right ms-2" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>
            </div>

            <aside class="withdrawal-card withdrawal-aside" aria-labelledby="withdrawal-important-title">
                <div class="withdrawal-card__body">
                    <h2 id="withdrawal-important-title">{{ __('contract_withdrawal.important_title') }}</h2>
                    <ul class="withdrawal-aside__list">
                        @foreach (array_slice(__('contract_withdrawal.important'), 0, 5) as $importantItem)
                            <li>{{ $importantItem }}</li>
                        @endforeach
                        <li>{{ $returnCostText }}</li>
                        @foreach (array_slice(__('contract_withdrawal.important'), 5) as $importantItem)
                            <li>{{ $importantItem }}</li>
                        @endforeach
                    </ul>

                    @if (($withdrawalSettings['return_address'] ?? '') !== '')
                        <div class="withdrawal-address"><strong>{{ __('contract_withdrawal.return_address') }}</strong><br>{{ $withdrawalSettings['return_address'] }}</div>
                    @endif
                </div>
            </aside>
        </div>
    </div>
@endsection

@if ($captchaEnabled)
    @push('js_after')
        <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.sitekey') }}"></script>
    @endpush
@endif

@push('js_after')
    <script>
        (function () {
            var form = document.querySelector('[data-withdrawal-form]');

            if (!form) {
                return;
            }

            form.addEventListener('submit', function (event) {
                var captchaEnabled = form.dataset.recaptchaEnabled === '1';
                var tokenInput = form.querySelector('[data-withdrawal-recaptcha]');
                var button = form.querySelector('[data-withdrawal-submit]');

                if (!captchaEnabled || !tokenInput || tokenInput.value) {
                    if (button) {
                        button.disabled = true;
                    }

                    return;
                }

                event.preventDefault();

                if (!window.grecaptcha) {
                    return;
                }

                if (button) {
                    button.disabled = true;
                }

                window.grecaptcha.ready(function () {
                    window.grecaptcha
                        .execute(@json(config('services.recaptcha.sitekey')), { action: 'contract_withdrawal' })
                        .then(function (token) {
                            tokenInput.value = token || '';
                            form.submit();
                        })
                        .catch(function () {
                            if (button) {
                                button.disabled = false;
                            }
                        });
                });
            });
        })();
    </script>
@endpush
