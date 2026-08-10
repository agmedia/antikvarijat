
@extends('front.layouts.app')

@section('content')

    @if (isset($data['google_tag_manager']))
        @section('google_data_layer')
            <script>
                window.dataLayer = window.dataLayer || [];
                dataLayer.push(<?php echo json_encode($data['google_tag_manager']); ?>);
            </script>
        @endsection
    @endif

    <div class="container pb-5 mb-sm-4">
        <div class="pt-5">
            <div class="card py-3 mt-sm-3">
                <div class="card-body text-center">
                    <i class="fa-solid fa-circle-check display-4 text-success mb-3" aria-hidden="true"></i>
                    <h2 class="h4 pb-3">{{ __('front.checkout.success_title') }}</h2>

                    @if($data['order']['payment_code'] == 'bank')
                        <p>{{ __('front.checkout.bank_received', ['order_id' => $data['order']['id']]) }}</p><p>{{ __('front.checkout.bank_instructions') }}</p>
                        <p>{{ __('front.checkout.bank_deadline') }}</p>
                        <p>{{ __('front.checkout.bank_cancel') }}</p>
                        <p>{{ __('front.checkout.bank_pay_amount', ['amount' => number_format($data['order']['total'], 2)]) }}<br>
                           {{ __('front.checkout.bank_iban') }}: HR3123600001101595832<br>
                           {{ __('front.checkout.bank_model') }}: {{ $data['order']['id'] }}-{{date('ym')}}</p>
                        <p>{{ __('front.checkout.bank_scan') }}</p>
                        <p><img src="{{ asset('media/img/qr/'.$data['order']['id']) }}.jpg"></p>
                    @else
                        <p class="fs-sm mb-2">{{ __('front.checkout.order_sent') }}</p>
                        <p class="fs-sm">{{ __('front.checkout.email_confirmation') }}</p>
                    @endif

                    <a class="btn btn-secondary mt-3" href="{{ \App\Helpers\LocaleHelper::route('index') }}"><i class="fa-solid fa-book-open me-2" aria-hidden="true"></i>{{ __('front.checkout.continue_browsing') }}</a>
                </div>
            </div>
        </div>
    </div>

    <section class="container-fluid pt-grid-gutter bg-third">
        <div class="container">
            <div class="row">
                <div class="col-xl-3 col-sm-6 mb-grid-gutter"><a class="card h-100" href="#map" data-scroll="">
                        <div class="card-body text-center"><i class="fa-solid fa-location-dot h3 mt-2 mb-4 text-primary"></i>
                            <h3 class="h6 mb-2">{{ __('front.general.address') }}</h3>
                            <p class="fs-sm text-muted">{{ __('front.general.address_value') }}</p>
                            <div class="fs-sm text-primary">{{ __('front.general.click_for_map') }}<i class="fa-solid fa-arrow-right align-middle ms-1"></i></div>
                        </div></a></div>
                <div class="col-xl-3 col-sm-6 mb-grid-gutter">
                    <div class="card h-100">
                        <div class="card-body text-center"><i class="fa-solid fa-clock h3 mt-2 mb-4 text-primary"></i>
                            <h3 class="h6 mb-3">{{ __('front.general.opening_hours') }}</h3>
                            <ul class="list-unstyled fs-sm text-muted mb-0">
                                <li>{{ __('front.general.opening_hours_weekdays') }}</li>
                                <li class="mb-0">{{ __('front.general.opening_hours_saturday') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6  mb-grid-gutter">
                    <div class="card h-100">
                        <div class="card-body text-center"><i class="fa-solid fa-phone h3 mt-2 mb-4 text-primary"></i>
                            <h3 class="h6 mb-3">{{ __('front.general.phones') }}</h3>
                            <ul class="list-unstyled fs-sm mb-0">
                                <li><a class="nav-link-style text-primary" href="tel:+38514816574"> +385 1 48 16 574</a></li>
                                <li><a class="nav-link-style text-primary" href="tel:++385981629674"> +385 98 16 29 674</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 mb-grid-gutter">
                    <div class="card h-100">
                        <div class="card-body text-center"><i class="fa-solid fa-envelope h3 mt-2 mb-4 text-primary"></i>
                            <h3 class="h6 mb-3">{{ __('front.general.email_address') }}</h3>
                            <ul class="list-unstyled fs-sm mb-0">
                                <li><a class="nav-link-style text-primary" href="mailto:info@antikvarijat-biblos.hr">info@antikvarijat-biblos.hr</a></li>

                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
