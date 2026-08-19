@extends('front.layouts.app')
@php
    $isEnglish = \App\Helpers\LocaleHelper::isEnglish();
@endphp
@if (request()->routeIs(['index', 'en.index']))
    @section ( 'title', __('front.meta.default_title') )
@section ( 'description', __('front.meta.default_description') )
@section('canonical', \App\Helpers\LocaleHelper::route('index'))
@section('og_image', 'https://www.antikvarijat-biblos.hr/media/antikvarijat-biblos.jpg')


@push('meta_tags')

    <meta property="og:image:width" content="1920" />
    <meta property="og:image:height" content="720" />

@endpush

@else
    @php
        $pageMetaDescription = $page->meta_description
            ?: \Illuminate\Support\Str::limit(trim(strip_tags((string) $page->short_description)), 160, '');
    @endphp
    @section('title', $page->meta_title ?: $page->title . ' - Antikvarijat Biblos')
    @section('description', $pageMetaDescription)
    @section('schema_page_type', \App\Helpers\StructuredData::contentPageType($page))
@endif

@section('content')
    <main id="main-content">

    @if (request()->routeIs(['index', 'en.index']))

        <h1 class="visually-hidden">{{ __('front.meta.default_title') }}</h1>

        <section style="background-image: url({{ asset('media/img/farmer.png') }});background-repeat: repeat" class="bg-secondary py-4 pt-md-5">
            <div class="container py-xl-2">
                <div class="row">
                    <!-- Slider     -->
                    <div class="col-xl-9 pt-0 order-xl-2">
                        <div class="tns-carousel">
                            <div class="tns-carousel-inner"
                                 data-carousel-options="{&quot;items&quot;:1,&quot;controls&quot;:false,&quot;autoplay&quot;:true,&quot;autoplayTimeout&quot;:5500,&quot;autoplayHoverPause&quot;:true,&quot;speed&quot;:800,&quot;mode&quot;:&quot;carousel&quot;,&quot;loop&quot;:true,&quot;nav&quot;:false,&quot;mouseDrag&quot;:true,&quot;autoplayButtonOutput&quot;:false}">
                                 <div>
                                    <div class="row align-items-center">
                                        <div class="col-md-6 order-md-2"><img class="d-block mx-auto" src="{{ asset('media/img/knjige_rara_shape_left.png') }}" width="500" height="478" alt="Hrvatska RARA" loading="eager" fetchpriority="high" decoding="async"></div>
                                        <div class="col-lg-5 col-md-6 offset-lg-1 order-md-1 pt-4 pb-md-4 text-center text-md-start">
                                            <p class="fw-light h4 pb-1 from-top delay-1">{{ __('front.home.for_collectors') }}</p>
                                            <h2 class="display-6 from-bottom ">Hrvatska RARA</h2>
                                            <p class="h5 fw-light pb-3 from-bottom delay-2">{{ __('front.home.rara_subtitle') }}</p>

                                            <div class="d-table scale-up delay-4 mx-auto mx-md-0"><a class="btn btn-primary btn-shadow" href="{{ url($isEnglish ? 'en/books/hrvatska-rara' : 'knjige/hrvatska-rara') }}">{{ __('front.home.view_selection') }}<i class="fa-solid fa-arrow-right ms-2 me-n1"></i></a></div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div class="row align-items-center">
                                        <div class="col-md-6 order-md-2"><img class="d-block mx-auto" src="{{ asset('media/img/karta_kapljica.png') }}" width="500" height="500" alt="{{ __('front.home.maps_title') }}" loading="lazy" decoding="async"></div>
                                        <div class="col-lg-5 col-md-6 offset-lg-1 order-md-1 pt-4 pb-md-4 text-center text-md-start">
                                            <p class="h4 fw-light pb-1 from-top delay-1">{{ __('front.home.for_collectors') }}</p>
                                            <h2 class="display-6 from-bottom ">{{ __('front.home.maps_title') }}</h2>
                                            <p class="h5 fw-light pb-3 from-bottom delay-2">{{ __('front.home.maps_subtitle') }}</p>

                                            <div class="d-table scale-up delay-4 mx-auto mx-md-0"><a class="btn btn-primary btn-shadow" href="{{ url($isEnglish ? 'en/maps-and-views' : 'zemljovidi-i-vedute') }}">{{ __('front.home.view_selection') }}<i class="fa-solid fa-arrow-right ms-2 me-n1"></i></a></div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>


                    <!-- Banner group-->
                    <div class="col-xl-3 order-xl-1 pt-4 mt-3 mt-xl-0 pt-xl-0">
                        <div class="table-responsive" data-simplebar>
                            <div class="d-flex d-xl-block">
                                <a class="d-flex align-items-center rounded-3 pt-2 ps-2 mb-4 me-3 me-xl-0 mbanner" href="{{ url($isEnglish ? 'en/books/knjizevnost' : 'knjige/knjizevnost') }}" ><img src="{{ asset('media/img/knjizevnost-ikona.png') }}" width="100" height="122" alt="{{ __('front.home.literature') }}" loading="eager" decoding="async">
                                    <div class="py-4 px-2">
                                        <h5 class="mb-2"><span class="fw-light">{{ __('front.home.category') }}:</span> <br> {{ __('front.home.literature') }}</h5>
                                        <div class="text-dark fs-sm">{{ __('front.home.explore_titles') }}<i class="fa-solid fa-arrow-right fs-xs ms-1"></i></div>
                                    </div>
                                </a>
                                <a class="d-flex align-items-center rounded-3 pt-2 ps-2 mb-4 me-4 me-xl-0 mbanner" href="{{ url($isEnglish ? 'en/books/filozofija' : 'knjige/filozofija') }}" ><img src="{{ asset('media/img/umjetnost-ikona.png') }}" width="100" height="122" alt="{{ __('front.home.philosophy') }}" loading="eager" decoding="async">
                                    <div class="py-4 px-2">
                                        <h5 class="mb-2"><span class="fw-light">{{ __('front.home.category') }}:</span> <br> {{ __('front.home.philosophy') }}</h5>
                                        <div class="text-dark fs-sm">{{ __('front.home.explore_titles') }}<i class="fa-solid fa-arrow-right fs-xs ms-1"></i></div>
                                    </div></a>
                                <a class="d-flex align-items-center rounded-3 pt-2 ps-2 mb-4 me-3 me-xl-0 mbanner" href="{{ url($isEnglish ? 'en/books/povijest' : 'knjige/povijest') }}" ><img src="{{ asset('media/img/povijest-ikona.png') }}" width="100" height="122" alt="{{ __('front.home.history') }}" loading="lazy" decoding="async">
                                    <div class="py-4 px-2">
                                        <h5 class="mb-2"><span class="fw-light">{{ __('front.home.category') }}:</span> <br> {{ __('front.home.history') }}</h5>
                                        <div class="text-dark fs-sm">{{ __('front.home.explore_titles') }}<i class="fa-solid fa-arrow-right fs-xs ms-1"></i></div>
                                    </div></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </section>

        {!! $page->rendered_description ?? $page->description !!}


    @else

        <div class=" bg-dark pt-4 pb-3" style="background-image: url({{ asset('media/img/farmer.png')  }});background-repeat: repeat">
            <div class="container d-lg-flex justify-content-between py-2 py-lg-3">
                <div class="order-lg-2 mb-3 mb-lg-0 pt-lg-2">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb breadcrumb-dark flex-lg-nowrap justify-content-center justify-content-lg-start">
                            <li class="breadcrumb-item"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}"><i class="fa-solid fa-house"></i>{{ __('front.nav.home') }}</a></li>
                            <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ $page->title }}</li>
                        </ol>
                    </nav>
                </div>
                <div class="order-lg-1 pe-lg-4 text-center text-lg-start">
                    <h1 class="h3 text-dark">{{ $page->title }}</h1>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="mt-5 mb-5">
                {!! $page->description !!}
            </div>
        </div>

    @endif

    </main>

@endsection
