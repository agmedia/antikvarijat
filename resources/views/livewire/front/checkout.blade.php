<div id="checkout-flow" class="checkout-flow">
    <nav class="checkout-progress-shell" aria-label="{{ __('front.checkout.checkout') }}">
    <div class="steps steps-dark checkout-steps">
        <a class="step-item active" href="{{ \App\Helpers\LocaleHelper::route('kosarica') }}">
            <div class="step-progress"><span class="step-count">1</span></div>
            <div class="step-label"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>{{ __('front.checkout.cart') }}</div>
        </a>
        <a class="step-item @if($step == 'podaci') current @endif @if(in_array($step, ['podaci', 'dostava', 'placanje'])) active @endif" wire:click="changeStep('podaci')" href="javascript:void(0);">
            <div class="step-progress"><span class="step-count">2</span></div>
            <div class="step-label"><i class="fa-solid fa-circle-user" aria-hidden="true"></i>{{ __('front.checkout.details') }}</div>
        </a>
        <a class="step-item @if($step == 'dostava') current @endif @if(in_array($step, ['dostava', 'placanje'])) active @endif" wire:click="changeStep('dostava')" href="javascript:void(0);">
            <div class="step-progress"><span class="step-count">3</span></div>
            <div class="step-label"><i class="fa-solid fa-box" aria-hidden="true"></i>{{ __('front.checkout.shipping') }}</div>
        </a>
        <a class="step-item @if($step == 'placanje') current @endif @if(in_array($step, ['placanje'])) active @endif" wire:click="changeStep('placanje')" href="javascript:void(0);">
            <div class="step-progress"><span class="step-count">4</span></div>
            <div class="step-label"><i class="fa-solid fa-credit-card" aria-hidden="true"></i>{{ __('front.checkout.payment') }}</div>
        </a>
        <a class="step-item" href="{{ ($payment != '') ? \App\Helpers\LocaleHelper::route('pregled') : '#' }}">
            <div class="step-progress"><span class="step-count">5</span></div>
            <div class="step-label"><i class="fa-solid fa-eye" aria-hidden="true"></i>{{ __('front.checkout.review') }}</div>
        </a>

        <a class="step-item" href="#">
            <div class="step-progress"><span class="step-count">6</span></div>
            <div class="step-label"><i class="fa-solid fa-circle-check" aria-hidden="true"></i>{{ __('front.checkout.success') }}</div>
        </a>
    </div>
    </nav>

    @if ( ! empty($gdl) && ! $gdl_shipping && ! $gdl_payment)
        @section('google_data_layer')
            <script>
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({ ecommerce: null });
                window.dataLayer.push({
                    'event': '<?php echo $gdl_event; ?>',
                    'ecommerce': {
                        'items': <?php echo json_encode($gdl); ?>
                    } });
            </script>
        @endsection
    @endif

    @if ( ! empty($gdl) && $gdl_shipping && $gdl_event == 'add_shipping_info')
        @section('google_data_layer')
            <script>
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({ ecommerce: null });
                window.dataLayer.push({
                    'event': '<?php echo $gdl_event; ?>',
                    'ecommerce': {
                        'shipping_tier': '<?php echo $gdl_shipping; ?>',
                        'items': <?php echo json_encode($gdl); ?>
                    } });
            </script>
        @endsection
    @endif

    @if ( ! empty($gdl) && $gdl_payment && $gdl_event == 'add_payment_info')
        @section('google_data_layer')
            <script>
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({ ecommerce: null });
                window.dataLayer.push({
                    'event': '<?php echo $gdl_event; ?>',
                    'ecommerce': {
                        'payment_type': '<?php echo $gdl_payment; ?>',
                        'items': <?php echo json_encode($gdl); ?>
                    } });
            </script>
        @endsection
    @endif
    <div class="checkout-panel checkout-surface">
    @if ($step == 'podaci')
        <div class="checkout-section-heading d-flex align-items-start justify-content-between gap-3 pb-3 mb-4 border-bottom">
            <div class="d-flex align-items-center gap-3">
                <span class="checkout-heading-icon"><i class="fa-solid fa-address-card" aria-hidden="true"></i></span>
                <div>
                    <h2 class="h5 mb-1">{{ __('front.checkout.billing_address') }}</h2>
                    <p class="text-muted fs-sm mb-0">{{ __('front.checkout.details_intro') }}</p>
                </div>
            </div>
            <span class="text-muted fs-xs text-nowrap"><span class="text-danger">*</span> {{ __('front.checkout.required_fields') }}</span>
        </div>

        @if (auth()->guest())
            <div class="alert alert-secondary checkout-login-prompt d-flex align-items-center mb-3" role="alert">
                <div class="alert-icon">
                    <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                </div>
                <div>
                    <button class="checkout-login-link alert-link" type="button" data-auth-tab="signin" data-bs-toggle="modal" data-bs-target="#signin-modal">
                        {{ __('front.checkout.login') }}
                    </button>
                    {{ __('front.checkout.registered_users') }}
                </div>
            </div>
        @endif

        <div class="checkout-subsection">
        <h3 class="h6 d-flex align-items-center mb-3"><i class="fa-solid fa-user me-2 text-primary" aria-hidden="true"></i>{{ __('front.checkout.contact_details') }}</h3>
        <div class="row g-3">
            <div class="col-sm-6">
                <div>
                    <label class="form-label" for="checkout-fn">{{ __('front.checkout.first_name') }} <span class="text-danger">*</span></label>
                    <input class="form-control @error('address.fname') is-invalid @enderror" id="checkout-fn" type="text" autocomplete="given-name" wire:model.defer="address.fname">
                    @error('address.fname') <div class="invalid-feedback animated fadeIn">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-sm-6">
                <div>
                    <label class="form-label" for="checkout-ln">{{ __('front.checkout.last_name') }} <span class="text-danger">*</span></label>
                    <input class="form-control @error('address.lname') is-invalid @enderror" id="checkout-ln" type="text" autocomplete="family-name" wire:model.defer="address.lname">
                    @error('address.lname') <div class="invalid-feedback animated fadeIn">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-sm-6">
                <div>
                    <label class="form-label" for="checkout-email">{{ __('front.checkout.email_address') }} <span class="text-danger">*</span></label>
                    <input class="form-control @error('address.email') is-invalid @enderror" id="checkout-email" type="email" autocomplete="email" wire:model.defer="address.email">
                    @error('address.email') <div class="invalid-feedback animated fadeIn">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-sm-6">
                <div>
                    <label class="form-label" for="checkout-phone">{{ __('front.checkout.phone') }} <span class="text-danger">*</span></label>
                    <input class="form-control @error('address.phone') is-invalid  @enderror" id="checkout-phone" type="tel" inputmode="tel" autocomplete="tel" wire:model.defer="address.phone">
                    @error('address.phone') <div class="invalid-feedback animated fadeIn">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
        </div>

        <div class="checkout-subsection mt-4 pt-4 border-top">
        <h3 class="h6 d-flex align-items-center mb-1"><i class="fa-solid fa-location-dot me-2 text-primary" aria-hidden="true"></i>{{ __('front.checkout.delivery_address') }}</h3>
        <p class="text-muted fs-sm mb-3">{{ __('front.checkout.city_zip_help') }}</p>
        <div class="row g-3">
            <div class="col-sm-6">
                <div>
                    <label class="form-label" for="checkout-address">{{ __('front.checkout.address') }} <span class="text-danger">*</span></label>
                    <input class="form-control @error('address.address') is-invalid @enderror" id="checkout-address" type="text" autocomplete="address-line1" wire:model.defer="address.address">
                    @error('address.address') <div class="invalid-feedback animated fadeIn">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-sm-6">
                <div>
                    <label class="form-label" for="checkout-city">{{ __('front.checkout.city') }} <span class="text-danger">*</span></label>
                    <input class="form-control @error('address.city') is-invalid @enderror" id="checkout-city" type="text" autocomplete="address-level2" wire:model.debounce.300ms="address.city">
                    @error('address.city') <div class="invalid-feedback animated fadeIn">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-sm-6">
                <div>
                    <label class="form-label" for="checkout-zip">{{ __('front.checkout.zip') }} <span class="text-danger">*</span></label>
                    <input class="form-control @error('address.zip') is-invalid @enderror" id="checkout-zip" type="text" inputmode="numeric" autocomplete="postal-code" wire:model.debounce.300ms="address.zip">
                    @error('address.zip') <div class="invalid-feedback animated fadeIn">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-sm-6">
                <div>
                    <label class="form-label" for="checkout-country">{{ __('front.checkout.country') }} <span class="text-danger">*</span></label>
                    <select class="form-select @error('address.state') is-invalid @enderror" id="checkout-country" autocomplete="country-name" wire:model.defer="address.state" wire:change="stateSelected($event.target.value)">
                        @foreach ($countries as $country)
                            <option value="{{ $country['name'] }}">{{ $country['name'] }}</option>
                        @endforeach
                    </select>
                    @error('address.state') <div class="invalid-feedback animated fadeIn">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
        </div>

        @if (auth()->guest())
            <div class="checkout-option-card checkout-toggle-card mt-3">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="checkout-register-account" wire:model="register_account">
                    <label class="form-check-label fw-semibold" for="checkout-register-account">
                        <i class="fa-solid fa-user-plus me-2 text-primary" aria-hidden="true"></i>{{ __('front.checkout.register_during_checkout') }}
                    </label>
                    <div class="form-text ms-0">{{ __('front.checkout.register_during_checkout_help') }}</div>
                </div>

                @if ($register_account)
                    <div class="row gx-3 gy-2 checkout-toggle-content">
                        <div class="col-sm-6">
                            <label class="form-label" for="checkout-register-password">{{ __('front.checkout.password') }} <span class="text-danger">*</span></label>
                            <input class="form-control @error('registration.password') is-invalid @enderror" id="checkout-register-password" type="password" autocomplete="new-password" wire:model.defer="registration.password">
                            @error('registration.password') <div class="invalid-feedback animated fadeIn">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="checkout-register-password-confirmation">{{ __('front.checkout.password_confirmation') }} <span class="text-danger">*</span></label>
                            <input class="form-control @error('registration.password_confirmation') is-invalid @enderror" id="checkout-register-password-confirmation" type="password" autocomplete="new-password" wire:model.defer="registration.password_confirmation">
                            @error('registration.password_confirmation') <div class="invalid-feedback animated fadeIn">{{ $message }}</div> @enderror
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <div class="checkout-subsection checkout-separated mt-4 pt-4 border-top">
        <h3 class="h6 d-flex align-items-center mb-3"><i class="fa-solid fa-sliders me-2 text-primary" aria-hidden="true"></i>{{ __('front.checkout.optional_details') }}</h3>
        <div class="row g-3">
            <div class="col-12">
                <div class="checkout-option-card checkout-toggle-card">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="checkout-r1-invoice" wire:model="r1_invoice">
                        <label class="form-check-label fw-semibold" for="checkout-r1-invoice">
                            <i class="fa-solid fa-file-invoice me-2 text-primary" aria-hidden="true"></i>{{ __('front.checkout.need_r1') }}
                        </label>
                    </div>

                    @if ($r1_invoice)
                        <div class="row gx-3 gy-2 checkout-toggle-content">
                            <div class="col-sm-6">
                                <label class="form-label" for="checkout-company">{{ __('front.checkout.company') }}</label>
                                <input class="form-control" id="checkout-company" type="text" autocomplete="organization" wire:model.defer="address.company">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="checkout-oib">{{ __('front.checkout.oib') }}</label>
                                <input class="form-control" id="checkout-oib" type="text" inputmode="numeric" wire:model.defer="address.oib">
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-sm-6">
                <label class="form-label" for="checkout-birthday-day">{{ __('front.checkout.birthday') }}</label>
                <div class="checkout-date-parts @error('address.birthday_year') is-invalid @enderror">
                    <input class="checkout-date-part checkout-date-part-day"
                           id="checkout-birthday-day"
                           type="text"
                           inputmode="numeric"
                           maxlength="2"
                           placeholder="{{ __('front.checkout.birthday_day_placeholder') }}"
                           aria-label="{{ __('front.checkout.birthday') }} - {{ __('front.checkout.birthday_day_placeholder') }}"
                           autocomplete="bday-day"
                           wire:model.defer="birthday.day">
                    <span class="checkout-date-separator" aria-hidden="true">/</span>
                    <input class="checkout-date-part checkout-date-part-month"
                           type="text"
                           inputmode="numeric"
                           maxlength="2"
                           placeholder="{{ __('front.checkout.birthday_month_placeholder') }}"
                           aria-label="{{ __('front.checkout.birthday') }} - {{ __('front.checkout.birthday_month_placeholder') }}"
                           autocomplete="bday-month"
                           wire:model.defer="birthday.month">
                    <span class="checkout-date-separator" aria-hidden="true">/</span>
                    <input class="checkout-date-part checkout-date-part-year"
                           type="text"
                           inputmode="numeric"
                           maxlength="4"
                           placeholder="{{ __('front.checkout.birthday_year_placeholder') }}"
                           aria-label="{{ __('front.checkout.birthday') }} - {{ __('front.checkout.birthday_year_placeholder') }}"
                           autocomplete="bday-year"
                           wire:model.defer="birthday.year">
                </div>
                @error('address.birthday_year') <div class="invalid-feedback animated fadeIn">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="checkout-napomena">{{ __('front.checkout.comment') }}</label>
                <textarea class="form-control" id="checkout-napomena" rows="3" wire:model.defer="napomena"></textarea>
            </div>
            <div class="col-12">
                <div class="checkout-option-card checkout-toggle-card">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="checkout-newsletter" name="newsletter" wire:model.defer="newsletter">
                        <label class="form-check-label" for="checkout-newsletter"><i class="fa-solid fa-envelope-open-text me-2 text-primary" aria-hidden="true"></i>{{ __('front.checkout.newsletter') }}</label>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <div class="checkout-actions d-flex gap-3 pt-4 mt-3 border-top">
            <a class="btn btn-secondary flex-fill" href="{{ \App\Helpers\LocaleHelper::route('kosarica') }}"><i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i><span class="d-none d-sm-inline">{{ __('front.checkout.back_to_cart') }}</span><span class="d-inline d-sm-none">{{ __('front.checkout.back') }}</span></a>
            <button class="btn checkout-cta flex-fill" wire:click="changeStep('dostava')" type="button"><span class="d-none d-sm-inline">{{ __('front.checkout.choose_shipping') }}</span><span class="d-inline d-sm-none">{{ __('front.checkout.continue') }}</span><i class="fa-solid fa-arrow-right ms-2" aria-hidden="true"></i></button>
        </div>

    @endif


    @if ($step == 'dostava')
        <div class="checkout-section-heading d-flex align-items-center gap-3 pb-3 mb-4 border-bottom">
            <span class="checkout-heading-icon"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i></span>
            <div>
                <h2 class="h5 mb-1">{{ __('front.checkout.select_shipping') }}</h2>
                <p class="text-muted fs-sm mb-0">{{ __('front.checkout.shipping_intro') }}</p>
            </div>
        </div>
        <div class="table-responsive checkout-options-wrap">
            <table class="table checkout-options-table checkout-shipping-table fs-sm align-middle mb-0">
                <thead>
                <tr>
                    <th class="align-middle"></th>
                    <th class="align-middle">{{ __('front.checkout.shipping') }}</th>
                    <th class="align-middle">{{ __('front.checkout.delivery_time') }}</th>
                    <th class="align-middle">{{ __('front.checkout.price') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($shippingMethods as $s_method)
                    @php($shippingTitle = \App\Helpers\LocaleHelper::localizedSettingField($s_method, 'title'))
                    @php($shippingDescription = \App\Helpers\LocaleHelper::localizedSettingDataField($s_method, 'short_description'))
                    @php($shippingTime = \App\Helpers\LocaleHelper::localizedSettingDataField($s_method, 'time'))
                    @php($isWolt = $s_method->code === \App\Services\Shipping\WoltDriveService::CARRIER)
                    @php($woltUnavailable = $isWolt && $woltAvailable === false)
                    @php($shippingPrice = app(\App\Services\Shipping\ShippingRuleService::class)->priceFor($s_method, (float) $cartSubtotal, $isWolt ? $woltQuotePrice : null))
                    @php($shippingIcon = $s_method->code === 'pickup' ? 'fa-store' : ($isWolt ? 'fa-motorcycle' : (strpos($s_method->code, 'paketomat') !== false || strpos($s_method->code, 'box') !== false ? 'fa-box' : 'fa-truck-fast')))
                    <tr class="checkout-option-row {{ $shipping === $s_method->code ? 'is-selected' : '' }} {{ $woltUnavailable ? 'text-muted' : '' }}"
                        @unless($woltUnavailable) wire:click="selectShipping('{{ $s_method->code }}')" @endunless
                        style="cursor: {{ $woltUnavailable ? 'not-allowed' : 'pointer' }}; {{ $woltUnavailable ? 'opacity: .72;' : '' }}"
                        @if($woltUnavailable) aria-disabled="true" @endif>
                        <td class="checkout-option-icon-cell">
                            <input class="checkout-option-radio" id="shipping-{{ $s_method->code }}" type="radio" value="{{ $s_method->code }}" wire:model="shipping" aria-label="{{ $shippingTitle }}" @if($woltUnavailable) disabled @endif>
                            <span class="checkout-method-icon"><i class="fa-solid {{ $shippingIcon }}" aria-hidden="true"></i></span>
                        </td>
                        <td class="align-middle">
                            <label class="text-dark fw-semibold mb-1" for="shipping-{{ $s_method->code }}">{{ $shippingTitle }}</label>
                            @if($woltUnavailable)<span class="badge badge-secondary ml-1">{{ __('front.checkout.not_available') }}</span>@endif
                            <br><span class="text-muted checkout-option-description">{!! $shippingDescription !!}</span>
                            @if($woltUnavailable && $woltUnavailableReason)<br><span class="text-danger">{{ $woltUnavailableReason }}</span>@endif
                            @if($isWolt && $woltAvailable === true && $woltEtaMinutes)<br><span class="text-success">{{ __('front.checkout.wolt_eta', ['minutes' => $woltEtaMinutes]) }}</span>@endif
                            @if (trim(strip_tags((string) $shippingTime)) !== '')<span class="checkout-option-mobile-time">{{ $shippingTime }}</span>@endif
                        </td>
                        <td class="align-middle">{{ $shippingTime }}</td>
                        <td class="align-middle fw-semibold text-nowrap">
                            @if ($shippingPrice <= 0)
                                € 0
                                @if ($secondary_price)
                                    <br>0 kn
                                @endif
                            @else
                                € {{ number_format($shippingPrice, 2, ',', '.') }}
                                @if ($secondary_price)
                                    <br>{{ number_format($shippingPrice * $secondary_price, 2, ',', '.') }} kn
                                @endif
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @foreach ($shippingMethods as $s_method)
            @if ($s_method->code == 'gls_eu' && $view_comment)
                <div class="alert alert-info d-flex align-items-center mt-4 mb-3" role="status">
                    <i class="fa-solid fa-map-location-dot fa-lg me-3" aria-hidden="true"></i>
                    <span>{{ __('front.checkout.gls_select_help') }}</span>
                </div>
                <div class="rounded-3 overflow-hidden border" style="height: 600px">
                    <gls-dpm country="hr" id="test-map" filter-type="parcel-locker"></gls-dpm>
                </div>
                <input class="form-control mt-2" type="text" id="comment"  wire:model="comment" placeholder="" readonly required>
                @error('comment') <small class="text-danger">{{ __('front.checkout.gls_required') }}</small>
                @enderror
            @endif

            @if ($s_method->code == 'boxnow' && $view_boxnow)
                <div class="alert alert-info d-flex align-items-center mt-4 mb-3" role="status">
                    <i class="fa-solid fa-box fa-lg me-3" aria-hidden="true"></i>
                    <span>{{ __('front.checkout.boxnow_select_help') }}</span>
                </div>
                <button type="button" class="boxnow-map-widget-button btn btn-primary mb-3">
                    <i class="fa-solid fa-map-location-dot me-2" aria-hidden="true"></i>{{ __('front.checkout.boxnow_select_button') }}
                </button>
                <div id="boxnowmap"></div>
                <input class="form-control" type="text" id="comment" wire:model="comment" placeholder="Odabrani Box Now paketomat" readonly required>
                @error('comment') <small class="text-danger">{{ __('front.checkout.boxnow_required') }}</small> @enderror
            @endif
        @endforeach
        @error('shipping') <div class="alert alert-danger mt-3 mb-0"><i class="fa-solid fa-circle-exclamation me-2" aria-hidden="true"></i>{{ __('front.checkout.shipping_required') }}</div> @enderror
        <div class="checkout-actions d-flex gap-3 pt-4 mt-4 border-top">
            <button class="btn btn-secondary flex-fill" wire:click="changeStep('podaci')" type="button"><i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i><span class="d-none d-sm-inline">{{ __('front.checkout.back_to_details') }}</span><span class="d-inline d-sm-none">{{ __('front.checkout.back') }}</span></button>
            <button class="btn checkout-cta flex-fill" wire:click="changeStep('placanje')" type="button"><span class="d-none d-sm-inline">{{ __('front.checkout.choose_payment') }}</span><span class="d-inline d-sm-none">{{ __('front.checkout.continue') }}</span><i class="fa-solid fa-arrow-right ms-2" aria-hidden="true"></i></button>
        </div>
    @endif


    @if ($step == 'placanje')
        <div class="checkout-section-heading d-flex align-items-center gap-3 pb-3 mb-4 border-bottom">
            <span class="checkout-heading-icon"><i class="fa-solid fa-credit-card" aria-hidden="true"></i></span>
            <div>
                <h2 class="h5 mb-1">{{ __('front.checkout.select_payment') }}</h2>
                <p class="text-muted fs-sm mb-0">{{ __('front.checkout.payment_intro') }}</p>
            </div>
        </div>
        <div class="table-responsive checkout-options-wrap">
            <table class="table checkout-options-table checkout-payment-table fs-sm align-middle mb-0">
                <tbody>
                @foreach ($paymentMethods as $p_method)
                    @php($paymentTitle = \App\Helpers\LocaleHelper::localizedSettingField($p_method, 'title'))
                    @php($paymentDescription = \App\Helpers\LocaleHelper::localizedSettingDataField($p_method, 'short_description'))
                    @php($paymentIcon = in_array($p_method->code, ['bank'], true) ? 'fa-building-columns' : (in_array($p_method->code, ['cod', 'pickup'], true) ? 'fa-money-bill-wave' : ($p_method->code === 'corvus_wallets' ? 'fa-wallet' : (strpos($p_method->code, 'keks') !== false ? 'fa-mobile-screen-button' : 'fa-credit-card'))))
                    <tr class="checkout-option-row {{ $payment === $p_method->code ? 'is-selected' : '' }}" wire:click="selectPayment('{{ $p_method->code }}')" style="cursor: pointer;">
                        <td class="checkout-option-icon-cell">
                            <input class="checkout-option-radio" id="payment-{{ $p_method->code }}" type="radio" value="{{ $p_method->code }}" wire:model="payment" aria-label="{{ $paymentTitle }}">
                            <span class="checkout-method-icon"><i class="fa-solid {{ $paymentIcon }}" aria-hidden="true"></i></span>
                        </td>
                        <td class="align-middle py-3"><label class="text-dark fw-semibold mb-1" for="payment-{{ $p_method->code }}">{{ $paymentTitle }}</label><br><span class="text-muted">{{ $paymentDescription }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @error('payment') <div class="alert alert-danger mt-3 mb-0"><i class="fa-solid fa-circle-exclamation me-2" aria-hidden="true"></i>{{ __('front.checkout.payment_required') }}</div> @enderror
        <div class="checkout-actions d-flex gap-3 pt-4 mt-4 border-top">
            <button class="btn btn-secondary flex-fill" wire:click="changeStep('dostava')" type="button"><i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i><span class="d-none d-sm-inline">{{ __('front.checkout.back_to_shipping') }}</span><span class="d-inline d-sm-none">{{ __('front.checkout.back') }}</span></button>
            <a class="btn checkout-cta flex-fill {{ $payment === '' ? 'disabled' : '' }}" href="{{ ($payment != '') ? \App\Helpers\LocaleHelper::route('pregled') : '#' }}" @if($payment === '') aria-disabled="true" tabindex="-1" @endif><span class="d-none d-sm-inline">{{ __('front.checkout.review_order') }}</span><span class="d-inline d-sm-none">{{ __('front.checkout.continue') }}</span><i class="fa-solid fa-arrow-right ms-2" aria-hidden="true"></i></a>
        </div>
    @endif

    </div>
</div>


@push('js_after')
{{--    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('js/plugins/select2/css/select2.min.css') }}">--}}
{{--    <script src="{{ \App\Helpers\Asset::url('js/plugins/select2/js/select2.full.min.js') }}"></script>--}}


<script>
    function initGlsParcelLockerMap() {
        const mapElement = document.getElementById('test-map');
        const commentInput = document.getElementById('comment');

        if (!mapElement || !commentInput || mapElement.dataset.listenerAttached === 'true') {
            return;
        }

        mapElement.dataset.listenerAttached = 'true';

        mapElement.addEventListener('change', (event) => {
            const detail = event.detail || {};
            const contact = detail.contact || {};

            commentInput.value = `${contact.address || ''}, ${contact.city || ''}_${detail.id || ''}`;
            commentInput.dispatchEvent(new Event('input', { bubbles: true }));
            notifyCheckoutPickupSelected(@json(__('front.checkout.gls_selected')));
        });
    }

    function notifyCheckoutPickupSelected(message) {
        window.dispatchEvent(new CustomEvent('checkout-option-saved', {
            detail: {
                message: message,
                duration: 3500,
            },
        }));
    }

    function updateCheckoutPickupComment(value) {
        const commentInput = document.getElementById('comment');

        if (!commentInput) {
            return;
        }

        commentInput.value = value;
        commentInput.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function initBoxNowMap() {
        const boxNowContainer = document.getElementById('boxnowmap');

        if (!boxNowContainer) {
            return;
        }

        window._bn_map_widget_config = {
            type: 'popup',
            partnerId: @json((int) $boxnow_widget_partner_id),
            parentElement: '#boxnowmap',
            afterSelect: function (selected) {
                if (!selected || !selected.boxnowLockerId) {
                    return;
                }

                const postalCode = selected.boxnowLockerPostalCode || '';
                const address = selected.boxnowLockerAddressLine1 || selected.boxnowLockerName || '';
                updateCheckoutPickupComment(`${postalCode}, ${address}_${selected.boxnowLockerId}`);
                notifyCheckoutPickupSelected(@json(__('front.checkout.boxnow_selected')));
            }
        };

        if (document.querySelector('script[data-boxnow-widget="1"]')) {
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://widget-cdn.boxnow.hr/map-widget/client/v5.js';
        script.async = true;
        script.defer = true;
        script.dataset.boxnowWidget = '1';
        document.head.appendChild(script);
    }

    function focusFirstCheckoutError() {
        const checkout = document.getElementById('checkout-flow');
        const firstInvalidField = checkout ? checkout.querySelector('.is-invalid, [aria-invalid="true"], .alert-danger') : null;

        if (!firstInvalidField) {
            return;
        }

        firstInvalidField.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });

        if (typeof firstInvalidField.focus === 'function') {
            firstInvalidField.focus({ preventScroll: true });
        }
    }

    function scrollCheckoutToTop() {
        window.setTimeout(() => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth',
            });
        }, 0);
    }

    document.addEventListener('DOMContentLoaded', () => {
        initGlsParcelLockerMap();
        initBoxNowMap();
    });
    window.addEventListener('checkout-validation-failed', focusFirstCheckoutError);
    window.addEventListener('checkout-step-changed', scrollCheckoutToTop);

    document.addEventListener('livewire:load', () => {
        initGlsParcelLockerMap();
        initBoxNowMap();

        if (window.Livewire && typeof window.Livewire.hook === 'function') {
            window.Livewire.hook('message.processed', () => {
                initGlsParcelLockerMap();
                initBoxNowMap();
            });
        }
    });
</script>

<!--
Javascript to initialize the custom element, it can be placed anywhere.
-->
<script type="module" src="https://map.gls-croatia.com/widget/gls-dpm.js"></script>

@endpush
