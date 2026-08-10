
@extends('front.layouts.app')

@push('css_after')
    @include('front.checkout.partials.progress-styles')
    <style>
        .checkout-review-heading-icon,
        .checkout-review-detail-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.35rem;
            height: 2.35rem;
            flex: 0 0 auto;
            border-radius: 50%;
            color: var(--bs-primary);
            background: rgba(42, 98, 72, .1);
        }
        .checkout-review-card { color: #536058; font-size: .93rem; }
        .checkout-review-card > .border-bottom { padding-bottom: 1.1rem !important; margin-bottom: 1.35rem !important; }
        .checkout-review-card h2 { font-size: 1.25rem; font-weight: 600; }
        .checkout-review-card > .border-bottom p { font-size: .86rem !important; }
        .checkout-review-detail {
            height: 100%;
            border: 1px solid #e8ece9;
            border-radius: .75rem;
            background: #fff;
        }
        .checkout-review-detail-icon { width: 2rem; height: 2rem; font-size: .75rem; }
        .checkout-review-detail h4 { font-size: .96rem; font-weight: 600; }
    </style>
@endpush

@section('content')

<div class="page-title-overlap bg-accent pt-4"  style="background-image: url({{ asset('media/img/farmer.png')  }});background-repeat: repeat">
    <div class="container d-lg-flex justify-content-between py-2 py-lg-3">
        <div class="order-lg-2 mb-3 mb-lg-0 pt-lg-2">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-dark flex-lg-nowrap justify-content-center justify-content-lg-start">
                    <li class="breadcrumb-item"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}"><i class="fa-solid fa-house"></i>{{ __('front.nav.home') }}</a></li>

                    <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ __('front.checkout.confirm_order') }}</li>
                </ol>
            </nav>
        </div>
        <div class="order-lg-1 pe-lg-4 text-center text-lg-start">
            <h1 class="h3 checkout-page-heading text-dark mb-0">{{ __('front.checkout.confirm_order') }}</h1>
        </div>
    </div>
</div>

