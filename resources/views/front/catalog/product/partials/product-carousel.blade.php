@php
    $productCount = $products->count();
    $shouldCenterShortCarousel = ($centerWhenShort ?? false) && $productCount < 5;
    $carouselOptions = [
        'items' => $shouldCenterShortCarousel ? min($productCount, 2) : 2,
        'controls' => false,
        'nav' => true,
        'responsive' => [
            0 => ['items' => $shouldCenterShortCarousel ? min($productCount, 2) : 2, 'gutter' => 5],
            500 => ['items' => $shouldCenterShortCarousel ? min($productCount, 2) : 2, 'gutter' => 10],
            768 => ['items' => $shouldCenterShortCarousel ? min($productCount, 3) : 3, 'gutter' => 10],
            1100 => ['items' => $shouldCenterShortCarousel ? min($productCount, 4) : 4, 'controls' => true, 'gutter' => 10],
            1300 => ['items' => $shouldCenterShortCarousel ? min($productCount, 5) : 5, 'controls' => true, 'gutter' => 10],
            1600 => ['items' => $shouldCenterShortCarousel ? min($productCount, 5) : 5, 'controls' => true, 'gutter' => 10],
        ],
    ];

    if ($shouldCenterShortCarousel) {
        $carouselOptions['loop'] = false;
    }
@endphp

@if ($products->isNotEmpty())
    <section class="product-recommendations pb-5 mb-2 mb-xl-4" aria-labelledby="{{ $headingId }}">
        <h2 class="product-recommendations__title h3 font-title" id="{{ $headingId }}">{{ $title }}</h2>
        <div @class([
            'tns-carousel tns-controls-static tns-controls-outside tns-nav-enabled pt-2',
            'product-recommendations__carousel--short' => $shouldCenterShortCarousel,
            'product-recommendations__carousel--count-' . $productCount => $shouldCenterShortCarousel,
        ])>
            <div class="tns-carousel-inner tns-nav-enabled" data-carousel-options='@json($carouselOptions)'>
                @foreach ($products as $carouselProduct)
                    <div>
                        @include('front.catalog.category.product', ['product' => $carouselProduct, 'eagerImages' => false])
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
