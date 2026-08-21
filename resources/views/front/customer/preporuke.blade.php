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
                            <span class="account-content-icon"><i class="fa-duotone fa-books" aria-hidden="true"></i></span>
                            <div>
                                <h2 class="account-content-title">{{ __('front.account.recommendations') }}</h2>
                                <p class="account-content-subtitle">{{ __('front.account.recommendations_subtitle') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="account-recommendation-note">
                        <i class="fa-duotone fa-star mt-1" aria-hidden="true"></i>
                        <span>{{ __('front.account.recommendations_note') }}</span>
                    </div>

                    @if($products->isNotEmpty())
                        <div class="tns-carousel catalog-product-carousel tns-controls-static tns-controls-outside tns-nav-enabled pt-2 account-recommendations-carousel">
                            <div class="tns-carousel-inner" data-carousel-options='{"items":2,"controls":false,"nav":true,"responsive":{"0":{"items":2,"gutter":8},"768":{"items":3,"gutter":14},"1200":{"items":4,"controls":true,"gutter":16}}}'>
                                @foreach($products as $product)
                                    <div>
                                        @include('front.catalog.category.product', ['product' => $product])
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="account-empty">
                            <div><i class="fa-duotone fa-books d-block fs-3 mb-3" aria-hidden="true"></i>{{ __('front.account.recommendations_empty') }}</div>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </section>
@endsection
