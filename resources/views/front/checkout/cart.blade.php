@extends('front.layouts.app')

@if (isset($gdl))
    @section('google_data_layer')
        <script>
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({ ecommerce: null });
            window.dataLayer.push({
                'event': 'view_cart',
                'ecommerce': {'items': <?php echo json_encode($gdl); ?>}
            });
        </script>
    @endsection
@endif

@push('css_after')
    @include('front.checkout.partials.progress-styles')
    @include('front.checkout.partials.cart-best-sellers-styles')
@endpush

@section('content')

<!-- Page Title-->
<div class="page-title-overlap bg-accent pt-4" style="background-image: url({{ asset('media/img/farmer.png')  }});background-repeat: repeat">
    <div class="container d-lg-flex justify-content-between py-2 py-lg-3">
        <div class="order-lg-2 mb-3 mb-lg-0 pt-lg-2">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-dark flex-lg-nowrap justify-content-center justify-content-lg-start">
                    <li class="breadcrumb-item"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}"><i class="fa-solid fa-house"></i>{{ __('front.nav.home') }}</a></li>
                    <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ __('front.checkout.cart') }}</li>
                </ol>
            </nav>
        </div>
        <div class="order-lg-1 pe-lg-4 text-center text-lg-start">
            <h1 class="h3 checkout-page-heading text-dark mb-0">{{ __('front.checkout.cart') }}</h1>
        </div>
    </div>
</div>
<div class="container checkout-page pb-4 mb-2">
    <div class="row">
        <section class="col-lg-8">
            <nav class="checkout-progress-shell" aria-label="{{ __('front.checkout.checkout') }}">
            <div class="steps steps-dark checkout-steps">
                <a class="step-item current active" href="{{ \App\Helpers\LocaleHelper::route('kosarica') }}">
                    <div class="step-progress"><span class="step-count">1</span></div>
                    <div class="step-label"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>{{ __('front.checkout.cart') }}</div>
                </a>
                <a class="step-item" href="{{ \App\Helpers\LocaleHelper::route('naplata', ['step' => 'podaci']) }}">
                    <div class="step-progress"><span class="step-count">2</span></div>
                    <div class="step-label"><i class="fa-solid fa-circle-user" aria-hidden="true"></i>{{ __('front.checkout.details') }}</div>
                </a>
                <a class="step-item" href="{{ \App\Helpers\LocaleHelper::route('naplata', ['step' => 'dostava']) }}">
                    <div class="step-progress"><span class="step-count">3</span></div>
                    <div class="step-label"><i class="fa-solid fa-box" aria-hidden="true"></i>{{ __('front.checkout.shipping') }}</div>
                </a>
                <a class="step-item" href="{{ \App\Helpers\LocaleHelper::route('naplata', ['step' => 'placanje']) }}">
                    <div class="step-progress"><span class="step-count">4</span></div>
                    <div class="step-label"><i class="fa-solid fa-credit-card" aria-hidden="true"></i>{{ __('front.checkout.payment') }}</div>
                </a>
                <a class="step-item" href="{{ \App\Helpers\LocaleHelper::route('pregled') }}">
                    <div class="step-progress"><span class="step-count">5</span></div>
                    <div class="step-label"><i class="fa-solid fa-eye"></i>{{ __('front.checkout.review') }}</div>
                </a>
                <a class="step-item" href="#">
                    <div class="step-progress"><span class="step-count">6</span></div>
                    <div class="step-label"><i class="fa-solid fa-circle-check"></i>{{ __('front.checkout.success') }}</div>
                </a>
            </div>
            </nav>
            <div class="checkout-surface">
            <cart-view continueurl="{{ \Illuminate\Support\Facades\URL::previous() }}" checkouturl="{{ \App\Helpers\LocaleHelper::route('naplata') }}" freeship="{{ config('settings.free_shipping') }}"></cart-view>
            </div>
        </section>
        <!-- Sidebar-->
        <aside class="col-lg-4 pt-4 pt-lg-0 ps-xl-5">
            <cart-view-aside route="kosarica" continueurl="{{ \Illuminate\Support\Facades\URL::previous() }}" checkouturl="{{ \App\Helpers\LocaleHelper::route('naplata') }}"></cart-view-aside>
        </aside>
    </div>
</div>

@include('front.checkout.partials.cart-best-sellers', ['products' => $bestSellers])

@endsection

@push('js_after')
    @include('front.checkout.partials.progress-script')
@endpush
