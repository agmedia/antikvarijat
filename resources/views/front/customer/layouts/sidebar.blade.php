@php
    $details = $user->details;
    $displayName = trim((string) optional($details)->fname . ' ' . (string) optional($details)->lname) ?: $user->name;
    $initials = collect(preg_split('/\s+/', trim($displayName)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('');
@endphp

<aside class="col-lg-4 col-xl-3">
    <div class="account-card account-sidebar mb-4 mb-lg-0">
        <div class="account-user-panel">
            <span class="account-avatar" aria-hidden="true">{{ $initials ?: 'BB' }}</span>
            <div class="account-user-panel__body">
                <h2 class="account-user-panel__name">{{ $displayName }}</h2>
                <span class="account-user-panel__email">{{ $user->email }}</span>
            </div>
            <button class="btn btn-primary btn-sm d-lg-none ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#account-menu" aria-controls="account-menu" aria-expanded="false" aria-label="{{ __('front.account.navigation') }}">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
            </button>
        </div>

        <div class="collapse d-lg-block" id="account-menu">
            <div class="account-nav-heading">{{ __('front.account.menu_title') }}</div>
            <ul class="account-nav list-unstyled mb-0">
                <li class="account-nav__item">
                    <a class="account-nav__link {{ request()->routeIs('moj-racun', 'en.moj-racun') ? 'active' : '' }}" href="{{ \App\Helpers\LocaleHelper::route('moj-racun') }}">
                        <i class="fa-duotone fa-user" aria-hidden="true"></i>{{ __('front.account.my_data') }}
                    </a>
                </li>
                <li class="account-nav__item">
                    <a class="account-nav__link {{ request()->routeIs('moje-narudzbe', 'en.moje-narudzbe') ? 'active' : '' }}" href="{{ \App\Helpers\LocaleHelper::route('moje-narudzbe') }}">
                        <i class="fa-regular fa-bag-shopping" aria-hidden="true"></i>{{ __('front.account.orders') }}
                    </a>
                </li>
                <li class="account-nav__item">
                    <a class="account-nav__link {{ request()->routeIs('moji-dojmovi', 'en.moji-dojmovi') ? 'active' : '' }}" href="{{ \App\Helpers\LocaleHelper::route('moji-dojmovi') }}">
                        <i class="fa-duotone fa-star" aria-hidden="true"></i>{{ __('front.account.reviews') }}
                    </a>
                </li>
                <li class="account-nav__item">
                    <a class="account-nav__link {{ request()->routeIs('preporuke-za-vas', 'en.preporuke-za-vas') ? 'active' : '' }}" href="{{ \App\Helpers\LocaleHelper::route('preporuke-za-vas') }}">
                        <i class="fa-duotone fa-books" aria-hidden="true"></i>{{ __('front.account.recommendations') }}
                    </a>
                </li>
                <li class="account-nav__item">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="account-nav__link account-nav__link--logout" type="submit">
                            <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>{{ __('front.account.logout') }}
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</aside>
