<div class="account-hero">
    <div class="container d-lg-flex justify-content-between py-2 py-lg-3">
        <div class="order-lg-2 mb-3 mb-lg-0 pt-lg-2">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb flex-lg-nowrap justify-content-center justify-content-lg-start mb-0">
                    <li class="breadcrumb-item"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}"><i class="fa-solid fa-house me-1" aria-hidden="true"></i>{{ __('front.nav.home') }}</a></li>
                    <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ __('front.account.breadcrumb') }}</li>
                </ol>
            </nav>
        </div>
        <div class="order-lg-1 pe-lg-4 text-center text-lg-start">
            <h1 class="account-hero__title">{{ __('front.account.title') }}</h1>
        </div>
    </div>
</div>
