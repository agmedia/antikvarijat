@extends('front.layouts.app')
@section ('title', $seo['title'])
@section ('description', $seo['description'])
@section('og_type', 'product')
@section('schema_page_type', 'ItemPage')
@section('og_image', $prod->image)
@section('og_image_type', 'image/webp')
@section('og_image_alt', $prod->image_alt ?: $prod->name)
@section('canonical', url($prod->url))
@push('meta_tags')
    <meta property="og:updated_time" content="{{ $prod->updated_at  }}" />
    <meta property="product:price:amount" content="{{ number_format((float) $prod->special(), 2, '.', '') }}" />
    <meta property="product:price:currency" content="EUR" />
    <meta property="product:availability" content="{{ $prod->quantity > 0 ? 'in stock' : 'out of stock' }}" />
    <meta property="product:retailer_item_id" content="{{ $prod->sku }}" />
@endpush

@push('css_after')
    <link rel="stylesheet" media="screen" href="{{ asset('js/slick/slick.css') }}">
    <link rel="stylesheet" media="screen" href="{{ asset('js/slick/slick-theme.css') }}">
    <link rel="stylesheet" media="screen" href="{{ asset('js/simple-lightbox.css?v2.14.0') }}">
    <link rel="stylesheet" media="screen" href="{{ asset('css/product.css?v=' . filemtime(public_path('css/product.css'))) }}">
@endpush

@php
    $reviewErrorFields = ['reviewer_name', 'reviewer_email', 'rating', 'title', 'body', 'recaptcha'];
    $shouldOpenReviewForm = collect($reviewErrorFields)->contains(fn ($field) => $errors->has($field));
    $reviewAverage = (float) $reviewStats['average'];
    $reviewAverageFormatted = number_format($reviewAverage, 1, app()->getLocale() === 'hr' ? ',' : '.', '');
    $reviewCountLabel = trans_choice('front.reviews.count', $reviewStats['count'], ['count' => $reviewStats['count']]);
    $exploreCategory = $subcat ?: $cat;
    $exploreCategoryUrl = $cat ? \App\Helpers\LocaleHelper::categoryUrl($cat, $subcat) : null;
    $hasExploreLinks = $authorProducts->isNotEmpty() || $publisherProducts->isNotEmpty() || $relatedProducts->isNotEmpty();
    $galleryItems = collect();

    if ($prod->getRawOriginal('image')) {
        $galleryItems->push([
            'image' => $prod->image,
            'thumb' => $prod->thumb,
            'alt' => $prod->name,
        ]);
    }

    foreach ($prod->images as $productImage) {
        $galleryItems->push([
            'image' => config('settings.images_domain') . $productImage->image,
            'thumb' => $productImage->image ? config('settings.images_domain') . $productImage->thumb : null,
            'alt' => $productImage->alt ?: $prod->name,
        ]);
    }

    $galleryItems = $galleryItems->filter(fn ($item) => !empty($item['image']))->unique('image')->values();
@endphp

@if (isset($gdl))
    @section('google_data_layer')
        <script>
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({ ecommerce: null });
            window.dataLayer.push({
                'event': 'view_item',
                'ecommerce': {
                    'items': [<?php echo json_encode($gdl); ?>]
                } });
        </script>
    @endsection
@endif

