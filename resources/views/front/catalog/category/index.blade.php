@extends('front.layouts.app')
@php($isEnglish = \App\Helpers\LocaleHelper::isEnglish())

@if (isset($group) && $group)
    @if ($group && ! $cat && ! $subcat)
        @section ( 'title',  \App\Helpers\LocaleHelper::groupTitle($group). ' - Antikvarijat Biblos' )
    @endif
    @if ($cat && ! $subcat)
        @section ( 'title',  $cat->title . ' - Antikvarijat Biblos' )
        @section ( 'description', $cat->meta_description )
    @elseif ($cat && $subcat)
        @section ( 'title', $subcat->title . ' - Antikvarijat Biblos' )
        @section ( 'description', $cat->meta_description )
    @endif
@endif

@if (isset($author) && $author)
    @if (isset($seo['title']) && isset($seo['description']))
        @section ('title',  $seo['title'])
        @section ('description', $seo['description'])
    @endif
    @if (isset($seo['name']) && isset($seo['content']))
        <meta name="{{ $seo['name'] }}" content="{{ $seo['content'] }}">
    @endif
@endif

@if (isset($publisher) && $publisher)
    @section ('title',  $seo['title'])
    @section ('description', $seo['description'])
@endif

@if (isset($meta_tags))
    @push('meta_tags')
        @foreach ($meta_tags as $tag)
            <meta name="{{ $tag['name'] }}" content="{{ $tag['content'] }}">
        @endforeach
    @endpush
@endif

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

    @if (isset($author) && $author && ! empty($author->description))
        <div class="container pb-4 mb-2 mb-md-4" >
            {!! $author->description !!}
        </div>
    @endif

    <div class="container pb-4 mb-2 mb-md-4" >
        @if ($cat && ! $subcat)
            {!! $cat->description !!}
        @elseif ($subcat && ! $subcat)
            {!! $cat->description !!}
        @endif
    </div>

@endsection

@push('js_after')
    <script type="application/ld+json">
        {!! collect($crumbs)->toJson() !!}
    </script>
@endpush
