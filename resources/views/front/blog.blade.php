@extends('front.layouts.app')
@if(isset($blogs))
    @section ( 'title', 'Iz medija - Antikvarijat Biblos' )
    @section ( 'description', 'Medijske objave, članci i obavijesti -  Antikvarijat Biblos' )
@else
    @section ( 'title', $blog->title. ' - Antikvarijat Biblos' )
    @section ( 'description', $blog->meta_description )

@endif

@push('meta_tags')


    <link rel="stylesheet" media="screen" href="{{ asset('vendor/lightgallery/css/lightgallery-bundle.min.css')}}"/>

@endpush

@if (isset($gdl))
    @section('google_data_layer')
        <script type="application/ld+json">
                <?php echo json_encode($gdl); ?>
        </script>
    @endsection
@endif

@section('content')
    <!-- Page Title-->
    <div class=" bg-dark pt-4 pb-3" style="background-image: url({{ asset('media/img/farmer.png')  }});background-repeat: repeat">
        <div class="container d-lg-block justify-content-end py-2 py-lg-3">
            <div class="order-lg-2 mb-3 mb-lg-0 pb-lg-2">

                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-dark flex-lg-nowrap justify-content-center ">
                        <li class="breadcrumb-item"><a class="text-nowrap" href="{{ route('index') }}"><i class="ci-home"></i>Naslovnica</a></li>
                           @if(isset($blogs))
                     <li class="breadcrumb-item text-nowrap active" aria-current="page">Iz medija</li>
                @else
              
                      <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="https://www.antikvarijat-biblos.hr/blog">Iz medija</a></li>
                @endif
                        
                    </ol>
                </nav>

            </div>
            <div class="order-lg-1 pe-lg-4 text-center ">
                @if(isset($blogs))
                    <h1 class="h3 text-dark">Iz medija</h1>
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
                                <a class="blog-entry-thumb" href="{{ route('catalog.route.blog', ['blog' => $blog]) }}">
                                    <img
                                        class="card-img-top"
                                        src="{{ $blog->image }}"
                                        alt="{{ $blog->title }}"
                                        loading="{{ $loop->index < 3 ? 'eager' : 'lazy' }}"
                                        fetchpriority="{{ $loop->index < 2 ? 'high' : 'auto' }}"
                                        decoding="async"
                                        width="400"
                                        height="230">
                                </a>
                                <div class="card-body">
                                    <h2 class="h6 blog-entry-title"><a href="{{ route('catalog.route.blog', ['blog' => $blog]) }}">{{ $blog->title }}</a></h2>
                                    <p class="fs-sm">{{ $blog->short_description }}</p>
                                </div>
                                <div class="card-footer d-flex align-items-left fs-xs">
                                    <div class="me-auto text-nowrap"><a class="blog-entry-meta-link text-nowrap" href="{{ route('catalog.route.blog', ['blog' => $blog]) }}">{{ \Carbon\Carbon::make($blog->created_at)->locale('hr')->format('d.m.Y.') }}</a></div>
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
                    <div class="gallery row pb-2">
                        <div class="col-sm-12">
                            <a class="gallery-item rounded-3 mb-grid-gutter" href="{{ asset($blog->image) }}" data-bs-sub-html="&lt;h6 class=&quot;fs-sm text-light&quot;&gt;Gallery image caption #1&lt;/h6&gt;">
                                <img
                                    src="{{ asset($blog->image) }}"
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

  
    <link rel="stylesheet" media="screen" href="{{ asset('js/simple-lightbox.css?v2.14.0') }}">
    <script src="{{ asset('js/simple-lightbox.js?v2.14.0') }}"></script>


   
    <script>
        (function () {
            var $gallery = new SimpleLightbox('.gallery a', {});
        })();
    </script>
 



@endpush
