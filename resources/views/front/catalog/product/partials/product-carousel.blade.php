@if ($products->isNotEmpty())
    <section class="product-recommendations pb-5 mb-2 mb-xl-4" aria-labelledby="{{ $headingId }}">
        <h2 class="product-recommendations__title h3 font-title" id="{{ $headingId }}">{{ $title }}</h2>
        <div class="tns-carousel tns-controls-static tns-controls-outside tns-nav-enabled pt-2">
            <div class="tns-carousel-inner tns-nav-enabled" data-carousel-options='{"items":2,"controls":false,"nav":true,"responsive":{"0":{"items":2,"gutter":5},"500":{"items":2,"gutter":10},"768":{"items":3,"gutter":10},"1100":{"items":4,"controls":true,"gutter":10},"1300":{"items":5,"controls":true,"gutter":10},"1600":{"items":5,"controls":true,"gutter":10}}}'>
                @foreach ($products as $carouselProduct)
                    <div>
                        @include('front.catalog.category.product', ['product' => $carouselProduct])
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
