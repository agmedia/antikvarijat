@extends('front.layouts.app')

@section('content')
    @include('front.customer.layouts.header')

    <section class="account-page container pb-5 mb-2 mb-md-4">
        <div class="row g-4">
            @include('front.customer.layouts.sidebar')

            <section class="col-lg-8 col-xl-9">
                <div class="account-card account-content-card">
                    <div class="account-content-header">
                        <div class="account-content-heading">
                            <span class="account-content-icon"><i class="fa-duotone fa-star" aria-hidden="true"></i></span>
                            <div>
                                <h2 class="account-content-title">{{ __('front.account.reviews') }}</h2>
                                <p class="account-content-subtitle">{{ __('front.account.reviews_subtitle') }}</p>
                            </div>
                        </div>
                    </div>

                    @include('front.layouts.partials.session')

                    <div class="account-summary-grid">
                        <div class="account-summary-item">
                            <div class="account-summary-item__label">{{ __('front.account.reviews_approved') }}</div>
                            <div class="account-summary-item__value">{{ $approvedReviewsCount }}</div>
                        </div>
                        <div class="account-summary-item">
                            <div class="account-summary-item__label">{{ __('front.account.reviews_pending') }}</div>
                            <div class="account-summary-item__value">{{ $pendingReviewsCount }}</div>
                        </div>
                        <div class="account-summary-item">
                            <div class="account-summary-item__label">{{ __('front.account.reviews_rejected') }}</div>
                            <div class="account-summary-item__value">{{ $rejectedReviewsCount }}</div>
                        </div>
                    </div>

                    @if($pendingProducts->isNotEmpty())
                        <section class="mb-4">
                            <h3 class="account-section-title"><i class="fa-duotone fa-books" aria-hidden="true"></i>{{ __('front.account.reviews_waiting') }}</h3>
                            <div class="account-review-prompt-grid">
                                @foreach($pendingProducts as $orderProduct)
                                    @php
                                        $pendingProduct = $orderProduct->real;
                                        $pendingProductUrl = $pendingProduct ? url($pendingProduct->url) . '#review-form' : null;
                                        $pendingProductImage = $pendingProduct ? $pendingProduct->thumb : null;
                                    @endphp
                                    <article class="account-review-prompt">
                                        <img class="account-review-prompt__image" src="{{ $pendingProductImage ?: asset('media/avatars/avatar0.jpg') }}" alt="{{ $orderProduct->name }}">
                                        <div class="min-w-0">
                                            <a class="account-review-prompt__name" href="{{ $pendingProductUrl }}">{{ $orderProduct->name }}</a>
                                            <a class="btn btn-outline-primary btn-sm mt-2" href="{{ $pendingProductUrl }}">{{ __('front.account.review_write') }}</a>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <section>
                        <h3 class="account-section-title"><i class="fa-solid fa-star" aria-hidden="true"></i>{{ __('front.account.reviews_yours') }}</h3>

                        @forelse($reviews as $review)
                            @php
                                $reviewProduct = $review->product;
                                $reviewUrl = $reviewProduct && $reviewProduct->url ? url($reviewProduct->url) . '#reviews' : null;
                                $reviewImage = $reviewProduct && $reviewProduct->image
                                    ? (\Illuminate\Support\Str::startsWith($reviewProduct->image, ['http://', 'https://'])
                                        ? $reviewProduct->image
                                        : rtrim(config('settings.images_domain'), '/') . '/' . ltrim($reviewProduct->image, '/'))
                                    : asset('media/avatars/avatar0.jpg');
                                $reviewStatusClass = $review->status === \App\Models\ProductReview::STATUS_APPROVED
                                    ? 'account-status--approved'
                                    : ($review->status === \App\Models\ProductReview::STATUS_REJECTED ? 'account-status--rejected' : '');
                                $reviewStatusLabel = $review->status === \App\Models\ProductReview::STATUS_APPROVED
                                    ? __('front.account.review_approved')
                                    : ($review->status === \App\Models\ProductReview::STATUS_REJECTED
                                        ? __('front.account.review_rejected')
                                        : __('front.account.review_pending'));
                            @endphp
                            <article class="account-review-card">
                                @if($reviewUrl)<a href="{{ $reviewUrl }}">@endif
                                    <img class="account-review-card__cover" src="{{ $reviewImage }}" alt="{{ optional($reviewProduct)->name ?: $review->title }}">
                                @if($reviewUrl)</a>@endif

                                <div>
                                    @if($reviewUrl)
                                        <a class="account-review-card__product" href="{{ $reviewUrl }}">{{ $reviewProduct->name }}</a>
                                    @else
                                        <span class="account-review-card__product">{{ optional($reviewProduct)->name ?: __('front.nav.product') }}</span>
                                    @endif
                                    <div class="account-review-card__meta">
                                        {{ \Illuminate\Support\Carbon::make($review->created_at)->format('d.m.Y') }}
                                        <span class="account-review-card__stars ms-2" aria-label="{{ $review->rating }}/5">
                                            @for($star = 1; $star <= 5; $star++){{ $star <= $review->rating ? '★' : '☆' }}@endfor
                                        </span>
                                    </div>
                                    @if($review->title)<div class="account-review-card__title">{{ $review->title }}</div>@endif
                                    <p class="account-review-card__body">{{ $review->body }}</p>
                                </div>

                                <div class="account-review-card__status">
                                    <span class="account-status {{ $reviewStatusClass }}">{{ $reviewStatusLabel }}</span>
                                </div>
                            </article>
                        @empty
                            <div class="account-empty">
                                <div>
                                    <i class="fa-duotone fa-star d-block fs-3 mb-3" aria-hidden="true"></i>
                                    <p class="mb-3">{{ __('front.account.reviews_empty') }}</p>
                                    <a class="btn btn-primary btn-sm" href="{{ \App\Helpers\LocaleHelper::route('moje-narudzbe') }}">{{ __('front.account.orders') }}</a>
                                </div>
                            </div>
                        @endforelse

                        <div class="mt-4">{{ $reviews->links() }}</div>
                    </section>
                </div>
            </section>
        </div>
    </section>
@endsection
