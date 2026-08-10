@extends('front.layouts.app')
@if(isset($blogs))
    @section ( 'title', __('front.blog.meta_title') )
    @section ( 'description', __('front.blog.meta_description') )
    @section('schema_page_type', 'CollectionPage')
@else
    @section('title', $blog->meta_title ?: $blog->title . ' - Antikvarijat Biblos')
    @section('description', $blog->meta_description ?: $blog->short_description)
    @section('og_type', 'article')
    @section('og_image', $blog->image)
    @section('og_image_alt', $blog->title)

@endif

@push('meta_tags')
    @if (isset($blogs))
        <script src="{{ \App\Helpers\Asset::url('js/imagesloaded/imagesloaded.pkgd.min.js') }}"></script>
        <script src="{{ \App\Helpers\Asset::url('js/shufflejs/dist/shuffle.min.js') }}"></script>
    @else
        <meta property="article:published_time" content="{{ optional(\Illuminate\Support\Carbon::make($blog->publish_date ?: $blog->created_at))->toAtomString() }}">
        <meta property="article:modified_time" content="{{ optional(\Illuminate\Support\Carbon::make($blog->updated_at))->toAtomString() }}">
        <script type="application/ld+json">{!! \App\Helpers\StructuredData::toJson($blogSchema) !!}</script>
    @endif
@endpush

@push('css_after')
    @if (! isset($blogs))
        <link rel="stylesheet" media="screen" href="{{ \App\Helpers\Asset::url('js/simple-lightbox.css') }}">
    @endif
@endpush

@section('content')
    <!-- Page Title-->
    <div class=" bg-dark pt-4 pb-3" style="background-image: url({{ asset('media/img/farmer.png')  }});background-repeat: repeat">
        <div class="container d-lg-block justify-content-end py-2 py-lg-3">
            <div class="order-lg-2 mb-3 mb-lg-0 pb-lg-2">

                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-dark flex-lg-nowrap justify-content-center ">
                        <li class="breadcrumb-item"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}"><i class="fa-solid fa-house"></i>{{ __('front.nav.home') }}</a></li>
                           @if(isset($blogs))
                     <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ __('front.blog.title') }}</li>
                @else
              
                      <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.blog') }}">{{ __('front.blog.title') }}</a></li>
                @endif
                        
                    </ol>
                </nav>

            </div>
            <div class="order-lg-1 pe-lg-4 text-center ">
                @if(isset($blogs))
                    <h1 class="h3 text-dark">{{ __('front.blog.title') }}</h1>
                @else
                    <h1 class="h3 text-dark">{{ $blog->title }}</h1>
                @endif
            </div>
        </div>
    </div>

    @if(isset($blogs))
        <!-- Lista blogova -->
        <div class="container pb-5 mb-2 mb-md-4">
            <div class="pt-5 mt-md-2">
                <!-- Entries grid-->
                <div class="masonry-grid" data-columns="3">
                    @foreach ($blogs as $blog)
                        <article class="masonry-grid-item">
                            <div class="card">
                                <a class="blog-entry-thumb" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.blog', ['blog' => $blog]) }}">
                                    <img
                                        class="card-img-top"
                                        src="{{ $blog->thumb ?: $blog->image }}"
                                        alt="{{ $blog->title }}"
                                        loading="{{ $loop->index < 3 ? 'eager' : 'lazy' }}"
                                        fetchpriority="{{ $loop->index < 2 ? 'high' : 'auto' }}"
                                        decoding="async"
                                        width="400">
                                </a>
                                <div class="card-body">
                                    <h2 class="h6 blog-entry-title"><a href="{{ \App\Helpers\LocaleHelper::route('catalog.route.blog', ['blog' => $blog]) }}">{{ $blog->title }}</a></h2>
                                    <p class="fs-sm">{{ $blog->short_description }}</p>
                                </div>
                                <div class="card-footer d-flex align-items-left fs-xs">
                                    <div class="me-auto text-nowrap"><a class="blog-entry-meta-link text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.blog', ['blog' => $blog]) }}">{{ \Carbon\Carbon::make($blog->created_at)->locale(app()->getLocale())->format('d.m.Y.') }}</a></div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="row py-md-3">
                    <div class="col-lg-12">
                        {{ $blogs->links() }}
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Individualni blog -->
        <div class="container pb-5">
            <div class="row justify-content-center pt-5 mt-md-2">
                <div class="col-lg-9">
                    <div class="blog-gallery row pb-2">
                        <div class="col-sm-12">
                            <a class="gallery-item rounded-3 mb-grid-gutter" href="{{ $blog->image }}" data-bs-sub-html="&lt;h6 class=&quot;fs-sm text-light&quot;&gt;Gallery image caption #1&lt;/h6&gt;">
                                <img
                                    class="img-fluid rounded-3"
                                    src="{{ $blog->hero ?: $blog->image }}"
                                    alt="{{ $blog->title }}"
                                    loading="eager"
                                    fetchpriority="high"
                                    decoding="async">
                                <span class="gallery-item-caption">{{ $blog->title }}</span>
                            </a>
                        </div>
                    </div>
                    {!! $blog->description !!}
                </div>
            </div>
        </div>
    @endif

@endsection

@push('js_after')
    @if (! isset($blogs))
        <script src="{{ \App\Helpers\Asset::url('js/simple-lightbox.js') }}"></script>
        <script>
            (function () {
                var $gallery = new SimpleLightbox('.blog-gallery a', {});
            })();
        </script>
    @endif
@endpush
