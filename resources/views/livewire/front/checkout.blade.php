<div>
    <div class="steps steps-dark pt-2 pb-3 mb-5">
        <a class="step-item active" href="{{ \App\Helpers\LocaleHelper::route('kosarica') }}">
            <div class="step-progress"><span class="step-count">1</span></div>
            <div class="step-label"><i class="fa-regular fa-bag-shopping"></i>{{ __('front.checkout.cart') }}</div>
        </a>
        <a class="step-item @if($step == 'podaci') current @endif @if(in_array($step, ['podaci', 'dostava', 'placanje'])) active @endif" wire:click="changeStep('podaci')" href="javascript:void(0);">
            <div class="step-progress"><span class="step-count">2</span></div>
            <div class="step-label"><i class="fa-duotone fa-circle-user"></i>{{ __('front.checkout.details') }}</div>
        </a>
        <a class="step-item @if($step == 'dostava') current @endif @if(in_array($step, ['dostava', 'placanje'])) active @endif" wire:click="changeStep('dostava')" href="javascript:void(0);">
            <div class="step-progress"><span class="step-count">3</span></div>
            <div class="step-label"><i class="fa-duotone fa-box"></i>{{ __('front.checkout.shipping') }}</div>
        </a>
        <a class="step-item @if($step == 'placanje') current @endif @if(in_array($step, ['placanje'])) active @endif" wire:click="changeStep('placanje')" href="javascript:void(0);">
            <div class="step-progress"><span class="step-count">4</span></div>
            <div class="step-label"><i class="fa-duotone fa-credit-card"></i>{{ __('front.checkout.payment') }}</div>
        </a>
        <a class="step-item" href="{{ ($payment != '') ? \App\Helpers\LocaleHelper::route('pregled') : '#' }}">
            <div class="step-progress"><span class="step-count">5</span></div>
            <div class="step-label"><i class="fa-solid fa-eye"></i>{{ __('front.checkout.review') }}</div>
        </a>

        <a class="step-item" href="#">
            <div class="step-progress"><span class="step-count">6</span></div>
            <div class="step-label"><i class="fa-solid fa-circle-check"></i>{{ __('front.checkout.success') }}</div>
        </a>
    </div>

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
    <div class="bg-white rounded-3 shadow-lg p-4">
    @if ($step == 'podaci')
        <h2 class="h6 pt-1 pb-3 mb-3 border-bottom">{{ __('front.checkout.billing_address') }}</h2>

        @if (session()->has('login_success'))
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                {{ session('login_success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (auth()->guest())
            <div class="alert alert-secondary d-flex mb-3" role="alert">
                <div class="alert-icon">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div><a data-bs-toggle="collapse" href="#collapseLogin" role="button" aria-expanded="false" aria-controls="collapseLogin" class="alert-link">{{ __('front.checkout.login') }} </a> {{ __('front.checkout.registered_users') }}</div>
            </div>

            @if (session()->has('error'))
                <div class="alert alert-primary alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div id="collapseLogin" aria-expanded="false" class="collapse">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="mb-3">
                                    <label class="form-label" for="si-email">{{ __('front.checkout.email_address') }}</label>
                                    <input class="form-control" type="email" wire:model.defer="login.email" placeholder="" required>
                                    <div class="invalid-feedback">{{ __('validation.email', ['attribute' => __('front.checkout.email_address')]) }}</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-3">
                                    <label class="form-label" for="si-password">{{ __('front.checkout.password') }}</label>
                                    <div class="password-toggle">
                                        <input class="form-control" type="password" wire:model.defer="login.pass" required>
                                        <label class="password-toggle-btn" aria-label="Show/hide password">
                                            <input class="password-toggle-check" type="checkbox"><span class="password-toggle-indicator"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="mb-3 d-flex flex-wrap justify-content-between">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" wire:model.defer="login.remember" id="si-remember">
                                        <label class="form-check-label" for="si-remember">{{ __('front.checkout.remember_me') }}</label>
                                    </div>
                                    <a class="fs-sm" href="{{ route('register') }}">{{ __('front.checkout.register') }}</a>
                                </div>
                                <button class="btn btn-primary btn-shadow d-block w-100" wire:click="authUser()" type="button">{{ __('front.checkout.login') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="row">
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label" for="checkout-fn">{{ __('front.checkout.first_name') }} <span class="text-danger">*</span></label>
                    <input class="form-control @error('address.fname') is-invalid @enderror" type="text" wire:model.defer="address.fname">
                    @error('address.fname') <div class="invalid-feedback animated fadeIn">{{ __('validation.required', ['attribute' => __('front.checkout.first_name')]) }}</div> @enderror
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label" for="checkout-ln">{{ __('front.checkout.last_name') }} <span class="text-danger">*</span></label>
                    <input class="form-control @error('address.lname') is-invalid @enderror" type="text" wire:model.defer="address.lname">
                    @error('address.lname') <div class="invalid-feedback animated fadeIn">{{ __('validation.required', ['attribute' => __('front.checkout.last_name')]) }}</div> @enderror
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label" for="checkout-email">{{ __('front.checkout.email_address') }} <span class="text-danger">*</span></label>
                    <input class="form-control @error('address.email') is-invalid @enderror" type="email" wire:model.defer="address.email">
                    @error('address.email') <div class="invalid-feedback animated fadeIn">{{ __('validation.required', ['attribute' => __('front.checkout.email_address')]) }}</div> @enderror
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label" for="checkout-phone">{{ __('front.checkout.phone') }} <span class="text-danger">*</span></label>
                    <input class="form-control @error('address.phone') is-invalid  @enderror" type="text" wire:model.defer="address.phone">
                    @error('address.phone') <div class="invalid-feedback animated fadeIn">{{ __('validation.required', ['attribute' => __('front.checkout.phone')]) }}</div> @enderror
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label" for="checkout-birthday-year">{{ __('front.checkout.birthday') }}</label>
                    <input class="form-control @error('address.birthday_year') is-invalid @enderror"
                           id="checkout-birthday-year"
                           type="date"
                           wire:model.defer="address.birthday_year">
                    @error('address.birthday_year') <div class="invalid-feedback animated fadeIn">{{ __('validation.date', ['attribute' => __('front.checkout.birthday')]) }}</div> @enderror
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label" for="checkout-address">{{ __('front.checkout.address') }} <span class="text-danger">*</span></label>
                    <input class="form-control @error('address.address') is-invalid @enderror" type="text" wire:model.defer="address.address">
                    @error('address.address') <div class="invalid-feedback animated fadeIn">{{ __('validation.required', ['attribute' => __('front.checkout.address')]) }}</div> @enderror
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label" for="checkout-city">{{ __('front.checkout.city') }} <span class="text-danger">*</span></label>
                    <input class="form-control @error('address.city') is-invalid @enderror" type="text" wire:model.defer="address.city">
                    @error('address.city') <div class="invalid-feedback animated fadeIn">{{ __('validation.required', ['attribute' => __('front.checkout.city')]) }}</div> @enderror
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label" for="checkout-zip">{{ __('front.checkout.zip') }} <span class="text-danger">*</span></label>
                    <input class="form-control @error('address.zip') is-invalid @enderror" type="text" wire:model.defer="address.zip">
                    @error('address.zip') <div class="invalid-feedback animated fadeIn">{{ __('validation.required', ['attribute' => __('front.checkout.zip')]) }}</div> @enderror
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label" for="checkout-country">{{ __('front.checkout.country') }} <span class="text-danger">*</span></label>
                    <select class="form-select @error('address.state') is-invalid @enderror" id="state-select" wire:model="address.state" wire:change="stateSelected($event.target.value)">
<!--                        <option value=""></option>-->
                        @foreach ($countries as $country)
                            <option value="{{ $country['name'] }}">{{ $country['name'] }}</option>
                        @endforeach
                    </select>
                    @error('address.state') <div class="invalid-feedback animated fadeIn">{{ __('validation.required', ['attribute' => __('front.checkout.country')]) }}</div> @enderror
                </div>
            </div>
        </div>

            <div class="row mt-2 mb-3">
                <div class="col-sm-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="checkout-newsletter" name="newsletter" wire:model.defer="newsletter">
                        <label class="form-check-label" for="checkout-newsletter">
                            {{ __('front.checkout.newsletter') }}
                        </label>
                    </div>
                </div>
            </div>

            <h2 class="h6 pt-1 pb-3 mb-3 border-bottom">{{ __('front.checkout.additional_note') }}</h2>
            <div class="row mt-3">
                <div class="col-sm-12">
                    <div class="mb-3">
                        <label class="form-label" for="checkout-napomena">{{ __('front.checkout.comment') }}</label>
                        <textarea class="form-control" id="checkout-napomena" rows="3" wire:model.defer="napomena"></textarea>
                    </div>
                </div>
            </div>

        <h2 class="h6 pt-1 pb-3 mb-3 border-bottom">{{ __('front.checkout.need_r1') }}</h2>
        <div class="row mt-3">
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label" for="checkout-company">{{ __('front.checkout.company') }}</label>
                    <input class="form-control" type="text" wire:model.defer="address.company">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <div class="mb-3">
                        <label class="form-label" for="checkout-oib">{{ __('front.checkout.oib') }}</label>
                        <input class="form-control" type="text" wire:model.defer="address.oib">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex pt-4 mt-3">
            <div class="w-50 pe-3"><a class="btn btn-secondary d-block w-100" href="{{ \App\Helpers\LocaleHelper::route('kosarica') }}"><i class="fa-solid fa-arrow-left mt-sm-0 me-1"></i><span class="d-none d-sm-inline">{{ __('front.checkout.back_to_cart') }}</span><span class="d-inline d-sm-none">{{ __('front.checkout.back') }}</span></a></div>
            <div class="w-50 ps-2"><button class="btn btn-primary d-block w-100" wire:click="changeStep('dostava')" type="button"><span class="d-none d-sm-inline">{{ __('front.checkout.choose_shipping') }}</span><span class="d-inline d-sm-none">{{ __('front.checkout.continue') }}</span><i class="fa-solid fa-arrow-right mt-sm-0 ms-1"></i></button></div>
        </div>

    @endif


    @if ($step == 'dostava')
        <h2 class="h6 pt-1 pb-3 mb-3 ">{{ __('front.checkout.select_shipping') }}</h2>
        <div class="table-responsive">
            <table class="table table-hover fs-sm border-top">
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
                    <tr wire:click="selectShipping('{{ $s_method->code }}')" style="cursor: pointer;">
                        <td>
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="radio" value="{{ $s_method->code }}" wire:model="shipping">
                                <label class="form-check-label" for="courier"></label>
                            </div>
                        </td>
                        <td class="align-middle"><span class="text-dark fw-medium">{{ $shippingTitle }}</span><br><span class="text-muted">{!! $shippingDescription !!}</span></td>
                        <td class="align-middle">{{ $shippingTime }}</td>
                        <td class="align-middle">
                            @if ($is_free_shipping)
                                € 0
                                @if ($secondary_price)
                                    <br>0 kn
                                @endif
                            @else
                                € {{ $s_method->data->price }}
                                @if ($secondary_price)
                                    <br>{{ $s_method->data->price ? number_format($s_method->data->price * $secondary_price, 2) : '0' }} kn
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

                <div style="height: 600px">
                    <gls-dpm country="hr" id="test-map" filter-type="parcel-locker"></gls-dpm>
                </div>


                <input class="form-control mt-2" type="text" id="comment"  wire:model="comment" placeholder="" readonly required>

                @error('comment')             <script>location.reload();</script>         @enderror
                @error('comment') <small class="text-danger">{{ __('front.checkout.gls_required') }}</small>

                @enderror


            @endif



        @endforeach
        @error('shipping') <small class="text-danger">{{ __('front.checkout.shipping_required') }}</small> @enderror
        <div class=" d-flex pt-4 mt-3">
            <div class="w-50 pe-3"><button class="btn btn-secondary d-block w-100" wire:click="changeStep('podaci')" type="button"><i class="fa-solid fa-arrow-left mt-sm-0 me-1"></i><span class="d-none d-sm-inline">{{ __('front.checkout.back_to_details') }}</span><span class="d-inline d-sm-none">{{ __('front.checkout.back') }}</span></button></div>
            <div class="w-50 ps-2"><button class="btn btn-primary d-block w-100" wire:click="changeStep('placanje')" type="button"><span class="d-none d-sm-inline">{{ __('front.checkout.choose_payment') }}</span><span class="d-inline d-sm-none">{{ __('front.checkout.continue') }}</span><i class="fa-solid fa-arrow-right mt-sm-0 ms-1"></i></button></div>
        </div>
    @endif


    @if ($step == 'placanje')
        <h2 class="h6 pt-1 pb-3 mb-3 ">{{ __('front.checkout.select_payment') }}</h2>
        <div class="table-responsive">
            <table class="table table-hover fs-sm border-top">
                <tbody>
                @foreach ($paymentMethods as $p_method)
                    @php($paymentTitle = \App\Helpers\LocaleHelper::localizedSettingField($p_method, 'title'))
                    @php($paymentDescription = \App\Helpers\LocaleHelper::localizedSettingDataField($p_method, 'short_description'))
                    <tr wire:click="selectPayment('{{ $p_method->code }}')" style="cursor: pointer;">
                        <td>
                            <div class="form-check mb-2  ">
                                <input class="form-check-input" type="radio" value="{{ $p_method->code }}" wire:model="payment">
                                <label class="form-check-label" for="courier"></label>
                            </div>
                        </td>
                        <td class="align-middle"><span class="text-dark fw-medium">{{ $paymentTitle }}</span><br><span class="text-muted">{{ $paymentDescription }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @error('payment') <small class="text-danger">{{ __('front.checkout.payment_required') }}</small> @enderror
        <div class=" d-flex pt-4 mt-3">
            <div class="w-50 pe-3"><button class="btn btn-secondary d-block w-100" wire:click="changeStep('dostava')" type="button"><i class="fa-solid fa-arrow-left mt-sm-0 me-1"></i><span class="d-none d-sm-inline">{{ __('front.checkout.back_to_shipping') }}</span><span class="d-inline d-sm-none">{{ __('front.checkout.back') }}</span></button></div>
            <div class="w-50 ps-2"><a class="btn btn-primary d-block w-100" href="{{ ($payment != '') ? \App\Helpers\LocaleHelper::route('pregled') : '#' }}"><span class="d-none d-sm-inline">{{ __('front.checkout.review_order') }}</span><span class="d-inline d-sm-none">{{ __('front.checkout.continue') }}</span><i class="fa-solid fa-arrow-right mt-sm-0 ms-1"></i></a></div>
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
            commentInput.dispatchEvent(new Event('input'));
        });
    }

    function focusFirstCheckoutError() {
        const firstInvalidField = document.querySelector('.is-invalid, .invalid-feedback, small.text-danger');

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

    document.addEventListener('DOMContentLoaded', initGlsParcelLockerMap);
    window.addEventListener('checkout-validation-failed', focusFirstCheckoutError);

    document.addEventListener('livewire:load', () => {
        initGlsParcelLockerMap();

        if (window.Livewire && typeof window.Livewire.hook === 'function') {
            window.Livewire.hook('message.processed', () => {
                initGlsParcelLockerMap();
            });
        }
    });
</script>

<!--
Javascript to initialize the custom element, it can be placed anywhere.
-->
<script type="module" src="https://map.gls-croatia.com/widget/gls-dpm.js"></script>

<script>
    $( document ).ready(function() {
        /*$('#state-select').select2();*/
        $('input').attr('autocomplete','off');
    });
</script>


@endpush
