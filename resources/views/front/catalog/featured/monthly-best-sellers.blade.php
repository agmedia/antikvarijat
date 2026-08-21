@extends('front.layouts.app')

@section('title', __('front.monthly_best_sellers.meta_title'))
@section('description', __('front.monthly_best_sellers.meta_description'))
@section('schema_page_type', 'CollectionPage')
@section('canonical', \App\Helpers\LocaleHelper::route('featured.monthly-best-sellers'))

@push('meta_tags')
    @include('front.layouts.partials.collection-schema', [
        'collectionPaginator' => $products,
        'collectionName' => __('front.monthly_best_sellers.title'),
    ])
@endpush

@push('css_after')
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('css/category.css') }}">
@endpush

@section('content')
    <main id="main-content" class="monthly-best-sellers-page">
        <div class="bg-light pt-4 catalog-page-title">
            <div class="container d-lg-block d-lg-flex justify-content-between py-2 py-lg-3">
                <div class="order-lg-2 mb-3 mb-lg-0 pt-lg-2">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb breadcrumb-dark flex-lg-nowrap justify-content-center justify-content-lg-start">
                            <li class="breadcrumb-item">
                                <a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}">
                                    <i class="fa-solid fa-house" aria-hidden="true"></i>{{ __('front.nav.home') }}
                                </a>
                            </li>
                            <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ __('front.monthly_best_sellers.title') }}</li>
                        </ol>
                    </nav>
                </div>
                <div class="order-lg-1 pe-lg-4 text-center text-lg-start">
                    <h1 class="h3 text-dark mb-0">{{ __('front.monthly_best_sellers.title') }}</h1>
                </div>
            </div>
        </div>

        <section class="container monthly-best-sellers-results" aria-label="{{ __('front.monthly_best_sellers.title') }}">
            @if ($products->isNotEmpty())
                <div class="monthly-best-sellers-toolbar catalog-products-toolbar">
                    <form class="monthly-best-sellers-sort" action="{{ \App\Helpers\LocaleHelper::route('featured.monthly-best-sellers') }}" method="get">
                        <label class="visually-hidden" for="monthly-best-sellers-sort">{{ __('front.js.products.sort') }}</label>
                        <select id="monthly-best-sellers-sort" class="form-select" name="sort" data-submit-on-change>
                            <option value="best_selling" {{ $sort === 'best_selling' ? 'selected' : '' }}>{{ __('front.monthly_best_sellers.best_selling') }}</option>
                            <option value="novi" {{ $sort === 'novi' ? 'selected' : '' }}>{{ __('front.js.products.newest') }}</option>
                            <option value="price_up" {{ $sort === 'price_up' ? 'selected' : '' }}>{{ __('front.js.products.lowest_price') }}</option>
                            <option value="price_down" {{ $sort === 'price_down' ? 'selected' : '' }}>{{ __('front.js.products.highest_price') }}</option>
                            <option value="naziv_up" {{ $sort === 'naziv_up' ? 'selected' : '' }}>A - Ž</option>
                            <option value="naziv_down" {{ $sort === 'naziv_down' ? 'selected' : '' }}>Ž - A</option>
                        </select>
                        <button class="visually-hidden" type="submit">{{ __('front.monthly_best_sellers.apply_sort') }}</button>
                    </form>

                    <span class="monthly-best-sellers-total fs-sm text-light btn btn-primary btn-sm text-nowrap">
                        {{ __('front.js.products.total') }} {{ number_format($products->total(), 0, ',', '.') }} {{ __('front.js.products.items') }}
                    </span>
                </div>

                <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-4 row-cols-xxl-5 monthly-best-sellers-grid">
                    @foreach ($products as $product)
                        <div class="col monthly-best-sellers-grid__item">
                            @include('front.catalog.category.product', [
                                'product' => $product,
                                'eagerImages' => $loop->index < 5,
                            ])
                        </div>
                    @endforeach
                </div>

                @if ($products->hasPages())
                    <div class="catalog-pagination-wrap">
                        {{ $products->onEachSide(2)->links('vendor.pagination.catalog') }}
                    </div>
                @endif
            @else
                <div class="monthly-best-sellers-empty">
                    <i class="fa-duotone fa-books" aria-hidden="true"></i>
                    <h2>{{ __('front.monthly_best_sellers.empty_title') }}</h2>
                    <p>{{ __('front.monthly_best_sellers.empty_text') }}</p>
                </div>
            @endif
        </section>
    </main>
@endsection
