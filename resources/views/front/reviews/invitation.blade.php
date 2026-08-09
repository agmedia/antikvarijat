@extends('front.layouts.app')

@section('title', __('front.reviews.request_title'))
@section('description', __('front.reviews.request_intro', ['order' => $invitation->order_id]))
@section('robots', 'noindex,nofollow,noarchive')

@section('content')
    <div class="container py-5">
        <div class="mx-auto" style="max-width: 920px;">
            @include('back.layouts.partials.session')

            <div class="text-center mb-5">
                <div class="text-warning h2 mb-3" aria-hidden="true">★★★★★</div>
                <h1 class="h2">{{ __('front.reviews.request_title') }}</h1>
                <p class="text-muted mb-0">{{ __('front.reviews.request_intro', ['order' => $invitation->order_id]) }}</p>
            </div>

            @if ($items->isEmpty())
                <div class="alert alert-info">{{ __('front.reviews.no_items') }}</div>
            @elseif ($items->every(fn ($item) => $item->review_submitted))
                <div class="alert alert-success text-center">{{ __('front.reviews.all_reviewed') }}</div>
            @endif

            @foreach ($items as $item)
                @php
                    $product = $item->product;
                    $isEnglish = app()->getLocale() === 'en';
                    $productName = $isEnglish && $product->name_en ? $product->name_en : $product->name;
                    $image = $product->image ? config('settings.images_domain') . ltrim($product->image, '/') : null;
                @endphp
                <section class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start mb-3">
                            @if ($image)
                                <img src="{{ $image }}" alt="" width="72" height="96" class="rounded me-3" style="object-fit: cover;">
                            @endif
                            <div>
                                <h2 class="h5 mb-1">{{ $productName }}</h2>
                                <span class="badge bg-success">{{ __('front.reviews.verified_purchase') }}</span>
                            </div>
                        </div>

                        @if ($item->review_submitted)
                            <div class="alert alert-success mb-0">{{ __('front.reviews.reviewed') }}</div>
                        @else
                            <form method="POST" action="{{ $formAction }}">
                                @csrf
                                <input type="hidden" name="order_product_id" value="{{ $item->id }}">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label" for="rating-{{ $item->id }}">{{ __('front.reviews.rating') }} *</label>
                                        <select class="form-select" id="rating-{{ $item->id }}" name="rating" required>
                                            <option value="">—</option>
                                            @for ($rating = 5; $rating >= 1; $rating--)
                                                <option value="{{ $rating }}">{{ $rating }} / 5</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label" for="title-{{ $item->id }}">{{ __('front.reviews.optional_title') }}</label>
                                        <input class="form-control" id="title-{{ $item->id }}" name="title" maxlength="191">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="body-{{ $item->id }}">{{ __('front.reviews.comment') }} *</label>
                                    <textarea class="form-control" id="body-{{ $item->id }}" name="body" rows="4" minlength="10" maxlength="5000" required></textarea>
                                </div>
                                <button class="btn btn-primary" type="submit">{{ __('front.reviews.review_item') }}</button>
                            </form>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    </div>
@endsection
