
@extends('front.layouts.app')

@section('content')


    <div class="container pb-5 mb-sm-4">
        <div class="pt-5">
            <div class="card py-3 mt-sm-3">
                <div class="card-body text-center">
                    <h2 class="h4 pb-3 text-danger">{{ __('front.checkout.error_title') }}</h2>
                    <p class="fs-sm mb-2">...</p>

                    <p class="fs-sm">{{ __('front.checkout.email_confirmation') }}</p>

                    <a class="btn btn-secondary mt-3 me-3" href="{{ \App\Helpers\LocaleHelper::route('index') }}">{{ __('front.checkout.continue_browsing') }}</a>

                </div>
            </div>
        </div>
    </div>

    <section class="container-fluid pt-grid-gutter bg-third">
        <div class="container">
            <div class="row">
                <div class="col-xl-3 col-sm-6 mb-grid-gutter"><a class="card h-100" href="#map" data-scroll="">
                        <div class="card-body text-center"><i class="ci-location h3 mt-2 mb-4 text-primary"></i>
                            <h3 class="h6 mb-2">{{ __('front.general.address') }}</h3>
                            <p class="fs-sm text-muted">{{ __('front.general.address_value') }}</p>
                            <div class="fs-sm text-primary">{{ __('front.general.click_for_map') }}<i class="ci-arrow-right align-middle ms-1"></i></div>
                        </div></a></div>
                <div class="col-xl-3 col-sm-6 mb-grid-gutter">
                    <div class="card h-100">
                        <div class="card-body text-center"><i class="ci-time h3 mt-2 mb-4 text-primary"></i>
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
                        <div class="card-body text-center"><i class="ci-phone h3 mt-2 mb-4 text-primary"></i>
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
                        <div class="card-body text-center"><i class="ci-mail h3 mt-2 mb-4 text-primary"></i>
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
