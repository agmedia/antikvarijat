<section class="col-lg-9 catalog-products-section">
    @if ($initialProductsPaginator)
        <div class="d-flex justify-content-center justify-content-sm-between align-items-center pt-2 pb-4 pb-sm-5">
            <div class="d-flex flex-wrap">
                <div class="dropdown me-2 d-sm-none">
                    <a class="btn btn-primary dropdown-toggle collapsed" href="#shop-sidebar" data-bs-toggle="collapse" aria-expanded="false"><i class="ci-filter-alt"></i></a>
                </div>
                <div class="d-flex align-items-center flex-nowrap me-3 me-sm-4 pb-3">
                    @php
                        $currentSort = request()->get('sort', '');
                    @endphp
                    <select class="form-select" aria-label="{{ __('front.js.products.sort') }}" disabled>
                        <option value="" disabled @selected($currentSort === '')>{{ __('front.js.products.sort') }}</option>
                        @foreach (config('settings.sorting_list') as $item)
                            <option value="{{ $item['value'] }}" @selected($currentSort == $item['value'])>{{ $item['title'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="d-flex pb-3">
                <span class="fs-sm text-light btn btn-primary btn-sm text-nowrap ms-2 d-none d-sm-block">{{ __('front.js.products.total') }} {{ number_format($initialProductsPaginator->total(), 0, ',', '.') }} {{ __('front.js.products.items') }}</span>
            </div>
        </div>

        @if ($initialProductsPaginator->count())
            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-4 mb-3 px-2 catalog-products-grid">
                @foreach ($initialProductsPaginator as $product)
                    <div class="col px-2 mb-4">
                        @include('front.catalog.category.product')
                    </div>
                @endforeach
            </div>

            <div class="catalog-pagination-wrap">
                {{ $initialProductsPaginator->onEachSide(2)->links('vendor.pagination.catalog') }}

                <p class="catalog-pagination-summary mb-0">
                    {{ __('front.js.products.shown') }}
                    <strong>{{ number_format($initialProductsPaginator->firstItem(), 0, ',', '.') }}–{{ number_format($initialProductsPaginator->lastItem(), 0, ',', '.') }}</strong>
                    {{ __('front.js.products.of') }}
                    <strong>{{ number_format($initialProductsPaginator->total(), 0, ',', '.') }}</strong>
                    {{ $initialProductsPaginator->total() % 10 === 1 && $initialProductsPaginator->total() % 100 !== 11 ? __('front.js.products.result') : __('front.js.products.results') }}
                </p>
            </div>
        @else
            <div class="col-md-12 px-2 mb-4">
                @if (Route::currentRouteName() == 'pretrazi' || Route::currentRouteName() == 'en.pretrazi')
                    <h2>{{ __('front.js.products.no_search_results_title') }}</h2>
                    <p>{{ __('front.js.products.your_search_for') }} <mark>"{{ request()->input('pojam') }}"</mark> {{ __('front.js.products.zero_results') }}</p>
                @elseif (Route::currentRouteName() == 'catalog.route.actions' || Route::currentRouteName() == 'en.catalog.route.actions')
                    <h2>{{ __('front.js.products.sale_products_empty_title') }}</h2>
                    <p>{{ __('front.js.products.sale_products_empty_text') }}</p>
                @else
                    <h2>{{ __('front.js.products.no_products_title') }}</h2>
                    <p>{{ __('front.js.products.no_products_text') }}</p>
                @endif
            </div>
        @endif
    @endif
</section>
