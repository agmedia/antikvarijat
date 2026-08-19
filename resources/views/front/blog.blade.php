@extends('front.layouts.app')
@if(isset($blogs))
    @section ( 'title', __('front.blog.meta_title') )
    @section ( 'description', __('front.blog.meta_description') )
    @section('schema_page_type', 'CollectionPage')
@else
    @php
        $blogMetaTitle = trim((string) \App\Helpers\LocaleHelper::localizedField($blog, 'meta_title', false));
        $blogMetaDescription = trim((string) \App\Helpers\LocaleHelper::localizedField($blog, 'meta_description', false));
    @endphp
    @section('title', $blogMetaTitle ?: $blog->title . ' - Antikvarijat Biblos')
    @section('description', $blogMetaDescription ?: $blog->short_description)
    @section('og_type', 'article')
    @section('og_image', $blog->image)
    @section('og_image_alt', $blog->title)

@endif

@push('meta_tags')
    @if (isset($blogs))
        <script src="{{ \App\Helpers\Asset::url('js/imagesloaded/imagesloaded.pkgd.min.js') }}"></script>
        <script src="{{ \App\Helpers\Asset::url('js/shufflejs/dist/shuffle.min.js') }}"></script>
        @include('front.layouts.partials.collection-schema', [
            'collectionPaginator' => $blogs,
            'collectionName' => __('front.blog.title'),
        ])
    @endif
    @if (! isset($blogs))
        <meta property="article:published_time" content="{{ optional(\Illuminate\Support\Carbon::make($blog->publish_date ?: $blog->created_at))->toAtomString() }}">
        <meta property="article:modified_time" content="{{ optional(\Illuminate\Support\Carbon::make($blog->updated_at))->toAtomString() }}">
        <script type="application/ld+json">{!! \App\Helpers\StructuredData::toJson($blogSchema) !!}</script>
    @endif
@endpush

@push('css_after')
    <link rel="stylesheet" media="screen" href="{{ \App\Helpers\Asset::url('css/blog.css') }}">
    @if (! isset($blogs))
        <link rel="stylesheet" media="screen" href="{{ \App\Helpers\Asset::url('js/simple-lightbox.css') }}">
    @endif
@endpush

