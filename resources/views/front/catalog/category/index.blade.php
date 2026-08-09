@extends('front.layouts.app')
@php
    $isEnglish = \App\Helpers\LocaleHelper::isEnglish();
    $group = $group ?? null;
    $cat = $cat ?? null;
    $subcat = $subcat ?? null;
    $author = $author ?? null;
    $publisher = $publisher ?? null;
    $listingTitle = __('front.meta.default_title');
    $listingDescription = __('front.meta.default_description');

    if ($group) {
        $groupTitle = \App\Helpers\LocaleHelper::groupTitle($group);
        $listingTitle = $groupTitle . ' - Antikvarijat Biblos';
        $listingDescription = __('front.catalog.group_description', ['name' => $groupTitle]);

        $categoryEntity = $subcat ?: $cat;
        if ($categoryEntity) {
            $listingTitle = trim((string) $categoryEntity->meta_title)
                ?: $categoryEntity->title . ' - Antikvarijat Biblos';
            $listingDescription = trim((string) $categoryEntity->meta_description)
                ?: __('front.catalog.category_description', ['name' => $categoryEntity->title]);
        }
    }

    if ($author && isset($seo['title'], $seo['description'])) {
        $listingTitle = $seo['title'];
        $listingDescription = $seo['description'];
    }

    if ($publisher && isset($seo['title'], $seo['description'])) {
        $listingTitle = $seo['title'];
        $listingDescription = $seo['description'];
    }

    if (request()->routeIs('pretrazi', 'en.pretrazi')) {
        $listingTitle = __('front.search.results_for') . ': ' . request()->input(config('settings.search_keyword')) . ' - Antikvarijat Biblos';
    } elseif (request()->routeIs('tag', 'en.tag')) {
        $listingTitle = __('front.search.results_for_tag') . ': ' . request()->input(config('settings.search_keyword')) . ' - Antikvarijat Biblos';
    }
@endphp
@section('title', $listingTitle)
@section('description', $listingDescription)
@section('schema_page_type', 'CollectionPage')

@push('meta_tags')
    @if (! empty($crumbs))
        <script type="application/ld+json">{!! \App\Helpers\StructuredData::toJson($crumbs) !!}</script>
    @endif
    @if ($author && ! $cat && ! $subcat)
        <script type="application/ld+json">{!! \App\Helpers\StructuredData::toJson(
            \App\Helpers\CatalogEntityStructuredData::author(
                $author,
                \App\Helpers\Metatags::canonical(request()),
                app()->getLocale(),
                $listingDescription
            )
        ) !!}</script>
    @elseif ($publisher && ! $cat && ! $subcat)
        <script type="application/ld+json">{!! \App\Helpers\StructuredData::toJson(
            \App\Helpers\CatalogEntityStructuredData::publisher(
                $publisher,
                \App\Helpers\Metatags::canonical(request()),
                app()->getLocale(),
                $listingDescription
            )
        ) !!}</script>
    @endif
    @include('front.layouts.partials.collection-schema', [
        'collectionPaginator' => $initialProductsPaginator ?? null,
        'collectionName' => $listingTitle,
    ])
@endpush

@push('css_after')
    <style>
        filter-view,
        products-view {
            display: contents;
        }

        #filter-app,
        #filter-app .product-card,
        #filter-app .product-card .card-body,
        #filter-app .product-card .d-flex,
        #filter-app .product-card .text-muted,
        #filter-app .product-card .product-title,
        #filter-app .product-card .product-title > a,
        #filter-app .product-card .product-meta,
        #filter-app .product-card .one-line {
            min-width: 0;
        }

        #filter-app {
            overflow-x: hidden;
        }

        #filter-app .product-card .text-muted,
        #filter-app .product-card .product-meta,
        #filter-app .product-card .one-line {
            max-width: 100%;
        }

        #filter-app .product-card .text-muted {
            width: 100%;
            overflow: hidden;
        }

        #filter-app .product-card .product-meta {
            width: 100%;
        }

        #filter-app .product-card .product-title > a {
            overflow-wrap: anywhere;
        }

        @media (max-width: 499.98px) {
            #filter-app .product-card .card-body {
                padding-left: .75rem;
                padding-right: .75rem;
            }

            #filter-app .product-card .price-box {
                padding-right: 3.5rem;
            }

            #filter-app .product-floating-btn {
                right: .5rem;
                bottom: .5rem;
                opacity: 1;
            }
        }
    </style>
