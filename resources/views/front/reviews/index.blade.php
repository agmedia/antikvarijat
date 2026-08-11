@extends('front.layouts.app')

@section('title', __('front.reviews.index_meta_title'))
@section('description', __('front.reviews.index_meta_description'))
@section('schema_page_type', 'CollectionPage')

@push('css_after')
    <link rel="stylesheet" media="screen" href="{{ \App\Helpers\Asset::url('css/reviews.css') }}">
@endpush

@section('content')
    <header class="reviews-page-header" style="background-image: url({{ asset('media/img/farmer.png') }});">
        <div class="container py-4 py-lg-5">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-dark justify-content-center mb-0">
                    <li class="breadcrumb-item">
                        <a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}">
                            <i class="fa-solid fa-house me-1" aria-hidden="true"></i>{{ __('front.nav.home') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('front.reviews.index_title') }}</li>
                </ol>
            </nav>

            <div class="reviews-page-heading mx-auto text-center">
                <h1>{{ __('front.reviews.index_title') }}</h1>
                <p>{{ __('front.reviews.index_intro') }}</p>

                @if ($reviewCount > 0)
                    <div class="reviews-summary" aria-label="{{ __('front.reviews.rating_summary', ['rating' => number_format($averageRating, 1, ',', '.'), 'count' => $reviewCount]) }}">
                        <strong>{{ number_format($averageRating, 1, ',', '.') }}</strong>
                        <span class="reviews-summary-stars" aria-hidden="true">
                            @for ($star = 1; $star <= 5; $star++)
                                <i class="fa-solid fa-star"></i>
                            @endfor
                        </span>
                        <span>{{ trans_choice('front.reviews.count', $reviewCount, ['count' => number_format($reviewCount, 0, ',', '.')]) }}</span>
                    </div>
                @endif
            </div>
        </div>
    </header>

    <main class="reviews-index container py-4 py-md-5">
        @if ($reviews->count())
            <div class="reviews-grid">
                @foreach ($reviews as $review)
                    @php
                        $productName = $review->product
                            ? \App\Helpers\LocaleHelper::localizedField($review->product, 'name')
                            : null;
                        $productPath = null;
                        $productImage = null;

                        if ($review->product) {
                            $productPath = \App\Helpers\LocaleHelper::isEnglish()
                                ? ($review->product->getRawOriginal('url_en') ?: \App\Helpers\LocaleHelper::localizedUrl($review->product->getRawOriginal('url')))
                                : $review->product->getRawOriginal('url');

                            $productImagePath = $review->product->thumb ?: $review->product->getRawOriginal('image');
                            if ($productImagePath) {
                                $productImage = \Illuminate\Support\Str::startsWith($productImagePath, ['http://', 'https://'])
                                    ? $productImagePath
                                    : rtrim(config('settings.images_domain'), '/') . '/' . ltrim($productImagePath, '/');
                            }
                        }
                    @endphp
                    <article class="review-card" id="review-{{ $review->id }}">
                        <div class="review-card-main">
                            @if ($productImage && $productPath)
                                <a class="review-card-cover" href="{{ url($productPath) }}" aria-label="{{ $productName }}">
                                    <img src="{{ $productImage }}" width="82" height="112" loading="lazy" alt="{{ $productName }}">
                                </a>
                            @elseif ($productImage)
                                <span class="review-card-cover">
                                    <img src="{{ $productImage }}" width="82" height="112" loading="lazy" alt="{{ $productName }}">
                                </span>
                            @endif

                            <div class="review-card-copy">
                                <div class="review-card-rating" aria-label="{{ __('front.reviews.rating_option', ['rating' => $review->rating]) }}">
                                    @for ($star = 1; $star <= 5; $star++)
                                        <i class="fa-{{ $star <= $review->rating ? 'solid' : 'regular' }} fa-star" aria-hidden="true"></i>
                                    @endfor
                                </div>

                                @if ($review->title)
                                    <h2>{{ $review->title }}</h2>
                                @endif

                                <p class="review-card-body">{{ $review->body }}</p>
                            </div>
                        </div>

                        <footer class="review-card-footer">
                            <div>
                                <strong>{{ $review->reviewer_name }}</strong>
                                @if ($review->is_verified_purchase)
                                    <span class="review-verified">
                                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>{{ __('front.reviews.verified_purchase') }}
                                    </span>
                                @endif
                            </div>

                            @if ($productName && $productPath)
                                <a class="review-product-link" href="{{ url($productPath) }}">
                                    <i class="fa-duotone fa-book-open" aria-hidden="true"></i>
                                    <span>{{ __('front.reviews.review_for') }}: {{ $productName }}</span>
                                </a>
                            @endif
                        </footer>
                    </article>
                @endforeach
            </div>

            <div class="reviews-pagination">
                {{ $reviews->onEachSide(1)->links('vendor.pagination.catalog') }}
                <p class="reviews-pagination-summary mb-0">
                    {{ __('front.reviews.pagination_summary', [
                        'from' => number_format($reviews->firstItem(), 0, ',', '.'),
                        'to' => number_format($reviews->lastItem(), 0, ',', '.'),
                        'total' => number_format($reviews->total(), 0, ',', '.'),
                    ]) }}
                </p>
            </div>
        @else
            <div class="reviews-empty text-center">
                <i class="fa-duotone fa-comments" aria-hidden="true"></i>
                <p class="mb-0">{{ __('front.reviews.index_empty') }}</p>
            </div>
        @endif
    </main>
@endsection
