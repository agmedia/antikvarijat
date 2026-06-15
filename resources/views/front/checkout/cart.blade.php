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

@section('content')

<!-- Page Title-->
<div class="page-title-overlap bg-accent pt-4" style="background-image: url({{ asset('media/img/farmer.png')  }});background-repeat: repeat">
    <div class="container d-lg-flex justify-content-between py-2 py-lg-3">
        <div class="order-lg-2 mb-3 mb-lg-0 pt-lg-2">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-dark flex-lg-nowrap justify-content-center justify-content-lg-start">
                    <li class="breadcrumb-item"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}"><i class="ci-home"></i>{{ __('front.nav.home') }}</a></li>
                    <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ __('front.checkout.cart') }}</li>
                </ol>
            </nav>
        </div>
        <div class="order-lg-1 pe-lg-4 text-center text-lg-start">
            <h1 class="h3 text-dark mb-0">{{ __('front.checkout.cart') }}</h1>
        </div>
    </div>
</div>
<div class="container pb-5 mb-2 mb-md-4">
    <div class="row">
        <section class="col-lg-8">
            <div class="steps steps-dark pt-2 pb-3 mb-5">
                <a class="step-item current active" href="{{ \App\Helpers\LocaleHelper::route('kosarica') }}">
                    <div class="step-progress"><span class="step-count">1</span></div>
                    <div class="step-label"><i class="ci-cart"></i>{{ __('front.checkout.cart') }}</div>
                </a>
                <a class="step-item" href="{{ \App\Helpers\LocaleHelper::route('naplata', ['step' => 'podaci']) }}">
                    <div class="step-progress"><span class="step-count">2</span></div>
                    <div class="step-label"><i class="ci-user-circle"></i>{{ __('front.checkout.details') }}</div>
                </a>
                <a class="step-item" href="{{ \App\Helpers\LocaleHelper::route('naplata', ['step' => 'dostava']) }}">
                    <div class="step-progress"><span class="step-count">3</span></div>
                    <div class="step-label"><i class="ci-package"></i>{{ __('front.checkout.shipping') }}</div>
                </a>
                <a class="step-item" href="{{ \App\Helpers\LocaleHelper::route('naplata', ['step' => 'placanje']) }}">
                    <div class="step-progress"><span class="step-count">4</span></div>
                    <div class="step-label"><i class="ci-card"></i>{{ __('front.checkout.payment') }}</div>
                </a>
                <a class="step-item" href="{{ \App\Helpers\LocaleHelper::route('pregled') }}">
                    <div class="step-progress"><span class="step-count">5</span></div>
                    <div class="step-label"><i class="ci-eye"></i>{{ __('front.checkout.review') }}</div>
                </a>
                <a class="step-item" href="#">
                    <div class="step-progress"><span class="step-count">6</span></div>
                    <div class="step-label"><i class="ci-check-circle"></i>{{ __('front.checkout.success') }}</div>
                </a>
            </div>
            <div class="bg-white rounded-3 shadow-lg p-4">
            <cart-view continueurl="{{ \Illuminate\Support\Facades\URL::previous() }}" checkouturl="{{ \App\Helpers\LocaleHelper::route('naplata') }}" freeship="{{ config('settings.free_shipping') }}"></cart-view>
            </div>
        </section>
        <!-- Sidebar-->
        <aside class="col-lg-4 pt-4 pt-lg-0 ps-xl-5">
            <cart-view-aside route="kosarica" continueurl="{{ \Illuminate\Support\Facades\URL::previous() }}" checkouturl="{{ \App\Helpers\LocaleHelper::route('naplata') }}"></cart-view-aside>
        </aside>
    </div>
</div>

@endsection
