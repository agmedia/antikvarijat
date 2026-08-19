@extends('front.layouts.app')
@section('title', __('front.faq.meta_title'))
@section('description', __('front.faq.meta_description'))

@php
    $faqSchema = \App\Helpers\StructuredData::faqPage(
        \App\Helpers\LocaleHelper::route('faq'),
        $faq,
        app()->getLocale()
    );
@endphp

@if (! empty($faqSchema['mainEntity']))
    @push('meta_tags')
        <script type="application/ld+json">{!! \App\Helpers\StructuredData::toJson($faqSchema) !!}</script>
    @endpush
@endif

@section('content')
    <main id="main-content">

    <!-- Page Title-->
    <div class=" bg-dark pt-4 pb-3" style="background-image: url({{ asset('media/img/farmer.png')  }});background-repeat: repeat">
        <div class="container d-lg-flex justify-content-between py-2 py-lg-3">
            <div class="order-lg-2 mb-3 mb-lg-0 pt-lg-2">

                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-dark flex-lg-nowrap justify-content-center justify-content-lg-start">
                        <li class="breadcrumb-item"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}"><i class="fa-solid fa-house"></i>{{ __('front.nav.home') }}</a></li>
                        <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ __('front.faq.title') }}</li>
                    </ol>
                </nav>

            </div>
            <div class="order-lg-1 pe-lg-4 text-center text-lg-start">
                <h1 class="text-dark">{{ __('front.faq.title') }}</h1>
            </div>
        </div>
    </div>


    <div class="container">



        <div class="mt-5 mb-5">

    <!-- Flush accordion. Use this when you need to render accordions edge-to-edge with their parent container -->

            <div class="bg-white rounded-3 shadow-lg p-4">
    <div class="accordion accordion-flush" id="accordionFlushExample">


    @foreach ($faq as $fa)

        <!-- Item -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="flush-heading{{ $fa->id }}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse{{ $fa->id }}" aria-expanded="false" aria-controls="flush-collapse{{ $fa->id }}">{{ $fa->title }}</button>
                </h2>
                <div class="accordion-collapse collapse" id="flush-collapse{{ $fa->id }}" aria-labelledby="flush-heading{{ $fa->id }}" data-bs-parent="#accordionFlushExample">
                    <div class="accordion-body">  {!! $fa->description !!}</div>
                </div>
            </div>

    @endforeach








    </div>


    </div>

        </div>
    </div>

    </main>
@endsection
