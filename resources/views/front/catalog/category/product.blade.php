@php
    $cardImageIndex = isset($loop) ? $loop->index : null;
    $isProductDetailContext = request()->routeIs('catalog.route') && request()->route('prod');
    $cardImageLoading = ($cardImageIndex !== null && $cardImageIndex < 8 && ! $isProductDetailContext) ? 'eager' : 'lazy';
    $cardImageFetchPriority = ($cardImageIndex !== null && $cardImageIndex < 2 && ! $isProductDetailContext) ? 'high' : 'auto';
@endphp

<div class="card product-card shadow mb-2 pb-4">
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
        <div class="d-flex flex-wrap justify-content-between align-items-start pb-2">
            <div class="text-muted fs-xs me-1">
                @if ($product->author)
                    <a class="product-meta fw-medium" href="{{ url($product->author->url) }}">{{ $product->author->title }}</a>
                @else
                    <a class="product-meta fw-medium" href="#">Nepoznato</a>
                @endif
            </div>

        </div>
        <h3 class="product-title fs-sm mb-0"><a href="{{ url($product->url) }}">{{ $product->name }}</a></h3>
        @if ($product->category_string)
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div class="fs-sm me-2 one-line"><i class="ci-book text-muted" style="font-size: 11px;"></i> {!! $product->category_string !!}</div>
            </div>
        @endif
        <div class="d-flex flex-wrap justify-content-between align-items-center price-box mt-2">
            @if ($product->main_price > $product->main_special)
                <div class="bg-faded-accent text-accent text-sm rounded-1 py-1 px-1" style="text-decoration: line-through;">{{ $product->main_price_text }}</div>
                <div class="bg-faded-accent text-accent text-sm rounded-1 py-1 px-1">{{ $product->main_special_text }}</div>
            @else
                <div class="bg-faded-accent text-accent rounded-1 py-1 px-1">{{ $product->main_price_text }}</div>
            @endif
        </div>
        @if($product->secondary_price_text)
            <div class="d-flex flex-wrap justify-content-between align-items-center price-box mt-2">
                @if ($product->main_price > $product->main_special)
                    <div class="bg-faded-accent text-accent text-sm rounded-1 py-1 px-1" style="text-decoration: line-through;">{{ $product->secondary_price_text }}</div>
                    <div class="bg-faded-accent text-accent text-sm rounded-1 py-1 px-1">{{ $product->secondary_special_text }}</div>
                @else
                    <div class="bg-faded-accent text-accent rounded-1 py-1 px-1">{{ $product->secondary_price_text }}</div>
                @endif
            </div>
        @endif


    </div>

        <div class="product-floating-btn">
        <add-to-cart-btn-simple id="{{ $product->id }}">
            <a href="{{ url($product->url) }}" class="btn btn-primary btn-sm" aria-label="Otvori {{ $product->name }}">+<i class="ci-cart fs-base ms-1"></i></a>
        </add-to-cart-btn-simple>
        </div>
</div>
<hr class="d-sm-none">
