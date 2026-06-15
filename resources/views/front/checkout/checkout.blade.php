@extends('front.layouts.app')

@push('css_after')
    @livewireStyles
@endpush

@section('content')

    <div class="page-title-overlap bg-accent pt-4" style="background-image: url({{ asset('media/img/farmer.png')  }});background-repeat: repeat">
        <div class="container d-lg-flex justify-content-between py-2 py-lg-3">
            <div class="order-lg-2 mb-3 mb-lg-0 pt-lg-2">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-dark flex-lg-nowrap justify-content-center justify-content-lg-start">
                        <li class="breadcrumb-item"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}"><i class="ci-home"></i>{{ __('front.nav.home') }}</a></li>
                        <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ __('front.checkout.checkout') }}</li>
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
                @livewire('front.checkout', ['step' => $step, 'is_free_shipping' => $is_free_shipping])
            </section>
            <!-- Sidebar-->
            <aside class="col-lg-4 pt-4 pt-lg-0 ps-xl-5 d-none d-lg-block">
                <cart-view-aside route="naplata" continueurl="{{ \Illuminate\Support\Facades\URL::previous() }}" checkouturl="{{ \App\Helpers\LocaleHelper::route('naplata') }}"></cart-view-aside>
            </aside>
        </div>
    </div>

@endsection

@push('js_after')
    @livewireScripts
@endpush
