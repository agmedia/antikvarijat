@extends('front.layouts.app')

@section('title', __('front.not_found.meta_title'))
@section('description', __('front.not_found.meta_description'))
@section('robots', 'noindex,nofollow,noarchive')

@section('content')
    <div class="container py-5 mb-lg-3">
        <div class="row justify-content-center pt-lg-4 text-center">
            <div class="col-lg-5 col-md-7 col-sm-9">
                <h1 class="display-404 py-lg-3">404</h1>
                <h2 class="h3 mb-4">{{ __('front.not_found.title') }}</h2>
                <p class="fs-md mb-4">
                    <u>{{ __('front.not_found.links_intro') }}</u>
                </p>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-10">
                <div class="row">
                    <div class="col-sm-4 mb-3">
                        <a class="card h-100 border-0 shadow-sm" href="{{ \App\Helpers\LocaleHelper::route('index') }}">
                            <div class="card-body">
                                <div class="d-flex align-items-center"><i class="fa-solid fa-house text-primary h4 mb-0" aria-hidden="true"></i>
                                    <div class="ps-3">
                                        <h5 class="fs-sm mb-0">{{ __('front.nav.home') }}</h5><span class="text-muted fs-ms">{{ __('front.not_found.home_text') }}</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-4 mb-3"><a class="card h-100 border-0 shadow-sm" href="{{ \App\Helpers\LocaleHelper::route('pretrazi') }}">
                            <div class="card-body">
                                <div class="d-flex align-items-center"><i class="fa-solid fa-magnifying-glass text-success h4 mb-0" aria-hidden="true"></i>
                                    <div class="ps-3">
                                        <h5 class="fs-sm mb-0">{{ __('front.not_found.search') }}</h5><span class="text-muted fs-ms">{{ __('front.not_found.search_text') }}</span>
                                    </div>
                                </div>
                            </div></a></div>
                    <div class="col-sm-4 mb-3"><a class="card h-100 border-0 shadow-sm" href="{{ \App\Helpers\LocaleHelper::route('faq') }}">
                            <div class="card-body">
                                <div class="d-flex align-items-center"><i class="fa-regular fa-circle-question text-info h4 mb-0" aria-hidden="true"></i>
                                    <div class="ps-3">
                                        <h5 class="fs-sm mb-0">{{ __('front.faq.title') }}</h5><span class="text-muted fs-ms">{{ __('front.not_found.faq_text') }}</span>
                                    </div>
                                </div>
                            </div></a></div>
                </div>
            </div>
        </div>
    </div>
@endsection
