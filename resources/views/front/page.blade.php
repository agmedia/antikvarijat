@extends('front.layouts.app')
@if (request()->routeIs(['index']))
    @section ( 'title', 'Antikvarijat Biblos - Knjige, vedute i zemljovidi' )
@section ( 'description', 'Dobrodošli na stranice Antikvarijata Biblos, Palmotićeva 28, Zagreb. Radno vrijeme pon-pet 09-20h, sub 09-14h.' )


@push('meta_tags')

    <link rel="canonical" href="{{ env('APP_URL')}}" />
    <meta property="og:locale" content="hr_HR" />
    <meta property="og:type" content="product" />
    <meta property="og:title" content="Antikvarijat Biblos - Knjige, vedute i zemljovidi" />
    <meta property="og:description" content="Dobrodošli na stranice Antikvarijata Biblos, Palmotićeva 28, Zagreb. Radno vrijeme pon-pet 09-20h, sub 09-14h." />
    <meta property="og:url" content="{{ env('APP_URL')}}"  />
    <meta property="og:site_name" content="Antikvarijat Biblos" />
    <meta property="og:image" content="https://www.antikvarijat-biblos.hr//media/antikvarijat-biblos.jpg" />
    <meta property="og:image:secure_url" content="https://www.antikvarijat-biblos.hr//media/antikvarijat-biblos.jpg" />
    <meta property="og:image:width" content="1920" />
    <meta property="og:image:height" content="720" />
    <meta property="og:image:type" content="image/jpeg" />
    <meta property="og:image:alt" content="Antikvarijat Biblos - Knjige, vedute i zemljovidi" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="Antikvarijat Biblos - Knjige, vedute i zemljovidi" />
    <meta name="twitter:description" content="Antikvarijat Biblos - Knjige, vedute i zemljovidi" />
    <meta name="twitter:image" content="https://www.antikvarijat-biblos.hr/media/antikvarijat-biblos.jpg" />

@endpush

@else
    @section ( 'title', $page->title. ' - Antikvarijat Biblos' )
@section ( 'description', $page->meta_description )
@endif

@section('content')

    @if (request()->routeIs(['index']))

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
                                        <div class="col-md-6 order-md-2"><img class="d-block mx-auto" src="{{ asset('media/img/knjige_rara_shape_left.png') }}" alt="Hrvatska RARA"></div>
                                        <div class="col-lg-5 col-md-6 offset-lg-1 order-md-1 pt-4 pb-md-4 text-center text-md-start">
                                            <p class="fw-light h4 pb-1 from-top delay-1">Za istinske kolekcionare</p>
                                            <h2 class="display-6 from-bottom ">Hrvatska RARA</h2>
                                            <p class="h5 fw-light pb-3 from-bottom delay-2">Jedinstvena izdanja za Vašu biblioteku</p>

                                            <div class="d-table scale-up delay-4 mx-auto mx-md-0"><a class="btn btn-primary btn-shadow" href="knjige/hrvatska-rara">Pogledajte ponudu<i class="ci-arrow-right ms-2 me-n1"></i></a></div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div class="row align-items-center">
                                        <div class="col-md-6 order-md-2"><img class="d-block mx-auto" src="{{ asset('media/img/karta_kapljica.png') }}" alt="Hrvatska RARA"></div>
                                        <div class="col-lg-5 col-md-6 offset-lg-1 order-md-1 pt-4 pb-md-4 text-center text-md-start">
                                            <p class="h4 fw-light pb-1 from-top delay-1">Za istinske kolekcionare</p>
                                            <h2 class="display-6 from-bottom ">Zemljovidi i vedute</h2>
                                            <p class="h5 fw-light pb-3 from-bottom delay-2">Karte i grafike koje nose stoljeća</p>

                                            <div class="d-table scale-up delay-4 mx-auto mx-md-0"><a class="btn btn-primary btn-shadow" href="zemljovidi-i-vedute">Pogledajte ponudu<i class="ci-arrow-right ms-2 me-n1"></i></a></div>
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
                                <a class="d-flex align-items-center rounded-3 pt-2 ps-2 mb-4 me-3 me-xl-0 mbanner" href="knjige/knjizevnost" ><img src="{{ asset('media/img/knjizevnost-ikona.png') }}" width="100" alt="Banner">
                                    <div class="py-4 px-2">
                                        <h5 class="mb-2"><span class="fw-light">Kategorija:</span> <br> Književnost</h5>
                                        <div class="text-dark fs-sm">Istražite naslove<i class="ci-arrow-right fs-xs ms-1"></i></div>
                                    </div>
                                </a>
                                <a class="d-flex align-items-center rounded-3 pt-2 ps-2 mb-4 me-4 me-xl-0 mbanner" href="knjige/filozofija" ><img src="{{ asset('media/img/umjetnost-ikona.png') }}" width="100" alt="Banner">
                                    <div class="py-4 px-2">
                                        <h5 class="mb-2"><span class="fw-light">Kategorija:</span> <br> </span> Filozofija</h5>
                                        <div class="text-dark fs-sm">Istražite naslove<i class="ci-arrow-right fs-xs ms-1"></i></div>
                                    </div></a>
                                <a class="d-flex align-items-center rounded-3 pt-2 ps-2 mb-4 me-3 me-xl-0 mbanner" href="knjige/povijest" ><img src="{{ asset('media/img/povijest-ikona.png') }}" width="100" alt="Banner">
                                    <div class="py-4 px-2">
                                        <h5 class="mb-2"><span class="fw-light">Kategorija:</span> <br> </span> Povijest</h5>
                                        <div class="text-dark fs-sm">Istražite naslove<i class="ci-arrow-right fs-xs ms-1"></i></div>
                                    </div></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            
        </section>
        
       


        {!! $page->description !!}


    @else

        <div class=" bg-dark pt-4 pb-3" style="background-image: url({{ asset('media/img/farmer.png')  }});background-repeat: repeat">
            <div class="container d-lg-flex justify-content-between py-2 py-lg-3">
                <div class="order-lg-2 mb-3 mb-lg-0 pt-lg-2">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb breadcrumb-dark flex-lg-nowrap justify-content-center justify-content-lg-start">
                            <li class="breadcrumb-item"><a class="text-nowrap" href="{{ route('index') }}"><i class="ci-home"></i>Naslovnica</a></li>
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

@endsection
