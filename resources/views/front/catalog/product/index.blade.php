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
@endpush

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
                   <div class="" id="gallery" style="max-height:750px">
                       <div class="main-image product-thumb">

                           <div class="galerija slider slider-for  mb-3">

                               @if ( ! empty($prod->image))


                                   <div class="item single-product" >
                                       <a class="link" href="{{  ($prod->image) }}">
                                           <img src="{{  ($prod->image) }}" alt="{{ $prod->name }}" height="600" style="max-height:600px" loading="eager" fetchpriority="high" decoding="async">
                                       </a>
                                   </div>


                               @endif

                               @if ($prod->images->count())
                                   @foreach ($prod->images as $key => $image)
                                       <div class="item single-product" >
                                           <a class="link" href="{{  config('settings.images_domain') .($image->image) }}">
                                               <img src="{{  config('settings.images_domain') .($image->image) }}" alt="{{ $image->alt }}" height="600" style="max-height:600px" loading="lazy" decoding="async">
                                           </a>
                                       </div>

                                   @endforeach
                               @endif
                           </div>

                           <ul class=" slider slider-nav mt-2 mb-2">
                               @if ($prod->images->count())
                                   @if ( ! empty($prod->thumb))

                                       <li><img src="{{  ($prod->thumb) }}" class="thumb" width="100" height="100" alt="{{ $prod->name }}" loading="eager" decoding="async"></li>


                                   @endif
                               @foreach ($prod->images as $key => $image)
                                   <li><img src="{{  config('settings.images_domain') .($image->thumb) }}" class="thumb" width="100" height="100" alt="{{ $image->alt }}" loading="lazy" decoding="async"></li>
                               @endforeach

                               @endif
                           </ul>
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



                   <h1 class="h3"><span style="font-weight: 300;">{{ $prod->author ? $prod->author->title.':' : '' }}</span> {{ $prod->name }}</h1>

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
                                       @if ($prod->author)
                                           <li><strong>{{ __('front.product.author') }}:</strong> <a href="{{ \App\Helpers\LocaleHelper::route('catalog.route.author', ['author' => $prod->author]) }}">{{ $prod->author->title }} </a></li>
                                       @endif
                                       @if ($prod->publisher)
                                           <li><strong>{{ __('front.product.publisher') }}:</strong> <a href="{{ \App\Helpers\LocaleHelper::route('catalog.route.publisher', ['publisher' => $prod->publisher]) }}">{{ $prod->publisher->title }}</a> </li>
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
               </div>
           </div>
       </section>
       <!-- Related products-->

       <section class="mx-n2 pb-2 px-2 mb-xl-3" id="tabs_widget">
           <div class="bg-light px-2 mb-3 shadow rounded-3">
               <!-- Tabs-->
               <ul class="nav nav-tabs" role="tablist">
                   <li class="nav-item"><a class="nav-link py-4 px-sm-4 active" href="#specs" data-bs-toggle="tab" role="tab"><span>{{ __('front.product.description') }}</span> </a></li>
                   <li class="nav-item"><a class="nav-link py-4 px-sm-4" href="#reviews" data-bs-toggle="tab" role="tab"><span>{{ __('front.reviews.title') }} ({{ $reviewStats['count'] }})</span></a></li>
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
                                   @if ($prod->author)
                                       <h3 class="h6 mb-4">{{ $prod->author->title }}</h3>
                                   @endif

                                   {{-- Sažetak i opis --}}
                                   <p class="h6">{{ __('front.product.summary') }}</p>
                                   <div class="fs-md pb-2 mb-4">
                                       {!! $prod->description !!}
                                   </div>

                                   {{-- Author and tags at the bottom --}}
                                   @if ($prod->author || !empty($prod->tags))
                                       <div class="mt-auto pt-3 pb-4">
                                           @if ($prod->author)
                                               <a class="btn btn-outline-primary btn-sm btn-shadow me-2 mb-2"
                                                  href="{{ \App\Helpers\LocaleHelper::route('catalog.route.author', ['author' => $prod->author]) }}">
                                                   #{{ $prod->author->title }}
                                               </a>
                                           @endif

                                           @if(!empty($prod->tags))
                                               @foreach($prod->tags as $tag)
                                                   <a class="btn btn-outline-primary btn-sm btn-shadow me-2 mb-2"
                                                      href="{{ \App\Helpers\LocaleHelper::route('tag', ['pojam' => $tag]) }}">
                                                       #{{ $tag }}
                                                   </a>
                                               @endforeach
                                           @endif
                                       </div>
                                   @endif
                               </div>

                               <div class="col-lg-5 col-sm-5">
                                   <h3 class="h6">{{ __('front.product.additional_information') }}</h3>
                                   <ul class="list-unstyled fs-md pb-2">

                                       @if ($prod->author)
                                           <li class="d-flex justify-content-between pb-2 border-bottom">
                                               <span class="text-muted">{{ __('front.product.author') }}:</span>
                                               <span>
                        <a href="{{ \App\Helpers\LocaleHelper::route('catalog.route.author', ['author' => $prod->author]) }}">
                            {{ Illuminate\Support\Str::limit($prod->author->title, 30) }}
                        </a>
                    </span>
                                           </li>
                                       @endif

                                       @if ($prod->publisher)
                                           <li class="d-flex justify-content-between pb-2 border-bottom">
                                               <span class="text-muted">{{ __('front.product.publisher_alt') }}:</span>
                                               <span>
                        <a href="{{ \App\Helpers\LocaleHelper::route('catalog.route.publisher', ['publisher' => $prod->publisher]) }}">
                            {{ Illuminate\Support\Str::limit($prod->publisher->title, 30) }}
                        </a>
                    </span>
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
                       <div class="tab-pane fade" id="reviews" role="tabpanel">
                           <div class="row pt-2">
                               <div class="col-lg-7 mb-4">
                                   <div class="d-flex align-items-center justify-content-between mb-4">
                                       <h2 class="h5 mb-0">{{ __('front.reviews.title') }}</h2>
                                       @if ($reviewStats['count'] > 0)
                                           <div class="text-end">
                                               <div class="h5 text-warning mb-0">★ {{ number_format($reviewStats['average'], 1, ',', '.') }}</div>
                                               <div class="small text-muted">{{ __('front.reviews.rating_summary', ['rating' => number_format($reviewStats['average'], 1, ',', '.'), 'count' => $reviewStats['count']]) }}</div>
                                           </div>
                                       @endif
                                   </div>

                                   @forelse ($reviews as $review)
                                       <article class="border-bottom pb-3 mb-3">
                                           <div class="d-flex justify-content-between align-items-start">
                                               <div>
                                                   <strong>{{ $review->reviewer_name }}</strong>
                                                   @if ($review->is_verified_purchase)
                                                       <span class="badge bg-success ms-2">{{ __('front.reviews.verified_purchase') }}</span>
                                                   @endif
                                               </div>
                                               <time class="small text-muted" datetime="{{ optional($review->approved_at ?: $review->created_at)->toDateString() }}">{{ optional($review->approved_at ?: $review->created_at)->format('d.m.Y.') }}</time>
                                           </div>
                                           <div class="text-warning my-1" aria-label="{{ $review->rating }} / 5">
                                               @for ($star = 1; $star <= 5; $star++)<span aria-hidden="true">{{ $star <= $review->rating ? '★' : '☆' }}</span>@endfor
                                           </div>
                                           @if ($review->title)<h3 class="h6 mb-1">{{ $review->title }}</h3>@endif
                                           <p class="mb-0" style="white-space: pre-line;">{{ $review->body }}</p>
                                       </article>
                                   @empty
                                       <p class="text-muted">{{ __('front.reviews.empty') }}</p>
                                   @endforelse
                               </div>

                               <div class="col-lg-5 mb-4">
                                   <div class="border rounded-3 p-4 bg-white">
                                       <h2 class="h5">{{ __('front.reviews.write') }}</h2>
                                       <form method="POST" action="{{ \App\Helpers\LocaleHelper::route('product-reviews.store') }}" data-product-review-form>
                                           @csrf
                                           <input type="hidden" name="product_id" value="{{ $prod->id }}">
                                           <input type="hidden" name="recaptcha" value="" data-product-review-recaptcha>
                                           <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="d-none" aria-hidden="true">
                                           <div class="mb-3">
                                               <label class="form-label" for="review-name">{{ __('front.reviews.name') }} *</label>
                                               <input class="form-control @error('reviewer_name') is-invalid @enderror" id="review-name" name="reviewer_name" maxlength="191" value="{{ old('reviewer_name', optional(auth()->user())->name) }}" required>
                                               @error('reviewer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                           </div>
                                           <div class="mb-3">
                                               <label class="form-label" for="review-email">{{ __('front.reviews.email') }} *</label>
                                               <input class="form-control @error('reviewer_email') is-invalid @enderror" id="review-email" type="email" name="reviewer_email" maxlength="191" value="{{ old('reviewer_email', optional(auth()->user())->email) }}" required>
                                               <div class="form-text">{{ __('front.reviews.email_private') }}</div>
                                               @error('reviewer_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                           </div>
                                           <div class="mb-3">
                                               <label class="form-label" for="review-rating">{{ __('front.reviews.rating') }} *</label>
                                               <select class="form-select @error('rating') is-invalid @enderror" id="review-rating" name="rating" required>
                                                   <option value="">—</option>
                                                   @for ($rating = 5; $rating >= 1; $rating--)<option value="{{ $rating }}" @if((int) old('rating') === $rating) selected @endif>{{ $rating }} / 5</option>@endfor
                                               </select>
                                               @error('rating')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                           </div>
                                           <div class="mb-3">
                                               <label class="form-label" for="review-title">{{ __('front.reviews.optional_title') }}</label>
                                               <input class="form-control" id="review-title" name="title" maxlength="191" value="{{ old('title') }}">
                                           </div>
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
                       </div>

                   </div>
               </div>
           </div>
       </section>
       <!-- Product description-->
       <section class="pb-5 mb-2 mb-xl-4">
           <div class=" flex-wrap justify-content-between align-items-center  text-center">
               <h2 class="h3 mb-4 pt-1 font-title me-3 text-center">{{ __('front.product.you_may_like') }}</h2>

           </div>
           <div class="tns-carousel tns-controls-static tns-controls-outside tns-nav-enabled pt-2">
               <div class="tns-carousel-inner tns-nav-enabled" data-carousel-options='{"items": 2, "controls": false, "nav": true, "responsive": {"0":{"items":2, "gutter": 5},"500":{"items":2, "gutter": 10},"768":{"items":3, "gutter": 10}, "1100":{"items":4, "controls": true, "gutter": 10}, "1300":{"items":5, "controls": true, "gutter": 10}, "1600":{"items":5, "controls": true, "gutter": 10}}}'>
                   @foreach ($relatedProducts as $cat_product)
                       <div>
                           @include('front.catalog.category.product', ['product' => $cat_product])
                       </div>
                   @endforeach
               </div>
           </div>
       </section>
       @if(isset($recentProducts) && $recentProducts->isNotEmpty())

       <section class="pb-5 mb-2 mb-xl-4">
           <div class=" flex-wrap justify-content-between align-items-center  text-center">
               <h2 class="h3 mb-4 pt-1 font-title me-3 text-center">{{ __('front.product.recently_viewed') }}</h2>

           </div>
           <div class="tns-carousel tns-controls-static tns-controls-outside tns-nav-enabled pt-2">
               <div class="tns-carousel-inner tns-nav-enabled" data-carousel-options='{"items": 2, "controls": false, "nav": true, "responsive": {"0":{"items":2, "gutter": 5},"500":{"items":2, "gutter": 10},"768":{"items":3, "gutter": 10}, "1100":{"items":4, "controls": true, "gutter": 10}, "1300":{"items":5, "controls": true, "gutter": 10}, "1600":{"items":5, "controls": true, "gutter": 10}}}'>
                   @foreach ($recentProducts as $recent)
                       @if ($recent->id != $prod->id)
                           <div>
                               @include('front.catalog.category.product', ['product' => $recent])
                           </div>
                       @endif
                   @endforeach
               </div>
           </div>
       </section>
       @endif
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
        (function () {
            var $gallery = new SimpleLightbox('.galerija a', {});
        })();
    </script>
    @php($i = 0)
    @if ($prod->images->count())
        @foreach ($prod->images as $key => $image)
            @if($image->default == '1')
                @php($i = $key)
            @endif
        @endforeach
    @endif



    <script>
        var $carousel = $('.slider-for').slick({
            slidesToShow:   1,
            slidesToScroll: 1,
            initialSlide: {{ $i }},
            arrows:         false,
            fade:           true,
            asNavFor:       '.slider-nav'
        });
        var $thumbs   = $('.slider-nav').slick({
            slidesToShow:   5,
            slidesToScroll: 1,
            asNavFor:       '.slider-for',
            dots:           false,
            centerMode:     false,
            focusOnSelect:  true,
            loop:           true,

        });


        $(".form-check").click(function () {
            var artworkId = $(this).data('target');

            console.log(artworkId);
            var artIndex = $carousel.find('[data-target="' + artworkId + '"]').data('slick-index');

            console.log(artIndex);

            $carousel.slick('slickGoTo', artIndex);
        });

    </script>

    <script>
        (function () {
            var $gallery = new SimpleLightbox('a.gal', {});
        })();
    </script>

    @include('front.layouts.modal.wishlist-email')
    @include('front.layouts.partials.recaptcha-js')
    <script>
        (function () {
            var form = document.querySelector('[data-product-review-form]');
            if (!form) return;

            if (window.location.hash === '#reviews' || form.querySelector('.is-invalid')) {
                var trigger = document.querySelector('a[href="#reviews"]');
                if (trigger && window.bootstrap && bootstrap.Tab) new bootstrap.Tab(trigger).show();
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