@section('content')

   <div class="container">
       <!-- Page title + breadcrumb-->
       <nav class="my-3" aria-label="breadcrumb">
           <ol class="breadcrumb flex-lg-nowrap">
               <li class="breadcrumb-item"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}"><i class="ci-home"></i>{{ __('front.nav.home') }}</a></li>
               @if ($group)
                   @if ($group && ! $cat && ! $subcat)
                       <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ \App\Helpers\LocaleHelper::groupTitle($group) }}</li>
                   @elseif ($group && $cat)
                       <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('catalog.route', ['group' => $group]) }}">{{ \App\Helpers\LocaleHelper::groupTitle($group) }}</a></li>
                   @endif

                   @if ($cat && ! $subcat)
                       @if ($prod)
                           <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('catalog.route', ['group' => $group, 'cat' => $cat]) }}">{{ $cat->title }}</a></li>
                       @else
                           <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ $cat->title }}</li>
                       @endif
                   @elseif ($cat && $subcat)
                       <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('catalog.route', ['group' => $group, 'cat' => $cat]) }}">{{ $cat->title }}</a></li>
                       @if ($prod)
                           @if ($cat && ! $subcat)
                               <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('catalog.route', ['group' => $group, 'cat' => $cat]) }}">{{ \Illuminate\Support\Str::limit($prod->name, 50) }}</a></li>
                           @else
                               <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('catalog.route', ['group' => $group, 'cat' => $cat, 'subcat' => $subcat]) }}">{{ $subcat->title }}</a></li>
                           @endif
                       @endif
                   @endif
               @endif

           </ol>
       </nav>
       <!-- Content-->
       <section class="row g-0 mx-n2 ">
           @include('back.layouts.partials.session')
           <!-- Product Gallery + description-->
           <div class="col-xl-6 px-2 mb-3">


               <div class="h-100 bg-light shadow rounded-3 p-4">
                   <div class="product-gallery-frame" id="gallery" data-product-gallery>
                       <div class="main-image product-thumb">
                           <div class="product-gallery-main" data-product-gallery-main>
                               @foreach ($galleryItems as $galleryItem)
                                   <div class="product-gallery-slide">
                                       <a class="product-gallery-slide__link" href="{{ $galleryItem['image'] }}">
                                           <img
                                               class="product-gallery-image"
                                               src="{{ $galleryItem['image'] }}"
                                               width="600"
                                               height="600"
                                               alt="{{ $galleryItem['alt'] }}"
                                               loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                               @if ($loop->first) fetchpriority="high" @endif
                                               decoding="async"
                                               draggable="false">
                                       </a>
                                   </div>
                               @endforeach
                           </div>

                           @if ($galleryItems->count() > 1)
                               <div class="product-gallery-swipe-hint" role="img" aria-label="{{ __('front.product.swipe_gallery') }}">
                                   <i class="fa-solid fa-hand-pointer" aria-hidden="true"></i>
                               </div>
                               <ul class="product-gallery-thumbs" data-product-gallery-thumbs>
                                   @foreach ($galleryItems as $galleryItem)
                                       <li>
                                           <button
                                               class="product-gallery-thumbnail{{ $loop->first ? ' is-active' : '' }}"
                                               type="button"
                                               data-product-gallery-index="{{ $loop->index }}"
                                               aria-label="{{ __('front.product.gallery_image', ['number' => $loop->iteration]) }}"
                                               aria-current="{{ $loop->first ? 'true' : 'false' }}">
                                               <img
                                                   class="product-gallery-thumbnail__image"
                                                   src="{{ $galleryItem['thumb'] ?: $galleryItem['image'] }}"
                                                   width="100"
                                                   height="100"
                                                   alt=""
                                                   loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                                   decoding="async"
                                                   draggable="false">
                                           </button>
                                       </li>
                                   @endforeach
                               </ul>
                           @endif
                       </div>
                   </div>
               </div>
           </div>
           <div class="col-xl-6 px-2 mb-3">
               <div class="h-100 bg-light shadow  rounded-3 py-5 px-4 px-sm-5">

                   @if ( $prod->quantity < 1)
                       <span class="badge bg-warning ">{{ __('front.product.sold_out') }}</span>
                   @endif

                   @if ($prod->main_price > $prod->main_special)
                       <span class="badge bg-primary ">-{{ number_format(floatval(\App\Helpers\Helper::calculateDiscount($prod->price, $prod->special())), 0) }}%</span>
                   @endif

                   @if ($reviewStats['count'] > 0)
                       <a
                           class="product-rating-anchor"
                           href="#reviews"
                           aria-label="{{ __('front.reviews.average', ['rating' => $reviewAverageFormatted]) }}. {{ $reviewCountLabel }}"
                       >
                           <span class="product-rating-stars" aria-hidden="true">
                               @for ($star = 1; $star <= 5; $star++)
                                   <i class="{{ $star <= round($reviewAverage) ? 'ci-star-filled active' : 'ci-star' }}"></i>
                               @endfor
                           </span>
                           <span class="product-rating-anchor__summary">{{ $reviewAverageFormatted }} / 5 · {{ $reviewCountLabel }}</span>
                       </a>
                   @endif

                   <h1 class="h3"><span class="product-title-author">{{ $hasAuthor ? $prod->author->title.':' : '' }}</span> {{ $prod->name }}</h1>

                       <div class="mb-0 mt-4">
                           @if ($prod->main_price > $prod->main_special)
                               <span class="h3 fw-normal text-accent me-1">{{ $prod->main_special_text }}</span>
                               <del class="text-muted fs-lg me-3">{{ $prod->main_price_text }}</del>
                               <span class="badge bg-danger align-middle mt-n2">{{ __('front.product.sale') }}</span>
                           @else
                               <span class="h3 fw-normal text-accent me-1">{{ $prod->main_price_text }}</span>
                           @endif
                          {{--  @if ($prod->quantity)
                               <span class="badge bg-success align-middle mt-n2">Dostupno</span>
                           @else
                               <span class="badge bg-danger align-middle mt-n2">Nije dostupno</span>
                           @endif--}}
                       </div>

                       @if($prod->secondary_price_text)
                           <div class="mb-3 mt-1">
                               @if ($prod->main_price > $prod->main_special)
                                   <span class="h3 fw-normal text-accent me-1">{{ $prod->secondary_special_text }}</span>
                                   <del class="text-muted fs-lg me-3">{{ $prod->secondary_price_text }}</del>
                               @else
                                   <span class="h3 fw-normal text-accent me-1">{{ $prod->secondary_price_text }}</span>
                               @endif
                           </div>
                       @endif

                       <add-to-cart-btn :id="{{ $prod->id }}":product='@json($prod->toArray())':wishlist="{{ $prod->quantity }}"></add-to-cart-btn>

                       <!-- Light alert -->
                       <div class="alert alert-secondary d-flex fs-sm" role="alert">
                           <div class="alert-icon">
                               <i class="ci-gift"></i>
                           </div>
                           <div>{{ __('front.product.free_delivery_notice', ['amount' => config('settings.free_shipping')]) }}</div>
                       </div>

                   <!-- Product panels-->
                   <div class="accordion mb-4" id="productPanels">
                       <div class="accordion-item">
                           <h3 class="accordion-header"><a class="accordion-button" href="#productInfo" role="button" data-bs-toggle="collapse" aria-expanded="true" aria-controls="productInfo"><i class="ci-announcement text-muted fs-lg align-middle mt-n1 me-2"></i>{{ __('front.product.basic_information') }}</a></h3>
                           <div class="accordion-collapse collapse show" id="productInfo" data-bs-parent="#productPanels">
                               <div class="accordion-body">

                                   <ul class="fs-sm ps-4 mb-0 info-list">
                                       @if ($hasAuthor)
                                           <li><strong>{{ __('front.product.author') }}:</strong> <a href="{{ \App\Helpers\LocaleHelper::route('catalog.route.author', ['author' => $prod->author]) }}">{{ $prod->author->title }} </a></li>
                                       @endif
                                       @if ($hasPublisher)
                                           <li><strong>{{ __('front.product.publisher') }}:</strong> <a href="{{ \App\Helpers\LocaleHelper::route('catalog.route.publisher', ['publisher' => $prod->publisher]) }}">{{ $prod->publisher->title }}</a> </li>
                                       @elseif ($prod->publisher)
                                           <li><strong>{{ __('front.product.publisher') }}:</strong> -</li>
                                       @endif
                                       @if ($prod->isbn)
                                           <li><strong>ISBN:</strong> {{ $prod->isbn }} </li>
                                       @endif
                                       @if ($prod->quantity)
                                           @if ($prod->decrease)
                                               <li><strong>{{ __('front.product.availability') }}:</strong> {{ $prod->quantity }} </li>
                                           @else
                                               <li><strong>{{ __('front.product.availability') }}:</strong> <span class="badge bg-success align-middle ">{{ __('front.product.available') }}</span></li>
                                           @endif
                                       @else
                                           <li><strong>{{ __('front.product.availability') }}:</strong> <span class="badge bg-danger align-middle ">{{ __('front.product.sold_out') }}</span></li>
                                       @endif
                                           @if ($prod->condition)
                                       <li><strong>{{ __('front.product.condition') }}:</strong> {{ \App\Helpers\LocaleHelper::localizedProductAttribute('condition', $prod->condition) }} </li>
                                           @endif
                                           @if ($prod->sku)
                                               <li><strong>{{ __('front.product.code') }}:</strong> {{ $prod->sku }} </li>
                                           @endif
                                   </ul>

                               </div>
                           </div>
                       </div>
                       <div class="accordion-item">
                           <h3 class="accordion-header"><a class="accordion-button collapsed" href="#shippingOptions" role="button" data-bs-toggle="collapse" aria-expanded="true" aria-controls="shippingOptions"><i class="ci-delivery text-muted lead align-middle mt-n1 me-2"></i>{{ __('front.product.shipping_options') }}</a></h3>
                           <div class="accordion-collapse collapse" id="shippingOptions" data-bs-parent="#productPanels">
                               <div class="accordion-body fs-sm">

                                   @foreach($shipping_methods as $shipping_method)
                                       @php
                                           $shippingTime = \App\Helpers\LocaleHelper::localizedSettingDataField($shipping_method, 'time');
                                           $shippingDescription = \App\Helpers\LocaleHelper::localizedSettingDataField($shipping_method, 'short_description');
                                           $shippingPrice = (float) data_get($shipping_method, 'data.price', 0);
                                           $isQuotedShipping = $shipping_method->code === 'gls_world';
                                           $isFreeShipping = ! $isQuotedShipping && (
                                               $shippingPrice <= 0
                                               || (float) $prod->special() > (float) config('settings.free_shipping')
                                           );
                                       @endphp
                                       <div class="d-flex justify-content-between  py-2">
                                           <div>
                                               <div class="fw-semibold text-dark">{{ \App\Helpers\LocaleHelper::localizedSettingField($shipping_method, 'title') }}</div>
                                               @if ($shippingTime)
                                                   <div class="fs-sm text-muted me-1">{{ __('front.product.delivery_time') }}: {{ $shippingTime }}</div>
                                               @endif
                                               @if ($shippingDescription)
                                                   <div class="fs-sm text-muted me-1">{{ $shippingDescription }}</div>
                                               @endif
                                           </div>
                                           <div class="text-end ms-3">
                                               @if ($isQuotedShipping)
                                                   {{ __('front.product.shipping_price_on_request') }}
                                               @elseif ($isFreeShipping)
                                                   {{ __('front.product.shipping_free') }}
                                               @else
                                                   {{ number_format($shippingPrice, 2, ',', '.') }} €
                                               @endif
                                           </div>
                                       </div>
                                   @endforeach

                               </div>
                               <small class="mt-2"></small>
                           </div>
                       </div>
                       <div class="accordion-item">
                           <h3 class="accordion-header"><a class="accordion-button collapsed" href="#localStore" role="button" data-bs-toggle="collapse" aria-expanded="true" aria-controls="localStore"><i class="ci-card text-muted fs-lg align-middle mt-n1 me-2"></i>{{ __('front.product.payment_methods') }}</a></h3>
                           <div class="accordion-collapse collapse" id="localStore" data-bs-parent="#productPanels">
                               <div class="accordion-body fs-sm">


                                   @foreach($payment_methods as $payment_method)
                                       @if($prod->origin == 'Engleski' and $payment_method->code == 'cod' )

                                       @else
                                           <div class="d-flex justify-content-between  py-2">
                                               <div>
                                                   <div class="fw-semibold text-dark">
                                                       {{ \App\Helpers\LocaleHelper::localizedSettingField($payment_method, 'title') }}
                                                   </div>
                                                   @if (\App\Helpers\LocaleHelper::localizedSettingDataField($payment_method, 'description'))
                                                       <div class="fs-sm text-muted">{{ \App\Helpers\LocaleHelper::localizedSettingDataField($payment_method, 'description') }}</div>
                                                   @endif
                                               </div>
                                           </div>
                                       @endif
                                   @endforeach

                               </div>


                           </div>
                       </div>
                   </div>
                   <!-- Sharing-->
                   <!-- ShareThis BEGIN --><div class="sharethis-inline-share-buttons"></div><!-- ShareThis END -->

                   @if ($hasExploreLinks)
                       <section class="product-explore" aria-labelledby="product-explore-title">
                           <h2 class="product-explore__title h6" id="product-explore-title">{{ __('front.product.explore_more') }}</h2>
                           <div class="product-explore__links">
                               @if ($authorProducts->isNotEmpty())
                                   <a class="btn btn-outline-primary btn-sm" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.author', ['author' => $prod->author]) }}">
                                       {{ __('front.product.more_by_author', ['author' => $prod->author->title]) }}
                                   </a>
                               @endif
                               @if ($publisherProducts->isNotEmpty())
                                   <a class="btn btn-outline-primary btn-sm" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.publisher', ['publisher' => $prod->publisher]) }}">
                                       {{ __('front.product.more_from_publisher', ['publisher' => $prod->publisher->title]) }}
                                   </a>
                               @endif
                               @if ($relatedProducts->isNotEmpty() && $exploreCategory && $exploreCategoryUrl)
                                   <a class="btn btn-outline-primary btn-sm" href="{{ $exploreCategoryUrl }}">
                                       {{ __('front.product.browse_category', ['category' => $exploreCategory->title]) }}
                                   </a>
                               @endif
                           </div>
                       </section>
                   @endif
               </div>
           </div>
       </section>
       <!-- Related products-->

       <section class="mx-n2 pb-2 px-2 mb-xl-3" id="tabs_widget">
           <div class="bg-light px-2 mb-3 shadow rounded-3">
               <!-- Tabs-->
               <ul class="nav nav-tabs" role="tablist">
                   <li class="nav-item"><a class="nav-link py-4 px-sm-4 active" href="#specs" data-bs-toggle="tab" role="tab"><span>{{ __('front.product.description') }}</span> </a></li>
               </ul>
               <div class="px-4 pt-lg-3 pb-3 mb-5">
                   <div class="tab-content px-lg-3">
                       <!-- Tech specs tab-->
                       <div class="tab-pane fade show active" id="specs" role="tabpanel">
                           <!-- Specs table-->
                           <div class="row pt-2">
                               <div class="col-lg-7 col-sm-7 d-flex flex-column">

                                   {{-- Title and author --}}
                                   <h2 class="h5 mb-2 pb-0">{{ $prod->name }}</h2>
                                   @if ($hasAuthor)
                                       <h3 class="h6 mb-4">{{ $prod->author->title }}</h3>
                                   @endif

                                   {{-- Sažetak i opis --}}
                                   <p class="h6">{{ __('front.product.summary') }}</p>
                                   <div class="fs-md pb-2 mb-4">
                                       {!! $prod->description !!}
                                   </div>

                                   {{-- Tags at the bottom --}}
                                   @if (!empty($prod->tags))
                                       <div class="mt-auto pt-3 pb-4">
                                           @foreach($prod->tags as $tag)
                                               <a class="btn btn-outline-primary btn-sm btn-shadow me-2 mb-2"
                                                  href="{{ \App\Helpers\LocaleHelper::route('tag', ['pojam' => $tag]) }}">
                                                   #{{ $tag }}
                                               </a>
                                           @endforeach
                                       </div>
                                   @endif
                               </div>

                               <div class="col-lg-5 col-sm-5">
                                   <h3 class="h6">{{ __('front.product.additional_information') }}</h3>
                                   <ul class="list-unstyled fs-md pb-2">

                                       @if ($hasAuthor)
                                           <li class="d-flex justify-content-between pb-2 border-bottom">
                                               <span class="text-muted">{{ __('front.product.author') }}:</span>
                                               <span>
                        <a href="{{ \App\Helpers\LocaleHelper::route('catalog.route.author', ['author' => $prod->author]) }}">
                            {{ Illuminate\Support\Str::limit($prod->author->title, 30) }}
                        </a>
                    </span>
                                           </li>
                                       @endif

                                       @if ($hasPublisher)
                                           <li class="d-flex justify-content-between pb-2 border-bottom">
                                               <span class="text-muted">{{ __('front.product.publisher_alt') }}:</span>
                                               <span>
                        <a href="{{ \App\Helpers\LocaleHelper::route('catalog.route.publisher', ['publisher' => $prod->publisher]) }}">
                            {{ Illuminate\Support\Str::limit($prod->publisher->title, 30) }}
                        </a>
                    </span>
                                           </li>
                                       @elseif ($prod->publisher)
                                           <li class="d-flex justify-content-between pb-2 border-bottom">
                                               <span class="text-muted">{{ __('front.product.publisher_alt') }}:</span>
                                               <span>-</span>
                                           </li>
                                       @endif

                                       {{--@if ($prod->origin)
                                           <li class="d-flex justify-content-between pb-2 border-bottom">
                                               <span class="text-muted">Jezik:</span><span>{{ $prod->origin }}</span>
                                           </li>
                                       @endif--}}

                                       @if ($prod->year)
                                           <li class="d-flex justify-content-between pb-2 border-bottom">
                                               <span class="text-muted">{{ __('front.product.year_of_publication') }}:</span><span>{{ $prod->year }}</span>
                                           </li>
                                       @endif

                                       @if ($prod->origin)
                                           <li class="d-flex justify-content-between pb-2 border-bottom">
                                               <span class="text-muted">{{ __('front.product.place_of_publication') }}:</span><span>{{ $prod->origin }}</span>
                                           </li>
                                       @endif

                                       @if ($prod->pages)
                                           <li class="d-flex justify-content-between pb-2 border-bottom">
                                               <span class="text-muted">{{ __('front.product.pages') }}:</span><span>{{ $prod->pages }}</span>
                                           </li>
                                       @endif

                                       @if ($prod->dimensions)
                                           <li class="d-flex justify-content-between pb-2 border-bottom">
                                               <span class="text-muted">{{ __('front.product.dimensions') }}:</span><span>{{ $prod->dimensions.' cm' }}</span>
                                           </li>
                                       @endif

                                       @if ($prod->letter)
                                           <li class="d-flex justify-content-between pb-2 border-bottom">
                                               <span class="text-muted">{{ __('front.product.script') }}:</span><span>{{ \App\Helpers\LocaleHelper::localizedProductAttribute('letter', $prod->letter) }}</span>
                                           </li>
                                       @endif

                                       @if ($prod->condition)
                                           <li class="d-flex justify-content-between pb-2 border-bottom">
                                               <span class="text-muted">{{ __('front.product.condition') }}:</span><span>{{ \App\Helpers\LocaleHelper::localizedProductAttribute('condition', $prod->condition) }}</span>
                                           </li>
                                       @endif

                                       @if ($prod->binding)
                                           <li class="d-flex justify-content-between pb-2 border-bottom">
                                               <span class="text-muted">{{ __('front.product.binding') }}:</span><span>{{ \App\Helpers\LocaleHelper::localizedProductAttribute('binding', $prod->binding) }}</span>
                                           </li>
                                       @endif

                                   </ul>
                               </div>
                           </div>

                       </div>
                   </div>

                   <section class="product-reviews-section px-lg-3" id="reviews" aria-labelledby="reviews-title">
                       <div class="product-reviews-section__header d-flex flex-wrap align-items-baseline justify-content-between gap-2">
                           <h2 class="h4 mb-0" id="reviews-title">{{ __('front.reviews.title') }}</h2>
                           <span class="text-muted">{{ $reviewCountLabel }}</span>
                       </div>

                       @if ($reviewStats['count'] > 0)
                           <div class="product-review-summary">
                               <div class="row g-4 align-items-center">
                                   <div class="col-md-4">
                                       <div class="d-flex align-items-center gap-3">
                                           <span class="product-review-summary__average">{{ $reviewAverageFormatted }}</span>
                                           <div>
                                               <div class="product-rating-stars mb-1" aria-hidden="true">
                                                   @for ($star = 1; $star <= 5; $star++)
                                                       <i class="{{ $star <= round($reviewAverage) ? 'ci-star-filled active' : 'ci-star' }}"></i>
                                                   @endfor
                                               </div>
                                               <div class="small text-muted">{{ __('front.reviews.average', ['rating' => $reviewAverageFormatted]) }}</div>
                                           </div>
                                       </div>
                                   </div>
                                   <div class="col-md-8 product-review-distribution">
                                       @for ($rating = 5; $rating >= 1; $rating--)
                                           @php($ratingCount = (int) ($reviewStats['distribution'][$rating] ?? 0))
                                           <div class="product-review-distribution__row">
                                               <span>{{ $rating }} ★</span>
                                               <progress
                                                   class="product-review-distribution__progress"
                                                   value="{{ $ratingCount }}"
                                                   max="{{ $reviewStats['count'] }}"
                                                   aria-label="{{ $rating }} / 5: {{ $ratingCount }}"
                                               ></progress>
                                               <span class="text-end">{{ $ratingCount }}</span>
                                           </div>
                                       @endfor
                                   </div>
                               </div>
                           </div>
                       @endif

                       <div class="product-review-write">
                           <div>
                               <h3 class="h5 mb-1">{{ __('front.reviews.write') }}</h3>
                               <p class="text-muted mb-0">{{ __('front.reviews.write_intro') }}</p>
                           </div>
                           <button
                               class="btn btn-primary"
                               type="button"
                               data-product-review-toggle
                               data-open-label="{{ __('front.reviews.write') }}"
                               data-close-label="{{ __('front.reviews.close_form') }}"
                               aria-controls="review-form"
                               aria-expanded="{{ $shouldOpenReviewForm ? 'true' : 'false' }}"
                           >
                               <span data-product-review-toggle-label>{{ $shouldOpenReviewForm ? __('front.reviews.close_form') : __('front.reviews.write') }}</span>
                           </button>
                       </div>

                       <div
                           class="product-review-form-collapse{{ $shouldOpenReviewForm ? '' : ' is-collapsed' }}"
                           id="review-form"
                           data-product-review-form-container
                           aria-hidden="{{ $shouldOpenReviewForm ? 'false' : 'true' }}"
                           @unless ($shouldOpenReviewForm) inert @endunless
                       >
                           <div class="product-review-form-collapse__inner">
                               <div class="product-review-form-shell">
                                   <h3 class="h5 mb-1">{{ __('front.reviews.write') }}</h3>
                                   <p class="small text-muted mb-4">{{ __('front.reviews.form_hint') }}</p>
                                   <form method="POST" action="{{ \App\Helpers\LocaleHelper::route('product-reviews.store') }}" data-product-review-form>
                               @csrf
                               <input type="hidden" name="product_id" value="{{ $prod->id }}">
                               <input type="hidden" name="recaptcha" value="" data-product-review-recaptcha>
                               <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="d-none" aria-hidden="true">
                               <div class="row">
                                   <div class="col-md-6 mb-3">
                                       <label class="form-label" for="review-name">{{ __('front.reviews.name') }} *</label>
                                       <input class="form-control @error('reviewer_name') is-invalid @enderror" id="review-name" name="reviewer_name" maxlength="191" value="{{ old('reviewer_name', optional(auth()->user())->name) }}" required>
                                       @error('reviewer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                   </div>
                                   <div class="col-md-6 mb-3">
                                       <label class="form-label" for="review-email">{{ __('front.reviews.email') }} *</label>
                                       <input class="form-control @error('reviewer_email') is-invalid @enderror" id="review-email" type="email" name="reviewer_email" maxlength="191" value="{{ old('reviewer_email', optional(auth()->user())->email) }}" required>
                                       <div class="form-text">{{ __('front.reviews.email_private') }}</div>
                                       @error('reviewer_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                   </div>
                               </div>
                               <fieldset class="mb-3">
                                   <legend class="form-label">{{ __('front.reviews.rating') }} *</legend>
                                   <div class="product-review-rating @error('rating') is-invalid @enderror">
                                       @for ($rating = 5; $rating >= 1; $rating--)
                                           <input
                                               class="product-review-rating__input"
                                               id="review-rating-{{ $rating }}"
                                               type="radio"
                                               name="rating"
                                               value="{{ $rating }}"
                                               aria-label="{{ __('front.reviews.rating_option', ['rating' => $rating]) }}"
                                               {{ (int) old('rating') === $rating ? 'checked' : '' }}
                                               required>
                                           <label
                                               class="product-review-rating__star"
                                               for="review-rating-{{ $rating }}">
                                               <i class="ci-star-filled" aria-hidden="true"></i>
                                           </label>
                                       @endfor
                                   </div>
                                   @error('rating')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                               </fieldset>
                               <div class="mb-3">
                                   <label class="form-label" for="review-body">{{ __('front.reviews.comment') }} *</label>
                                   <textarea class="form-control @error('body') is-invalid @enderror" id="review-body" name="body" rows="5" minlength="10" maxlength="5000" required>{{ old('body') }}</textarea>
                                   @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                   @error('recaptcha')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                               </div>
                                       <button class="btn btn-primary" type="submit">{{ __('front.reviews.submit') }}</button>
                                   </form>
                               </div>
                           </div>
                       </div>

                       <div class="product-review-list">
                           @forelse ($reviews as $review)
                               <article class="product-review-card">
                                   <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                       <div>
                                           <strong>{{ $review->reviewer_name }}</strong>
                                           @if ($review->is_verified_purchase)
                                               <span class="badge bg-success ms-2">{{ __('front.reviews.verified_purchase') }}</span>
                                           @endif
                                       </div>
                                       <time class="small text-muted" datetime="{{ optional($review->approved_at ?: $review->created_at)->toDateString() }}">{{ optional($review->approved_at ?: $review->created_at)->format('d.m.Y.') }}</time>
                                   </div>
                                   <div class="product-review-card__stars my-2" aria-label="{{ $review->rating }} / 5">
                                       @for ($star = 1; $star <= 5; $star++)<span aria-hidden="true">{{ $star <= $review->rating ? '★' : '☆' }}</span>@endfor
                                   </div>
                                   @if ($review->title)<h3 class="h6 mb-1">{{ $review->title }}</h3>@endif
                                   <p class="product-review-card__body mb-0">{{ $review->body }}</p>
                               </article>
                           @empty
                               <p class="text-muted mb-4">{{ __('front.reviews.empty') }}</p>
                           @endforelse
                       </div>
                   </section>
               </div>
           </div>
       </section>
       @if ($hasAuthor)
           @include('front.catalog.product.partials.product-carousel', [
               'products' => $authorProducts,
               'title' => __('front.product.more_by_author', ['author' => $prod->author->title]),
               'headingId' => 'more-by-author-title',
           ])
       @endif

       @if ($hasPublisher)
           @include('front.catalog.product.partials.product-carousel', [
               'products' => $publisherProducts,
               'title' => __('front.product.more_by_publisher', ['publisher' => $prod->publisher->title]),
               'headingId' => 'more-by-publisher-title',
           ])
       @endif

       @include('front.catalog.product.partials.product-carousel', [
           'products' => $relatedProducts,
           'title' => __('front.product.you_may_like'),
           'headingId' => 'related-products-title',
       ])

       @include('front.catalog.product.partials.product-carousel', [
           'products' => $recentProducts,
           'title' => __('front.product.recently_viewed'),
           'headingId' => 'recent-products-title',
       ])
   </div>

@endsection

@push('js_after')
    <script src="{{ asset('js/slick/slick.min.js') }}"></script>
    <script src="{{ asset('js/simple-lightbox.js?v2.14.0') }}"></script>


    <script type="application/ld+json">
        {!! \App\Helpers\StructuredData::toJson($crumbs) !!}
    </script>
    <script type="application/ld+json">
        {!! \App\Helpers\StructuredData::toJson($bookscheme) !!}
    </script>
    <script type='text/javascript' src='https://platform-api.sharethis.com/js/sharethis.js#property=6134a372eae16400120a5035&product=sop' async='async'></script>

    <script>
        (function ($) {
            var $gallery = $('[data-product-gallery]').first();
            if (!$gallery.length) return;

            var $carousel = $gallery.find('[data-product-gallery-main]');
            var $thumbs = $gallery.find('[data-product-gallery-thumbs]');
            var $thumbnailButtons = $gallery.find('[data-product-gallery-index]');
            var slideCount = $carousel.children().length;

            function setActiveThumbnail(index) {
                $thumbnailButtons.each(function () {
                    var $button = $(this);
                    var isActive = Number($button.data('product-gallery-index')) === Number(index);

                    $button.toggleClass('is-active', isActive);
                    $button.attr('aria-current', isActive ? 'true' : 'false');
                });
            }

            if (slideCount > 1 && $.fn.slick) {
                $carousel.on('afterChange', function (event, slick, currentSlide) {
                    setActiveThumbnail(currentSlide);

                    if ($thumbs.hasClass('slick-initialized')) {
                        $thumbs.slick('slickGoTo', currentSlide);
                    }
                });

                $carousel.slick({
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    arrows: false,
                    dots: false,
                    fade: false,
                    infinite: true,
                    speed: 350,
                    swipe: true,
                    swipeToSlide: true,
                    draggable: true,
                    touchMove: true,
                    touchThreshold: 10,
                    waitForAnimate: false,
                    adaptiveHeight: false
                });

                if ($thumbs.length) {
                    $thumbs.slick({
                        slidesToShow: Math.min(5, slideCount),
                        slidesToScroll: 1,
                        arrows: false,
                        dots: false,
                        infinite: false,
                        centerMode: false,
                        swipe: true,
                        swipeToSlide: true,
                        draggable: true,
                        touchMove: true,
                        responsive: [
                            {
                                breakpoint: 768,
                                settings: {
                                    slidesToShow: Math.min(4, slideCount)
                                }
                            },
                            {
                                breakpoint: 576,
                                settings: {
                                    slidesToShow: Math.min(3, slideCount)
                                }
                            }
                        ]
                    });
                }

                $thumbnailButtons.on('click', function () {
                    var slideIndex = Number($(this).data('product-gallery-index'));

                    setActiveThumbnail(slideIndex);
                    $carousel.slick('slickGoTo', slideIndex);
                });
            } else {
                $gallery.addClass('is-single-image');
            }

            $('.form-check[data-target]').on('click', function () {
                if (!$carousel.hasClass('slick-initialized')) return;

                var artIndex = $carousel.find('[data-target="' + $(this).data('target') + '"]').data('slick-index');
                if (typeof artIndex !== 'undefined') {
                    $carousel.slick('slickGoTo', artIndex);
                }
            });

            new SimpleLightbox('[data-product-gallery-main] a', {});
        })(jQuery);
    </script>

    @include('front.layouts.modal.wishlist-email')
    @include('front.layouts.partials.recaptcha-js')
    <script>
        (function () {
            var form = document.querySelector('[data-product-review-form]');
            if (!form) return;

            var container = document.querySelector('[data-product-review-form-container]');
            var toggle = document.querySelector('[data-product-review-toggle]');
            var toggleLabel = toggle ? toggle.querySelector('[data-product-review-toggle-label]') : null;
            var ratingAnchor = document.querySelector('.product-rating-anchor');
            var reviewsSection = document.getElementById('reviews');

            if (ratingAnchor && reviewsSection) {
                ratingAnchor.addEventListener('click', function (event) {
                    event.preventDefault();
                    reviewsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });

                    if (window.history && window.history.pushState) {
                        window.history.pushState(null, '', '#reviews');
                    }
                });
            }

            function setFormOpen(isOpen) {
                if (!container || !toggle) return;

                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                container.classList.toggle('is-collapsed', !isOpen);
                container.setAttribute('aria-hidden', isOpen ? 'false' : 'true');

                if (isOpen) {
                    container.removeAttribute('inert');
                } else {
                    container.setAttribute('inert', '');
                }

                if (toggleLabel) {
                    toggleLabel.textContent = isOpen ? toggle.dataset.closeLabel : toggle.dataset.openLabel;
                }
            }

            if (toggle) {
                toggle.addEventListener('click', function () {
                    setFormOpen(toggle.getAttribute('aria-expanded') !== 'true');
                });
            }

            if (form.querySelector('.is-invalid') || window.location.hash === '#review-form') {
                setFormOpen(true);
            }

            form.addEventListener('submit', function (event) {
                var input = form.querySelector('[data-product-review-recaptcha]');
                if (!input || input.value || !window.grecaptcha) return;
                event.preventDefault();
                grecaptcha.ready(function () {
                    grecaptcha.execute(@json(config('services.recaptcha.sitekey')), {action: 'product_review'}).then(function (token) {
                        input.value = token || 'local-bypass';
                        form.submit();
                    });
                });
            });
        })();
    </script>
@endpush
