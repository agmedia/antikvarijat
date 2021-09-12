<div class="card product-card-alt">
    <div class="product-thumb">
        <div class="product-card-actions">
            <a class="btn btn-light btn-icon btn-shadow fs-base mx-2" href="{{ url($product->url) }}"><i class="ci-eye"></i></a>
            <add-to-cart-btn-simple id="{{ $product->id }}"></add-to-cart-btn-simple>
        </div>
        <a class="product-thumb-overlay" href="{{ url($product->url) }}"></a>
        <img load="lazy" src="{{ url('cache/thumb?size=250x300&src=' . $product->image) }}" width="250" height="300" alt="{{ $product->name }}">
    </div>
    <div class="card-body pt-2">
        <div class="d-flex flex-wrap justify-content-between align-items-start pb-2">
            <div class="text-muted fs-xs me-1">
                @if ($product->author)
                    <a class="product-meta fw-medium" href="{{ $product->author->url }}">{{ $product->author->title }}</a>
                @else
                    <a class="product-meta fw-medium" href="#">Nepoznato</a>
                @endif
            </div>

        </div>
        <h3 class="product-title fs-sm mb-0"><a href="{{ url($product->url) }}">{{ $product->name }}</a></h3>
        @if ($product->category_string)
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div class="fs-sm me-2"><i class="ci-book text-muted" style="font-size: 11px;"></i> {!! $product->category_string !!}</div>
            </div>
        @endif
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-2">
            @if ($product->special())
                <div class="bg-faded-accent text-accent text-sm rounded-1 py-1 px-2" style="text-decoration: line-through;">{!! $product->priceString() !!}</div>
                <div class="bg-faded-accent text-accent rounded-1 py-1 px-2">{!! $product->priceString($product->special()) !!}</div>
            @else
                <div class="bg-faded-accent text-accent rounded-1 py-1 px-2">{!! $product->priceString() !!}</div>
            @endif
        </div>
    </div>
</div>
<hr class="d-sm-none">
