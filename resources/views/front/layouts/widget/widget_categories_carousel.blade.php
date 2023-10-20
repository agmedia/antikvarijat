<!-- {"title": "Categories Carousel", "description": "Some description of a Categories Carousel widget template."} -->
<section class=" mb-0 pb-5 py-5" style="background-image: url({{ (isset($data['background']) && $data['background']) ? url('cache/image?src=media/img/glag.png') : '' }});-webkit-background-size: cover;-moz-background-size: cover;-o-background-size: cover;background-size: cover;">
    <div class="container py-lg-3">
        <h2 class="h3 text-center">{{ $data['title'] }}</h2>
        <p class="text-muted-light text-center mb-3 pb-4">{{ $data['subtitle'] }}</p>
        <div class="tns-carousel pb-5">
            <div class="tns-carousel-inner" data-carousel-options="{&quot;items&quot;: 2, &quot;gutter&quot;: 15, &quot;controls&quot;: false, &quot;autoplay&quot;: true, &quot;autoplayTimeout&quot;: 2000, &quot;nav&quot;: true, &quot;responsive&quot;: {&quot;0&quot;:{&quot;items&quot;:1},&quot;500&quot;:{&quot;items&quot;:2},&quot;768&quot;:{&quot;items&quot;:3}, &quot;992&quot;:{&quot;items&quot;:4,&quot;gutter&quot;: 30}}}">
            @foreach ($data['items'] as $item)
                <!-- Product-->
                    <div >
                        <div class="card border-0 shadow-lg"><a class="blog-entry-thumb" href="{{ route('catalog.route', ['group' => $item]) }}"><img class="card-img-top" load="lazy" src="{{ $item['image'] }}" width="400" height="230" alt="{{ $item['title'] }}"></a>

                            <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); width:100%;">
                                <div class="card-body text-center">
                                    <h2 class="h5 blog-entry-title mb-32"><a href="{{ route('catalog.route.blog', ['blog' => $item]) }}">{{ $item['title'] }}</a></h2>
                                    <a href="{{ route('catalog.route', ['group' => $item]) }}" class="btn btn-primary btn-sm btn-shadow">Pogledajte kategoriju <i class="ci-arrow-right"></i></a>
                                </div>

                           </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @if($data['url'] !='/')
            <p class="text-center"><a class="btn btn-primary btn-shadow" href="{{ url($data['url']) }}">Pogledajte sve {{ $data['title'] }} <i class="ci-arrow-right"></i></a></p>
        @endif
    </div>
</section>
