@php
    $cardReviewCount = (int) ($product->approved_reviews_count ?? 0);
    $cardReviewAverage = (float) ($product->approved_reviews_average ?? 0);
    $cardReviewAverageFormatted = number_format(
        $cardReviewAverage,
        1,
        app()->getLocale() === 'hr' ? ',' : '.',
        ''
    );
@endphp

@if ($cardReviewCount > 0)
    <a
        class="d-inline-flex align-items-center gap-1 mb-2 text-decoration-none"
        href="{{ url($product->url) }}#reviews"
        aria-label="{{ __('front.reviews.rating_summary', ['rating' => $cardReviewAverageFormatted, 'count' => $cardReviewCount]) }}">
        <span class="star-rating" aria-hidden="true">
            @for ($star = 1; $star <= 5; $star++)
                <i class="star-rating-icon {{ $star <= round($cardReviewAverage) ? 'ci-star-filled active' : 'ci-star' }}"></i>
            @endfor
        </span>
        <span class="fs-xs text-muted">{{ $cardReviewAverageFormatted }} ({{ $cardReviewCount }})</span>
    </a>
@endif