<div class="container checkout-page pb-5 mb-2 mb-md-4">
    <div class="row">
        <section class="col-lg-8">

            <nav class="checkout-progress-shell" aria-label="{{ __('front.checkout.checkout') }}">
            <div class="steps steps-dark checkout-steps checkout-review-steps">
                <a class="step-item active" href="{{ \App\Helpers\LocaleHelper::route('kosarica') }}">
                    <div class="step-progress"><span class="step-count">1</span></div>
                    <div class="step-label"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>{{ __('front.checkout.cart') }}</div>
                </a>
                <a class="step-item active" href="{{ \App\Helpers\LocaleHelper::route('naplata', ['step' => 'podaci']) }}">
                    <div class="step-progress"><span class="step-count">2</span></div>
                    <div class="step-label"><i class="fa-solid fa-circle-user" aria-hidden="true"></i>{{ __('front.checkout.details') }}</div>
                </a>
                <a class="step-item active" href="{{ \App\Helpers\LocaleHelper::route('naplata', ['step' => 'dostava']) }}">
                    <div class="step-progress"><span class="step-count">3</span></div>
                    <div class="step-label"><i class="fa-solid fa-box" aria-hidden="true"></i>{{ __('front.checkout.shipping') }}</div>
                </a>
                <a class="step-item active" href="{{ \App\Helpers\LocaleHelper::route('naplata', ['step' => 'placanje']) }}">
                    <div class="step-progress"><span class="step-count">4</span></div>
                    <div class="step-label"><i class="fa-solid fa-credit-card" aria-hidden="true"></i>{{ __('front.checkout.payment') }}</div>
                </a>
                <a class="step-item current active" href="{{ \App\Helpers\LocaleHelper::route('pregled') }}">
                    <div class="step-progress"><span class="step-count">5</span></div>
                    <div class="step-label"><i class="fa-solid fa-eye" aria-hidden="true"></i>{{ __('front.checkout.review') }}</div>
                </a>

                <a class="step-item" href="javascript:void(0);">
                    <div class="step-progress"><span class="step-count">6</span></div>
                    <div class="step-label"><i class="fa-solid fa-circle-check" aria-hidden="true"></i>{{ __('front.checkout.success') }}</div>
                </a>
            </div>
            </nav>
            <div class="checkout-review-card checkout-surface">
            <div class="d-flex align-items-center gap-3 pb-3 mb-4 border-bottom">
                <span class="checkout-review-heading-icon"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i></span>
                <div>
                    <h2 class="h5 mb-1">{{ __('front.checkout.cart_review') }}</h2>
                    <p class="text-muted fs-sm mb-0">{{ __('front.checkout.review_intro') }}</p>
                </div>
            </div>
            <cart-view continueurl="{{ \App\Helpers\LocaleHelper::route('index') }}" checkouturl="{{ \App\Helpers\LocaleHelper::route('naplata') }}" buttons="false"></cart-view>

            <div class="bg-secondary rounded-3 p-3 p-md-4 mt-3">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="checkout-review-detail p-3">
                            <h4 class="h6 d-flex align-items-center mb-3"><span class="checkout-review-detail-icon me-2"><i class="fa-solid fa-user" aria-hidden="true"></i></span>{{ __('front.checkout.payer') }}</h4>
                            <ul class="list-unstyled fs-sm mb-0">
                                <li class="mb-1"><strong>{{ __('front.checkout.user') }}:</strong> {{ $data['address']['fname'] }} {{ $data['address']['lname'] }}</li>
                                <li class="mb-1"><strong>{{ __('front.checkout.address') }}:</strong> {{ $data['address']['address'] }}, {{ $data['address']['zip'] }} {{ $data['address']['city'] }}, {{ $data['address']['state'] }}</li>
                                <li><strong>{{ __('front.checkout.email') }}:</strong> {{ $data['address']['email'] }}</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="checkout-review-detail p-3">
                            <h4 class="h6 d-flex align-items-center mb-3"><span class="checkout-review-detail-icon me-2"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></span>{{ __('front.checkout.deliver_to') }}</h4>
                            <ul class="list-unstyled fs-sm mb-0">
                                <li class="mb-1"><strong>{{ __('front.checkout.user') }}:</strong> {{ $data['address']['fname'] }} {{ $data['address']['lname'] }}</li>
                                <li class="mb-1"><strong>{{ __('front.checkout.address') }}:</strong> {{ $data['address']['address'] }}, {{ $data['address']['zip'] }} {{ $data['address']['city'] }}, {{ $data['address']['state'] }}</li>
                                <li><strong>{{ __('front.checkout.email') }}:</strong> {{ $data['address']['email'] }}</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="checkout-review-detail p-3">
                            <h4 class="h6 d-flex align-items-center mb-3"><span class="checkout-review-detail-icon me-2"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i></span>{{ __('front.checkout.shipping_method') }}</h4>
                            <div class="fs-sm"><strong>{{ \App\Helpers\LocaleHelper::localizedSettingField($data['shipping'], 'title') }}</strong><br><span class="text-muted">{{ \App\Helpers\LocaleHelper::localizedSettingDataField($data['shipping'], 'description') ?: \App\Helpers\LocaleHelper::localizedSettingDataField($data['shipping'], 'short_description') }}</span></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="checkout-review-detail p-3">
                            <h4 class="h6 d-flex align-items-center mb-3"><span class="checkout-review-detail-icon me-2"><i class="fa-solid fa-credit-card" aria-hidden="true"></i></span>{{ __('front.checkout.payment_method') }}</h4>
                            <div class="fs-sm"><strong>{{ \App\Helpers\LocaleHelper::localizedSettingField($data['payment'], 'title') }}</strong><br><span class="text-muted">{{ \App\Helpers\LocaleHelper::localizedSettingDataField($data['payment'], 'description') ?: \App\Helpers\LocaleHelper::localizedSettingDataField($data['payment'], 'short_description') }}</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-none d-lg-flex pt-0 mt-3">
                {!! $data['payment_form'] !!}
            </div>
            </div>
        </section>

        <aside class="col-lg-4 pt-4 pt-lg-0 mb-3 ps-xl-5 d-block">
            <cart-view-aside route="pregled" continueurl="{{ \App\Helpers\LocaleHelper::route('index') }}" checkouturl="/"></cart-view-aside>
        </aside>
    </div>

    <div class="row d-lg-none">
        <div class="col-lg-8">
            {!! $data['payment_form'] !!}
        </div>
    </div>
</div>
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable ">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ __('front.checkout.terms_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @foreach ($uvjeti_kupnje as $uvjet)
                    {!! $uvjet->description !!}

                @endforeach
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('front.checkout.close') }}</button>

            </div>
        </div>
    </div>
</div>

@endsection

@push('js_after')
    @include('front.checkout.partials.progress-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[name="pay"]');
            const submitButton = form ? form.querySelector('button[type="submit"]') : null;

            window.scrollTo({ top: 0, behavior: 'auto' });

            if (!form || !submitButton) {
                return;
            }

            submitButton.addEventListener('click', function(event) {
                event.preventDefault(); // Zaustavi automatski submit forme

                // PUTANJA NA BACKEND KOJA PROVJERAVA STANJE ARTIKALA
                fetch('{{ url('/api/v2/cart/provjeri-stanje-artikala') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        order_id: '{{ $data['id'] }}'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        // Sve je u redu, šaljemo formu
                        form.submit();
                    } else {
                        // Prikaz greške korisniku
                        alert('{{ __('front.checkout.stock_error') }}');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('{{ __('front.checkout.stock_check_error') }}');
                });
            });
        });
    </script>

@endpush
