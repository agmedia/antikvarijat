@extends('front.layouts.app')
@section('title', __('front.contact.meta_title'))
@section('description', __('front.contact.meta_description'))
@section('schema_page_type', 'ContactPage')

@section('content')

    <!-- Page Title (Light)-->
    <div class="bg-secondary py-4" style="background-image: url({{ asset('media/img/farmer.png')  }});background-repeat: repeat">
        <div class="container d-lg-flex justify-content-between py-2 py-lg-3">
            <div class="order-lg-2 mb-3 mb-lg-0 pt-lg-2">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb flex-lg-nowrap justify-content-center justify-content-lg-start">
                        <li class="breadcrumb-item"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}"><i class="fa-solid fa-house"></i>{{ __('front.nav.home') }}</a></li>
                        <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ __('front.contact.breadcrumb') }}</li>
                    </ol>
                </nav>
            </div>
            <div class="order-lg-1 pe-lg-4 text-center text-lg-start">
                <h1 class="h3 mb-0">{{ __('front.contact.title') }}</h1>
            </div>
        </div>
    </div>
    <!-- Contact detail cards-->
    <section class="container pt-grid-gutter">
        <div class="row">

            @include('front.layouts.partials.success-session')

            <div class="col-xl-3 col-sm-6 mb-grid-gutter"><a class="card h-100" href="#map" data-scroll="">
                    <div class="card-body text-center"><i class="fa-solid fa-location-dot h3 mt-2 mb-4 text-primary"></i>
                        <h3 class="h6 mb-2">{{ __('front.general.address') }}</h3>
                        <p class="fs-sm text-muted">{!! str_replace(', ', ', <br>', __('front.general.address_value_long')) !!}</p>
                    </div>
                </a>
            </div>
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
    </section>

    <!-- Split section: Map + Contact form-->
    <div class="container-fluid px-0" id="map">
        <div class="row g-0">
            <div class="col-lg-6 iframe-full-height-wrap">
                <iframe class="iframe-full-height" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2781.031954941899!2d15.979511851748862!3d45.810618418178656!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x4765d7aac4f8b023%3A0xb60bceb791b31ede!2sAntikvarijat%20Biblos!5e0!3m2!1sen!2sua!4v1629710903017!5m2!1sen!2sua" width="600" height="350" style="border:0;" title="{{ __('front.contact.map_title') }}" allowfullscreen="" loading="lazy"></iframe>
            </div>
            <div class="col-lg-6 px-4 px-xl-5 py-5 border-top">
                <h2 class="h4 mb-4">{{ __('front.contact.form_title') }}</h2>
                <form action="{{ \App\Helpers\LocaleHelper::route('poruka') }}" method="POST" class="mb-3">
                    @csrf
                    <div class="row g-3">
                        <div class="col-sm-12">
                            <label class="form-label" for="cf-name">{{ __('front.contact.name') }}:&nbsp;@include('back.layouts.partials.required-star')</label>
                            <input class="form-control" type="text" name="name" id="cf-name" placeholder="">
                            @error('name')<div class="text-danger font-size-sm">{{ __('front.contact.name_required') }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="cf-email">{{ __('front.general.email_address') }}:&nbsp;@include('back.layouts.partials.required-star')</label>
                            <input class="form-control" type="email" id="cf-email" placeholder="" name="email">
                            @error('email')<div class="invalid-feedback">{{ __('front.contact.email_required') }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="cf-phone">{{ __('front.contact.phone') }}:&nbsp;@include('back.layouts.partials.required-star')</label>
                            <input class="form-control" type="text" id="cf-phone" placeholder="" name="phone">
                            @error('phone')<div class="invalid-feedback">{{ __('front.contact.phone_required') }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="cf-message">{{ __('front.contact.message') }}:&nbsp;@include('back.layouts.partials.required-star')</label>
                            <textarea class="form-control" id="cf-message" rows="6" placeholder="" name="message"></textarea>
                            @error('message')<div class="invalid-feedback">{{ __('front.contact.message_required') }}</div>@enderror
                            <button class="btn btn-primary mt-4" type="submit">{{ __('front.contact.submit') }}</button>
                        </div>
                    </div>
                    <input type="hidden" name="recaptcha" id="recaptcha">
                </form>
            </div>
        </div>
    </div>

@endsection

@push('js_after')
    @include('front.layouts.partials.recaptcha-js')
@endpush
