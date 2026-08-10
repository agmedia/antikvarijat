<div class="steps steps-light pt-2 pb-3 mb-5">
    <a class="step-item {{ request()->routeIs(['kosarica', 'en.kosarica']) ? 'current' : '' }} {{ request()->routeIs(['kosarica', 'en.kosarica', 'adresa-isporuke', 'dostava', 'naplata', 'en.naplata','pregled', 'en.pregled']) ? 'active' : '' }}" href="{{ \App\Helpers\LocaleHelper::route('kosarica') }}">
        <div class="step-progress"><span class="step-count">1</span></div>
        <div class="step-label"><i class="fa-regular fa-bag-shopping"></i>{{ __('front.checkout.cart') }}</div>
    </a>
    <a class="step-item  {{ request()->routeIs(['adresa-isporuke']) ? 'current' : '' }} {{ request()->routeIs([ 'adresa-isporuke', 'dostava', 'naplata', 'en.naplata','pregled', 'en.pregled']) ? 'active' : '' }}" href="#">
        <div class="step-progress"><span class="step-count">2</span></div>
        <div class="step-label"><i class="fa-duotone fa-circle-user"></i>{{ __('front.checkout.details') }}</div>
    </a>
    <a class="step-item  {{ request()->routeIs(['dostava']) ? 'current' : '' }} {{ request()->routeIs([ 'dostava', 'naplata', 'en.naplata','pregled', 'en.pregled']) ? 'active' : '' }}" href="#">
        <div class="step-progress"><span class="step-count">3</span></div>
        <div class="step-label"><i class="fa-duotone fa-box"></i>{{ __('front.checkout.shipping') }}</div></a>
    <a class="step-item  {{ request()->routeIs(['naplata', 'en.naplata']) ? 'current' : '' }} {{ request()->routeIs([ 'naplata', 'en.naplata','pregled', 'en.pregled']) ? 'active' : '' }}" href="{{ \App\Helpers\LocaleHelper::route('naplata') }}">
        <div class="step-progress"><span class="step-count">4</span></div>
        <div class="step-label"><i class="fa-duotone fa-credit-card"></i>{{ __('front.checkout.payment') }}</div>
    </a>
    <a class="step-item  {{ request()->routeIs(['pregled', 'en.pregled']) ? 'current' : '' }} {{ request()->routeIs([ 'pregled', 'en.pregled']) ? 'active' : '' }}" href="{{ \App\Helpers\LocaleHelper::route('pregled') }}">
        <div class="step-progress"><span class="step-count">5</span></div>
        <div class="step-label"><i class="fa-solid fa-circle-check"></i>{{ __('front.checkout.review') }}</div>
    </a>
</div>