@endpush


@section('content')

    <!-- Page Title-->
    <div class="page-title-overlap bg-light pt-4" style="background-image: url({{ asset('media/img/farmer.png')  }});background-repeat: repeat">
        <div class="container d-lg-block  d-lg-flex justify-content-between py-2 py-lg-3">

            @if (isset($group) && $group)
                <div class="order-lg-2 mb-3 mb-lg-0 pt-lg-2  ">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb breadcrumb-dark flex-lg-nowrap justify-content-center justify-content-lg-start">
                            <li class="breadcrumb-item"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}"><i class="ci-home"></i>{{ __('front.nav.home') }}</a></li>
                            @if ($group && ! $cat && ! $subcat)
                                <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ \App\Helpers\LocaleHelper::groupTitle($group) }}</li>
                            @elseif ($group && $cat)
                                <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('catalog.route', ['group' => $group]) }}">{{ \App\Helpers\LocaleHelper::groupTitle($group) }}</a></li>
                            @endif
                            @if ($cat && ! $subcat)
                                <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ $cat->title }}</li>
                            @elseif ($cat && $subcat)
                                <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('catalog.route', ['group' => $group, 'cat' => $cat]) }}">{{ $cat->title }}</a></li>
                                <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ $subcat->title }}</li>
                            @endif
                        </ol>
                    </nav>
                </div>
                <div class="order-lg-1 pe-lg-4 text-center text-lg-start">
                    @if ($group && ! $cat && ! $subcat)
                        <h1 class="h3 text-dark mb-0">{{ \App\Helpers\LocaleHelper::groupTitle($group) }}</h1>
                    @endif
                    @if ($cat && ! $subcat)
                        <h1 class="h3 text-darkt mb-0">{{ $cat->title }}</h1>
                    @elseif ($cat && $subcat)
                        <h1 class="h3 text-dark mb-0">{{ $subcat->title }}</h1>
                    @endif

                </div>
            @endif

            @if (request()->routeIs('pretrazi', 'en.pretrazi'))
                <div class="order-lg-1 pe-lg-4 text-center text-lg-start">
                    <h1 class="h3 text-dark mb-0"><span class="small fw-light me-2">{{ __('front.search.results_for') }}:</span> {{ request()->input('pojam') }}</h1>
                </div>
            @endif


                @if (request()->routeIs('tag', 'en.tag'))
                    <div class="order-lg-1 pe-lg-4 text-center text-lg-start">
                        <h1 class="h3 text-dark mb-0"><span class="small fw-light me-2">{{ __('front.search.results_for_tag') }}:</span> {{ request()->input('pojam') }}</h1>
                    </div>
                @endif

            @if (isset($author) && $author)
                <div class="order-lg-2 mb-3 mb-lg-0 pt-lg-2">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb breadcrumb-dark flex-lg-nowrap justify-content-center justify-content-lg-start">
                            <li class="breadcrumb-item"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}"><i class="ci-home"></i>{{ __('front.nav.home') }}</a></li>
                            <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.author') }}">{{ __('front.nav.authors') }}</a></li>
                            @if ( ! $cat && ! $subcat)
                                <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ $author->title }}</li>
                            @endif
                            @if ($cat && ! $subcat)
                                <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.author', ['author' => $author]) }}">{{ $author->title }}</a></li>
                                <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ $cat->title }}</li>
                            @elseif ($cat && $subcat)
                                <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.author', ['author' => $author]) }}">{{ $author->title }}</a></li>
                                <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.author', ['author' => $author, 'cat' => $cat]) }}">{{ $cat->title }}</a></li>
                                <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ $subcat->title }}</li>
                            @endif
                        </ol>
                    </nav>
                </div>
                <div class="order-lg-1 pe-lg-4 text-center text-lg-start">
                    <h1 class="h3 text-dark mb-0">{{ $author->title }}</h1>
                </div>
            @endif

            @if (isset($publisher) && $publisher)
                <div class="order-lg-2 mb-3 mb-lg-0 pt-lg-2">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb breadcrumb-dark flex-lg-nowrap justify-content-center justify-content-lg-start">
                            <li class="breadcrumb-item"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}"><i class="ci-home"></i>{{ __('front.nav.home') }}</a></li>
                            <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.publisher') }}">{{ __('front.nav.publishers') }}</a></li>
                            @if ( ! $cat && ! $subcat)
                                <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ $publisher->title }}</li>
                            @endif
                            @if ($cat && ! $subcat)
                                <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.publisher', ['publisher' => $publisher]) }}">{{ $publisher->title }}</a></li>
                                <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ $cat->title }}</li>
                            @elseif ($cat && $subcat)
                                <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.publisher', ['publisher' => $publisher]) }}">{{ $publisher->title }}</a></li>
                                <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.publisher', ['publisher' => $publisher, 'cat' => $cat]) }}">{{ $cat->title }}</a></li>
                                <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ $subcat->title }}</li>
                            @endif
                        </ol>
                    </nav>
                </div>
                <div class="order-lg-1 pe-lg-4 text-center text-lg-start">
                    <h1 class="h3 text-dark mb-0">{{ $publisher->title }}</h1>
                </div>
            @endif

        </div>
    </div>
    <div class="container pb-4 mb-2 mb-md-4" id="filter-app">
        <div class="row">
            <filter-view ids="{{ isset($ids) ? $ids : null }}"
                         group="{{ isset($group) ? $group : null }}"
                         cat="{{ isset($cat) ? $cat : null }}"
                         subcat="{{ isset($subcat) ? $subcat : null }}"
                         author="{{ isset($author) ? $author['slug'] : null }}"
                         publisher="{{ isset($publisher) ? $publisher['slug'] : null }}"
                         locale="{{ app()->getLocale() }}"
                         :initial-categories='@json($initialCategories ?? [])'>
                @include('front.catalog.category.partials.filter-fallback', [
                    'initialCategories' => $initialCategories ?? [],
                    'group' => $group ?? null,
                    'cat' => $cat ?? null,
                    'subcat' => $subcat ?? null,
                    'author' => $author ?? null,
                    'publisher' => $publisher ?? null,
                ])
            </filter-view>
            <products-view ids="{{ isset($ids) ? $ids : null }}"
                           group="{{ isset($group) ? $group : null }}"
                           cat="{{ isset($cat) ? $cat['id'] : null }}"
                           subcat="{{ isset($subcat) ? $subcat['id'] : null }}"
                           author="{{ isset($author) ? $author['slug'] : null }}"
                           publisher="{{ isset($publisher) ? $publisher['slug'] : null }}"
                           locale="{{ app()->getLocale() }}"
                           :initial-products='@json($initialProductsData ?? [])'>
                @include('front.catalog.category.partials.products-fallback', [
                    'initialProductsPaginator' => $initialProductsPaginator ?? null,
                ])
            </products-view>
        </div>
    </div>

    @if ($author || $publisher || $cat || $subcat || $group)
        <section class="container pb-4 mb-2 mb-md-4" aria-label="{{ $listingTitle }}">
            @if ($author && ! empty($author->description))
                {!! $author->description !!}
            @elseif ($publisher && ! empty($publisher->description))
                {!! $publisher->description !!}
            @elseif ($subcat && ! empty($subcat->description))
                {!! $subcat->description !!}
            @elseif ($cat && ! empty($cat->description))
                {!! $cat->description !!}
            @else
                <p>{{ $listingDescription }}</p>
            @endif
        </section>
    @endif

@endsection
