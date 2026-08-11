<!-- {"title": "Page Carousel", "description": "Category, Publisher, Reviews."} -->
@if ($data['tablename'] !== 'reviews' || collect($data['items'])->isNotEmpty())
 @if ($data['tablename'] == 'reviews')
 <section class="reviews-widget py-5" style="background-image: url({{ asset('media/img/farmer.png') }});">
 @else

         <section class=" py-0 ">
@endif

    <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center pt-1  pb-3 mb-2">
        <h2 class="h3 mb-0 pt-0 font-title me-3 "><span class="border-color"> {{ $data['title'] }}</span></h2>
        @if ($data['tablename'] == 'blog')
        <a class="btn btn-outline-primary btn-sm btn-shadow mt-0" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.blog') }}"><span class="d-none d-sm-inline-block">{{ __('front.widgets.view_all') }}</span> <i class="fa-solid fa-arrow-right fs-xs "></i></a>
        @endif

        @if ($data['tablename'] == 'reviews')
            <a class="btn btn-outline-primary btn-sm btn-shadow mt-0" href="{{ \App\Helpers\LocaleHelper::route('reviews.index') }}">
                <span class="d-none d-sm-inline-block">{{ __('front.widgets.all_reviews') }}</span>
                <i class="fa-solid fa-arrow-right fs-xs" aria-hidden="true"></i>
            </a>
        @endif
    </div>

    @if ($data['tablename'] == 'category')
            <div class="tns-carousel">
                <div class="tns-carousel-inner" data-carousel-options='{"items": 2, "controls": true, "autoHeight": false, "responsive": {"0":{"items":2, "gutter": 10},"480":{"items":2, "gutter": 10},"800":{"items":3, "gutter": 15}, "1300":{"items":4, "gutter": 20}, "1400":{"items":5, "gutter": 20}}}'>
                @foreach ($data['items'] as $item)
                    <!-- Product-->
                        <div class="article mb-grid-gutter">
                            <a class="card border-0 shadow" href="{{ \App\Helpers\LocaleHelper::route('catalog.route', ['group' => $item->getRawOriginal('group'), 'cat' => $item]) }}">
                                <span class="blog-entry-meta-label fs-sm"><i class="fa-duotone fa-books text-primary me-0"></i></span>
                                <img class="card-img-top" loading="lazy" width="400" height="300" src="{{ $item['image'] }}" alt="{{ __('front.widgets.category_alt', ['title' => $item['title']]) }}">
                                <div class="card-body py-2 text-center px-0">
                                    <h3 class="h6 mt-1 font-title text-primary">{{ $item['title'] }}</h3>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

    @elseif ($data['tablename'] == 'author')


        <div class="tns-carousel">
            <div class="tns-carousel-inner" data-carousel-options='{"items": 2, "controls": true, "autoHeight": false, "responsive": {"0":{"items":2, "gutter": 10},"480":{"items":2, "gutter": 10},"800":{"items":3, "gutter": 20}, "1200":{"items":4, "gutter": 30}, "1500":{"items":5, "gutter": 30}}}'>
                @foreach ($data['items'] as $item)
                    <div class="col-md-3 col-sm-4 col-6"><a class="d-block bg-white shadow-sm rounded-3 py-3 py-sm-4 mb-grid-gutter" href="{{ $item['url'] }}"><img loading="lazy" class="d-block mx-auto" src="{{ $item['image'] }}" style="width: 150px;" alt="{{ $item['title'] }}"></a></div>
                @endforeach
            </div>
        </div>

    @elseif ($data['tablename'] == 'reviews')

        <div class="tns-carousel reviews-widget__carousel pb-4">
            <div class="tns-carousel-inner" data-carousel-options='{"items": 1, "controls": false, "nav": true, "autoplay": true, "autoHeight": false, "responsive": {"0":{"items":1, "gutter":20},"576":{"items":2, "gutter":24},"992":{"items":3, "gutter":24}}}'>
                @foreach ($data['items'] as $review)
                    @php
                        $widgetProduct = $review->product;
                        $widgetProductName = $widgetProduct
                            ? \App\Helpers\LocaleHelper::localizedField($widgetProduct, 'name')
                            : null;
                        $widgetProductPath = null;
                        $widgetProductImage = null;

                        if ($widgetProduct) {
                            $widgetProductPath = \App\Helpers\LocaleHelper::isEnglish()
                                ? ($widgetProduct->getRawOriginal('url_en') ?: \App\Helpers\LocaleHelper::localizedUrl($widgetProduct->getRawOriginal('url')))
                                : $widgetProduct->getRawOriginal('url');
                            $widgetImagePath = $widgetProduct->thumb ?: $widgetProduct->getRawOriginal('image');

                            if ($widgetImagePath) {
                                $widgetProductImage = \Illuminate\Support\Str::startsWith($widgetImagePath, ['http://', 'https://'])
                                    ? $widgetImagePath
                                    : rtrim(config('settings.images_domain'), '/') . '/' . ltrim($widgetImagePath, '/');
                            }
                        }
                    @endphp

                    <div class="reviews-widget__slide">
                    <blockquote class="reviews-widget__card mb-0">
                        <div class="reviews-widget__main">
                            @if ($widgetProductImage && $widgetProductPath)
                                <a class="reviews-widget__cover" href="{{ url($widgetProductPath) }}" aria-label="{{ $widgetProductName }}">
                                    <img src="{{ $widgetProductImage }}" width="82" height="112" loading="lazy" alt="{{ $widgetProductName }}">
                                </a>
                            @endif

                            <div class="reviews-widget__copy">
                                <div class="star-rating reviews-widget__rating" aria-label="{{ __('front.reviews.rating_option', ['rating' => $review->rating]) }}">
                                    @for ($i = 0; $i < 5; $i++)
                                        <i class="star-rating-icon fa-{{ $review->rating - $i >= 1 ? 'solid' : 'regular' }} fa-star{{ $review->rating - $i >= 1 ? ' active' : '' }}" aria-hidden="true"></i>
                                    @endfor
                                </div>

                                @if ($review->title)
                                    <strong class="reviews-widget__title">{{ $review->title }}</strong>
                                @endif
                                <p class="reviews-widget__text">{{ $review->body }}</p>
                            </div>
                        </div>

                        <footer class="reviews-widget__footer">
                            <div class="reviews-widget__author">
                                <strong>{{ $review->reviewer_name }}</strong>
                                @if ($review->is_verified_purchase)
                                    <small><i class="fa-solid fa-circle-check" aria-hidden="true"></i>{{ __('front.reviews.verified_purchase') }}</small>
                                @endif
                            </div>

                            @if ($widgetProductName && $widgetProductPath)
                                <a class="reviews-widget__product" href="{{ url($widgetProductPath) }}">
                                    <i class="fa-duotone fa-book-open" aria-hidden="true"></i>
                                    <span>{{ __('front.reviews.review_for') }}: {{ $widgetProductName }}</span>
                                </a>
                            @endif
                        </footer>
                    </blockquote>
                    </div>
                @endforeach
            </div>
        </div>

    @else
        <div class="tns-carousel pb-5">
            <div class="tns-carousel-inner" data-carousel-options="{&quot;items&quot;: 2, &quot;gutter&quot;: 15, &quot;controls&quot;: false, &quot;nav&quot;: true, &quot;responsive&quot;: {&quot;0&quot;:{&quot;items&quot;:1},&quot;500&quot;:{&quot;items&quot;:2},&quot;768&quot;:{&quot;items&quot;:2}, &quot;992&quot;:{&quot;items&quot;:3, &quot;gutter&quot;: 30}}}">
                @foreach ($data['items'] as $item)

                    <!-- Product-->
                    <div>
                        <div class="card product-card  shadow mb-3 pb-2"><a class="blog-entry-thumb" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.blog', ['blog' => $item]) }}"><img class="card-img-top" loading="lazy" src="{{ $item['image'] }}" width="400" height="230" alt="{{ $item['title'] }}" style="width: 100%;height: 260px;object-fit: cover;margin:0 auto"></a>
                            <div class="card-body">
                                <h2 class="h6 blog-entry-title"><a href="{{ \App\Helpers\LocaleHelper::route('catalog.route.blog', ['blog' => $item]) }}">{{ $item['title'] }}</a></h2>
                                <p class="fs-sm"> {!! Str::limit($item['short_description'], 180, ' ...') !!}</p>
                                <div class="fs-xs text-nowrap"><a class="blog-entry-meta-link text-nowrap" href="#">{{ \Carbon\Carbon::make($item['created_at'])->locale(app()->getLocale())->format('d.m.Y.') }}</a></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    @endif
    </div>

</section>
@endif
