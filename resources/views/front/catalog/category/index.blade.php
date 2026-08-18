@extends('front.layouts.app')
@php
    $isEnglish = \App\Helpers\LocaleHelper::isEnglish();
    $group = $group ?? null;
    $cat = $cat ?? null;
    $subcat = $subcat ?? null;
    $author = $author ?? null;
    $publisher = $publisher ?? null;
    $filterParentUrl = null;
    $categoryEntity = null;
    $categoryOgImage = null;
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

    if ($categoryEntity) {
        $categoryImage = trim((string) $categoryEntity->getRawOriginal('image'));

        if ($categoryImage !== '' && ! str_ends_with($categoryImage, 'media/avatars/avatar0.jpg')) {
            $categoryOgImage = \App\Support\AdminImage::url($categoryImage, null);
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

    $currentPage = max(1, (int) request()->query('page', 1));
    if ($categoryEntity && ! $author && ! $publisher && $currentPage > 1) {
        $listingTitle = __('front.catalog.paginated_title', [
            'name' => $categoryEntity->title,
            'page' => $currentPage,
        ]);
        $listingDescription = __('front.catalog.paginated_description', [
            'name' => $categoryEntity->title,
            'page' => $currentPage,
        ]);
    }

    if ($cat || $subcat) {
        if ($author) {
            $filterParentUrl = \App\Helpers\LocaleHelper::route('catalog.route.author', array_filter([
                'author' => $author,
                'cat' => $subcat ? $cat : null,
            ]));
        } elseif ($publisher) {
            $filterParentUrl = \App\Helpers\LocaleHelper::route('catalog.route.publisher', array_filter([
                'publisher' => $publisher,
                'cat' => $subcat ? $cat : null,
            ]));
        } elseif ($group) {
            $filterParentUrl = \App\Helpers\LocaleHelper::route('catalog.route', array_filter([
                'group' => $group,
                'cat' => $subcat ? $cat : null,
            ]));
        }
    }
@endphp
@section('title', $listingTitle)
@section('description', $listingDescription)
@section('schema_page_type', 'CollectionPage')
@if ($categoryOgImage)
    @section('og_image', $categoryOgImage)
    @section('og_image_alt', $categoryEntity->title)
@endif

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
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('css/category.css') }}">
@endpush


@section('content')

    <main id="main-content">

    <!-- Page Title-->
    <div class="page-title-overlap bg-light pt-4 catalog-page-title">
        <div class="container d-lg-block  d-lg-flex justify-content-between py-2 py-lg-3">

            @if (isset($group) && $group)
                <div class="order-lg-2 mb-3 mb-lg-0 pt-lg-2  ">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb breadcrumb-dark flex-lg-nowrap justify-content-center justify-content-lg-start">
                            <li class="breadcrumb-item"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}"><i class="fa-solid fa-house"></i>{{ __('front.nav.home') }}</a></li>
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
                            <li class="breadcrumb-item"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}"><i class="fa-solid fa-house"></i>{{ __('front.nav.home') }}</a></li>
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
                            <li class="breadcrumb-item"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}"><i class="fa-solid fa-house"></i>{{ __('front.nav.home') }}</a></li>
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
                         :parent-url='@json($filterParentUrl)'
                         :initial-categories='@json($initialCategories ?? [])'
                         :initial-attributes='@json($initialAttributes ?? [])'>
                @include('front.catalog.category.partials.filter-fallback', [
                    'initialCategories' => $initialCategories ?? [],
                    'initialAttributes' => $initialAttributes ?? [],
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

    </main>

@endsection
