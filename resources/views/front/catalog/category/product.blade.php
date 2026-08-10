@php
    $cardImageIndex = isset($loop) ? $loop->index : null;
    $isProductDetailContext = request()->routeIs('catalog.route') && request()->route('prod');
    $cardImageLoading = ($cardImageIndex !== null && $cardImageIndex < 8 && ! $isProductDetailContext) ? 'eager' : 'lazy';
    $cardImageFetchPriority = ($cardImageIndex !== null && $cardImageIndex < 2 && ! $isProductDetailContext) ? 'high' : 'auto';
    $usesPublisherMeta = isset($publisher) && $publisher && $product->publisher;
    $productMeta = $usesPublisherMeta ? $product->publisher : $product->author;
    $showsProductMeta = $productMeta && (
        $usesPublisherMeta || \App\Models\Front\Catalog\Author::hasMeaningfulTitle($productMeta->title)
    );
@endphp

<div class="card product-card shadow mb-2 catalog-product-card">
    @if ($product->main_price > $product->main_special)
        <span class="badge  bg-dark mt-1 ms-1 badge-shadow">-{{ number_format(floatval(\App\Helpers\Helper::calculateDiscount($product->price, $product->special())), 0) }}%</span>
    @endif
    <div class="product-thumb">

        <a  href="{{ url($product->url) }}">
        <img
            src="{{ $product->thumb }}"
            width="250"
            height="300"
            alt="{{ $product->name }}"
            loading="{{ $cardImageLoading }}"
            fetchpriority="{{ $cardImageFetchPriority }}"
            decoding="async"
            sizes="(max-width: 575px) 50vw, (max-width: 991px) 33vw, (max-width: 1399px) 25vw, 250px">
        </a>
    </div>
    <div class="card-body pt-2">
        @if ($showsProductMeta)
        <div class="d-flex flex-wrap justify-content-between align-items-start pb-2">
            <div class="text-muted fs-xs me-1">
                <a class="product-meta fw-medium" href="{{ url($productMeta->url) }}">{{ $productMeta->title }}</a>
            </div>
        </div>
        @endif
        @include('front.catalog.product.partials.rating-summary', ['product' => $product])
        <h3 class="product-title fs-sm mb-0"><a href="{{ url($product->url) }}">{{ $product->name }}</a></h3>
        @if ($product->category_string)
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div class="fs-sm me-2 one-line"><i class="fa-duotone fa-books text-muted fs-xs"></i> {!! $product->category_string !!}</div>
            </div>
        @endif
        <div class="catalog-product-purchase mt-2">
            <div class="catalog-product-prices">
                <div class="d-flex flex-wrap justify-content-between align-items-center price-box">
                    @if ($product->main_price > $product->main_special)
                        <div class="bg-faded-accent text-accent fs-sm rounded-1 py-1 px-2 text-decoration-line-through">{{ $product->main_price_text }}</div>
                        <div class="bg-faded-accent text-accent fs-sm rounded-1 py-1 px-2">{{ $product->main_special_text }}</div>
                    @else
                        <div class="bg-faded-accent text-accent fs-sm rounded-1 py-1 px-2">{{ $product->main_price_text }}</div>
                    @endif
                </div>
                @if($product->secondary_price_text)
                    <div class="d-flex flex-wrap justify-content-between align-items-center price-box mt-2">
                        @if ($product->main_price > $product->main_special)
                            <div class="bg-faded-accent text-accent fs-sm rounded-1 py-1 px-2 text-decoration-line-through">{{ $product->secondary_price_text }}</div>
                            <div class="bg-faded-accent text-accent fs-sm rounded-1 py-1 px-2">{{ $product->secondary_special_text }}</div>
                        @else
                            <div class="bg-faded-accent text-accent fs-sm rounded-1 py-1 px-2">{{ $product->secondary_price_text }}</div>
                        @endif
                    </div>
                @endif
            </div>

            <div class="product-floating-btn">
                <add-to-cart-btn-simple id="{{ $product->id }}">
                    <a href="{{ url($product->url) }}" class="btn btn-primary btn-sm" aria-label="{{ __('front.product.open') }} {{ $product->name }}">+<i class="fa-regular fa-bag-shopping fs-base ms-1"></i></a>
                </add-to-cart-btn-simple>
            </div>
        </div>
    </div>
</div>
<hr class="d-sm-none">
