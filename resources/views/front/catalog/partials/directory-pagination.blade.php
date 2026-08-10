@if ($paginator->total())
    <div class="catalog-pagination-wrap">
        {{ $paginator->onEachSide(2)->links('vendor.pagination.catalog') }}

        <p class="catalog-pagination-summary mb-0">
            {{ __('front.js.products.shown') }}
            <strong>{{ number_format($paginator->firstItem(), 0, ',', '.') }}–{{ number_format($paginator->lastItem(), 0, ',', '.') }}</strong>
            {{ __('front.js.products.of') }}
            <strong>{{ number_format($paginator->total(), 0, ',', '.') }}</strong>
            {{ $paginator->total() % 10 === 1 && $paginator->total() % 100 !== 11 ? __('front.js.products.result') : __('front.js.products.results') }}
        </p>
    </div>
@endif