@section('content')
    <!-- Page Title-->
    <div class="blog-page-header bg-dark" style="background-image: url({{ asset('media/img/farmer.png') }});">
        <div class="container py-4 py-lg-5">
            <div class="mb-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-dark justify-content-center mb-0">
                        <li class="breadcrumb-item">
                            <a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}">
                                <i class="fa-solid fa-house me-1" aria-hidden="true"></i>{{ __('front.nav.home') }}
                            </a>
                        </li>
                        @if (isset($blogs))
                            <li class="breadcrumb-item active" aria-current="page">{{ __('front.blog.title') }}</li>
                        @else
                            <li class="breadcrumb-item">
                                <a href="{{ \App\Helpers\LocaleHelper::route('catalog.route.blog') }}">{{ __('front.blog.title') }}</a>
                            </li>
                            <li class="breadcrumb-item blog-breadcrumb-current active" aria-current="page">{{ $blog->title }}</li>
                        @endif
                    </ol>
                </nav>
            </div>

            <div class="blog-page-heading mx-auto text-center">
                @if (isset($blogs))
                    <h1>{{ __('front.blog.title') }}</h1>
                    <p class="mb-0">{{ __('front.blog.intro') }}</p>
                @else
                    <h1>{{ $blog->title }}</h1>
                @endif
            </div>
        </div>
    </div>

    @if(isset($blogs))
        <!-- Lista blogova -->
        <main class="blog-index container py-4 py-md-5">
            <div class="blog-grid masonry-grid" data-columns="3">
                @foreach ($blogs as $blog)
                    @php
                        $blogUrl = \App\Helpers\LocaleHelper::route('catalog.route.blog', ['blog' => $blog]);
                        $publishedAt = \Illuminate\Support\Carbon::make($blog->publish_date ?: $blog->created_at);
                        $cardImage = $blog->thumb ?: $blog->image;
                    @endphp
                    <article class="masonry-grid-item">
                        <div class="blog-card card">
                            <a class="blog-card-image" href="{{ $blogUrl }}" aria-label="{{ __('front.blog.read_article') }}: {{ $blog->title }}">
                                @if ($cardImage)
                                    <img
                                        src="{{ $cardImage }}"
                                        alt="{{ $blog->title }}"
                                        loading="{{ $loop->index < 3 ? 'eager' : 'lazy' }}"
                                        fetchpriority="{{ $loop->index < 2 ? 'high' : 'auto' }}"
                                        decoding="async"
                                        width="600">
                                @else
                                    <span class="blog-card-image-placeholder" aria-hidden="true">
                                        <i class="fa-regular fa-book-open"></i>
                                    </span>
                                @endif
                            </a>

                            <div class="blog-card-body card-body">
                                @if ($publishedAt)
                                    <time class="blog-card-date" datetime="{{ $publishedAt->toDateString() }}">
                                        <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                        {{ $publishedAt->locale(app()->getLocale())->format('d.m.Y.') }}
                                    </time>
                                @endif

                                <h2 class="blog-card-title">
                                    <a href="{{ $blogUrl }}">{{ $blog->title }}</a>
                                </h2>

                                @if ($blog->short_description)
                                    <p class="blog-card-description">{{ $blog->short_description }}</p>
                                @endif

                                <a class="blog-card-link" href="{{ $blogUrl }}">
                                    {{ __('front.blog.read_article') }}
                                    <i class="fa-regular fa-arrow-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @include('front.blog.partials.pagination', ['paginator' => $blogs])
        </main>
    @else
        <!-- Individualni blog -->
        @php
            $publishedAt = \Illuminate\Support\Carbon::make($blog->publish_date ?: $blog->created_at);
        @endphp
        <main class="blog-article-page container py-4 py-md-5">
            <article class="blog-article mx-auto">
                <header class="blog-article-intro">
                    <div class="blog-article-meta">
                        @if ($publishedAt)
                            <time datetime="{{ $publishedAt->toDateString() }}">
                                <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                {{ $publishedAt->locale(app()->getLocale())->format('d.m.Y.') }}
                            </time>
                            <span class="blog-meta-separator" aria-hidden="true"></span>
                        @endif
                        <span>
                            <i class="fa-regular fa-clock" aria-hidden="true"></i>
                            {{ __('front.blog.reading_time', ['minutes' => $readingMinutes]) }}
                        </span>
                    </div>

                    @if ($articleLead)
                        <p class="blog-article-lead">{{ $articleLead }}</p>
                    @endif
                </header>

                @if ($blog->image)
                    <figure class="blog-gallery blog-article-hero">
                        <a href="{{ $blog->image }}" aria-label="{{ $blog->title }}">
                            <img
                                src="{{ $blog->hero ?: $blog->image }}"
                                alt="{{ $blog->title }}"
                                loading="eager"
                                fetchpriority="high"
                                decoding="async">
                        </a>
                        <figcaption class="visually-hidden">{{ $blog->title }}</figcaption>
                    </figure>
                @endif

                <div class="blog-article-content">
                    {!! $blog->description !!}
                </div>

                <footer class="blog-article-footer">
                    <a class="btn btn-outline-primary" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.blog') }}">
                        <i class="fa-regular fa-arrow-left me-2" aria-hidden="true"></i>{{ __('front.blog.back_to_list') }}
                    </a>
                </footer>
            </article>

            @if ($recommendationProducts->isNotEmpty())
                <div class="blog-recommendations widget-product-carousel">
                    @include('front.catalog.product.partials.product-carousel', [
                        'products' => $recommendationProducts,
                        'title' => $recommendationTitle,
                        'headingId' => 'blog-recommendations-title',
                        'centerWhenShort' => true,
                    ])
                </div>
            @endif

            @if ($newerBlog || $olderBlog)
                <nav class="blog-article-navigation mx-auto" aria-label="{{ __('front.js.pagination.navigation') }}">
                    @if ($newerBlog)
                        <a class="blog-article-navigation-item blog-article-navigation-item--newer" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.blog', ['blog' => $newerBlog]) }}">
                            <i class="fa-regular fa-arrow-left" aria-hidden="true"></i>
                            <span>
                                <small>{{ __('front.blog.newer_article') }}</small>
                                <strong>{{ $newerBlog->title }}</strong>
                            </span>
                        </a>
                    @endif

                    @if ($olderBlog)
                        <a class="blog-article-navigation-item blog-article-navigation-item--older" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.blog', ['blog' => $olderBlog]) }}">
                            <span>
                                <small>{{ __('front.blog.older_article') }}</small>
                                <strong>{{ $olderBlog->title }}</strong>
                            </span>
                            <i class="fa-regular fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    @endif
                </nav>
            @endif
        </main>
    @endif

@endsection

@push('js_after')
    @if (! isset($blogs) && $blog->image)
        <script src="{{ \App\Helpers\Asset::url('js/simple-lightbox.js') }}"></script>
        <script>
            (function () {
                new SimpleLightbox('.blog-gallery a', {});
            })();
        </script>
    @endif
@endpush
