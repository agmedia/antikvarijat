@if ($products->isNotEmpty())
    @php
        $carouselOptions = [
            'items' => 2,
            'controls' => false,
            'nav' => true,
            'loop' => false,
            'responsive' => [
                0 => ['items' => 1.35, 'gutter' => 12],
                420 => ['items' => 2, 'gutter' => 12],
                768 => ['items' => 3, 'gutter' => 16],
                992 => ['items' => 4, 'controls' => $products->count() > 4, 'gutter' => 18],
                1200 => ['items' => 5, 'controls' => $products->count() > 5, 'gutter' => 18],
            ],
        ];
    @endphp

    <section class="cart-best-sellers" aria-labelledby="cart-best-sellers-title">
        <div class="container">
            <header class="cart-best-sellers__header">
                <span class="cart-best-sellers__eyebrow">
                    <i class="fa-regular fa-sparkles" aria-hidden="true"></i>
                    {{ __('front.checkout.best_sellers_eyebrow') }}
                </span>
                <h2 class="cart-best-sellers__title font-title" id="cart-best-sellers-title">
                    {{ __('front.checkout.best_sellers_title') }}
                </h2>
                <p class="cart-best-sellers__subtitle">{{ __('front.checkout.best_sellers_subtitle') }}</p>
            </header>

            <div class="tns-carousel tns-controls-static tns-controls-outside tns-nav-enabled cart-best-sellers__carousel">
                <div class="tns-carousel-inner tns-nav-enabled" data-carousel-options='@json($carouselOptions)'>
                    @foreach ($products as $product)
                        @php
                            $author = $product->author;
                            $showsAuthor = $author && \App\Models\Front\Catalog\Author::hasMeaningfulTitle($author->title);
                        @endphp
                        <div class="cart-best-sellers__slide">
                            <article class="cart-best-sellers__card">
                                <a class="cart-best-sellers__cover" href="{{ url($product->url) }}" aria-label="{{ __('front.checkout.best_sellers_open', ['name' => $product->name]) }}">
                                    <span class="cart-best-sellers__rank" aria-hidden="true">{{ $loop->iteration }}</span>
                                    <img src="{{ $product->thumb }}"
                                         width="220"
                                         height="290"
                                         loading="lazy"
                                         decoding="async"
                                         alt="{{ $product->name }}">
                                </a>

                                <div class="cart-best-sellers__body">
                                    @if ($showsAuthor)
                                        <a class="cart-best-sellers__author" href="{{ url($author->url) }}">{{ $author->title }}</a>
                                    @endif

                                    <h3 class="cart-best-sellers__product-title">
                                        <a href="{{ url($product->url) }}">{{ $product->name }}</a>
                                    </h3>

                                    <span class="cart-best-sellers__badge">
                                        <i class="fa-solid fa-chart-line-up" aria-hidden="true"></i>
                                        {{ __('front.checkout.best_sellers_badge') }}
                                    </span>

                                    <div class="cart-best-sellers__purchase">
                                        <div class="cart-best-sellers__price">
                                            @if ($product->main_price > $product->main_special)
                                                <del>{{ $product->main_price_text }}</del>
                                                <strong>{{ $product->main_special_text }}</strong>
                                            @else
                                                <strong>{{ $product->main_price_text }}</strong>
                                            @endif
                                        </div>

                                        <add-to-cart-btn-simple
                                            id="{{ $product->id }}"
                                            label="{{ __('front.checkout.best_sellers_add') }}">
                                        </add-to-cart-btn-simple>
                                    </div>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
