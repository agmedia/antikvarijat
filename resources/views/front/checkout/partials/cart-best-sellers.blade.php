@if ($products->isNotEmpty())
    <section class="pt-2 pb-lg-2 widget-product-carousel" aria-labelledby="cart-best-sellers-title">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-center pt-1 pb-2 mb-2">
                <div>
                    <h2 class="h3 mb-0 pt-3 font-title me-3" id="cart-best-sellers-title">
                        <span class="border-color">{{ __('front.checkout.best_sellers_title') }}</span>
                    </h2>
                    <p class="text-ph fs-md mb-0">{{ __('front.checkout.best_sellers_subtitle') }}</p>
                </div>
            </div>

            <div class="tns-carousel catalog-product-carousel pt-2 pb-3">
                <div class="tns-carousel-inner tns-nav-enabled" data-carousel-options='{"items":2,"controls":false,"nav":true,"responsive":{"0":{"items":2,"gutter":5},"500":{"items":2,"gutter":10},"768":{"items":3,"gutter":10},"1100":{"items":4,"controls":true,"gutter":10},"1400":{"items":5,"controls":true,"gutter":10},"1600":{"items":5,"controls":true,"gutter":10}}}'>
                    @foreach ($products as $product)
                        <div>
                            @include('front.catalog.category.product')
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
