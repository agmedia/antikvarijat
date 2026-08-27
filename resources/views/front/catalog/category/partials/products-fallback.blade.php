<section class="col-lg-9 catalog-products-section">
    @if ($initialProductsPaginator)
        <h2 class="visually-hidden">{{ $productsHeading ?? __('front.catalog.all_products') }}</h2>
        <div class="catalog-products-toolbar d-flex justify-content-center justify-content-sm-between align-items-center pt-2 pb-4 pb-sm-5">
            <div class="catalog-products-toolbar__controls d-flex flex-wrap">
                @php
                    $activeFilterCount = count(array_filter(explode('+', (string) request('autor'))))
                        + count(array_filter(explode('+', (string) request('nakladnik'))))
                        + count(array_filter(explode('+', (string) request('prevoditelj'))))
                        + (request()->filled('start') ? 1 : 0)
                        + (request()->filled('end') ? 1 : 0)
                        + (request()->filled('pismo') ? 1 : 0)
                        + (request()->filled('stanje') ? 1 : 0)
                        + (request()->filled('uvez') ? 1 : 0);
                @endphp
                <div class="me-2 d-lg-none">
                    <a class="btn collapsed catalog-filter-trigger" href="#shop-sidebar" data-bs-toggle="collapse" aria-expanded="false" aria-label="{{ __('front.js.filter.filters') }}">
                        <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                        <span class="visually-hidden">{{ __('front.js.filter.filters') }}</span>
                        @if ($activeFilterCount)
                            <span class="catalog-filter-trigger-count">{{ $activeFilterCount }}</span>
                        @endif
                    </a>
                </div>
                <div class="catalog-sort-control d-flex align-items-center flex-nowrap me-3 me-sm-4 pb-3">
                    @php
                        $currentSort = request()->get('sort', '');
                    @endphp
                    <select class="form-select" aria-label="{{ __('front.js.products.sort') }}" disabled>
                        <option value="" disabled {{ $currentSort === '' ? 'selected' : '' }}>{{ __('front.js.products.sort') }}</option>
                        @foreach (config('settings.sorting_list') as $item)
                            <option value="{{ $item['value'] }}" {{ $currentSort == $item['value'] ? 'selected' : '' }}>{{ $item['title'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="catalog-products-toolbar__aside d-flex pb-3">
                <div class="catalog-view-switch d-flex d-lg-none" role="group" aria-label="{{ __('front.js.filter.view') }}">
                    <button type="button" class="catalog-view-switch__button" aria-pressed="false" aria-label="{{ __('front.js.filter.one_column') }}">
                        <i class="fa-regular fa-square" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="catalog-view-switch__button is-active" aria-pressed="true" aria-label="{{ __('front.js.filter.two_columns') }}">
                        <i class="fa-regular fa-grid-2" aria-hidden="true"></i>
                    </button>
                </div>
                <span class="fs-sm text-light btn btn-primary btn-sm text-nowrap ms-2 d-none d-lg-block">{{ __('front.js.products.total') }} {{ number_format($initialProductsPaginator->total(), 0, ',', '.') }} {{ __('front.js.products.items') }}</span>
            </div>
        </div>

        @if ($initialProductsPaginator->count())
            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-4 mb-3 px-2 catalog-products-grid catalog-products-grid--mobile-2">
                @foreach ($initialProductsPaginator as $product)
                    <div class="col px-2 mb-4">
                        @include('front.catalog.category.product', ['eagerImages' => true])
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
