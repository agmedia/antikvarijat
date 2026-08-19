<!-- {"title": "Carousel", "description": "Widget za product carousel"} -->
<section class="pt-2 pb-lg-2 widget-product-carousel">
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center pt-1   pb-2 mb-2">
        <div>
            <h2 class="h3 mb-0 pt-3 font-title me-3"><span class="border-color">{{ $data['title'] }}</span></h2>
            @if($data['subtitle'])  <p class=" text-ph fs-md mb-0">{{ $data['subtitle'] }}</p> @endif
        </div>
        @if($data['url'] !='/')
         <a class="btn btn-outline-primary btn-sm btn-shadow mt-3" href="{{ url(\App\Helpers\LocaleHelper::localizedUrl($data['url'])) }}"><span class="d-none d-sm-inline-block">{{ __('front.widgets.view_offer') }}</span> <i class="fa-solid fa-arrow-right fs-xs "></i></a>
        @endif

    </div>
    <div class="tns-carousel pt-2 pb-3">
        <div class="tns-carousel-inner tns-nav-enabled" data-carousel-options='{"items": 2, "controls": false, "nav": true, "responsive": {"0":{"items":2, "gutter": 5},"500":{"items":2, "gutter": 10},"768":{"items":3, "gutter": 10}, "1100":{"items":4, "controls": true, "gutter": 10}, "1400":{"items":5, "controls": true, "gutter": 10}, "1600":{"items":5, "controls": true, "gutter": 10}}}'>


            @foreach ($data['items'] as $product)
                <!-- Product-->
                <div>
                    @include('front.catalog.category.product', [
                        'publisher' => ($data['tablename'] ?? null) === 'publisher',
                        'eagerImages' => false,
                    ])
                </div>
            @endforeach
        </div>
    </div>
</div>

</section>
