@guest
    @php
        $authForm = old('_auth_form', 'signin');
        $hasAuthErrors = old('_auth_form') && $errors->any();
        $loginOpen = $authForm !== 'signup';
        $checkoutRouteNames = [
            'kosarica', 'naplata', 'pregled', 'checkout', 'checkout.*',
            'en.kosarica', 'en.naplata', 'en.pregled', 'en.checkout', 'en.checkout.*',
        ];
        $checkoutReturnUrl = request()->routeIs($checkoutRouteNames) ? request()->getRequestUri() : null;
    @endphp
    <div class="modal fade account-auth-modal" id="signin-modal" tabindex="-1" aria-label="{{ __('front.auth.account_access') }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <ul class="nav nav-tabs" role="tablist" aria-label="{{ __('front.auth.account_access') }}">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $loginOpen ? 'active' : '' }}" id="pills-signin-tab" data-bs-target="#signin-tab" data-bs-toggle="tab" type="button" role="tab" aria-controls="signin-tab" aria-selected="{{ $loginOpen ? 'true' : 'false' }}">
                                <i class="fa-solid fa-lock-open me-2" aria-hidden="true"></i>{{ __('front.auth.login_title') }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $loginOpen ? '' : 'active' }}" id="pills-signup-tab" data-bs-target="#signup-tab" data-bs-toggle="tab" type="button" role="tab" aria-controls="signup-tab" aria-selected="{{ $loginOpen ? 'false' : 'true' }}">
                                <i class="fa-solid fa-user-plus me-2" aria-hidden="true"></i>{{ __('front.auth.registration') }}
                            </button>
                        </li>
                    </ul>
                    <button class="account-auth-close" type="button" data-bs-dismiss="modal" aria-label="{{ __('front.auth.close') }}">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="modal-body tab-content">
                    <form method="POST" class="needs-validation tab-pane fade {{ $loginOpen ? 'show active' : '' }}" action="{{ route('login') }}" autocomplete="on" novalidate id="signin-tab" role="tabpanel" aria-labelledby="pills-signin-tab">
                        @csrf
                        <input type="hidden" name="_auth_form" value="signin">
                        @if ($checkoutReturnUrl)
                            <input type="hidden" name="_redirect_to" value="{{ $checkoutReturnUrl }}">
                        @endif

                        @if (session('status'))
                            <div class="alert alert-success py-2 px-3" role="status">{{ session('status') }}</div>
                        @endif
                        @if (session('auth_error'))
                            <div class="alert alert-danger py-2 px-3" role="alert">{{ session('auth_error') }}</div>
                        @endif
                        @if ($loginOpen && $hasAuthErrors)
                            <div class="alert alert-danger py-2 px-3" role="alert">{{ $errors->first() }}</div>
                        @endif

                        @if ($googleLoginEnabled ?? false)
                            <a class="google-login-button" href="{{ route('google.login.redirect', ['redirect' => request()->fullUrl()]) }}">
                                <svg class="google-login-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path fill="#4285F4" d="M21.6 12.227c0-.709-.064-1.391-.182-2.045H12v3.868h5.382a4.601 4.601 0 0 1-1.996 3.018v2.509h3.232c1.891-1.741 2.982-4.305 2.982-7.35Z"/>
                                    <path fill="#34A853" d="M12 22c2.7 0 4.964-.895 6.618-2.423l-3.232-2.509c-.895.6-2.041.955-3.386.955-2.605 0-4.809-1.76-5.596-4.123h-3.34v2.591A9.997 9.997 0 0 0 12 22Z"/>
                                    <path fill="#FBBC05" d="M6.404 13.9A6.017 6.017 0 0 1 6.091 12c0-.659.113-1.3.313-1.9V7.509h-3.34A9.996 9.996 0 0 0 2 12c0 1.614.386 3.141 1.064 4.491l3.34-2.591Z"/>
                                    <path fill="#EA4335" d="M12 5.977c1.468 0 2.786.505 3.823 1.496l2.868-2.868C16.959 2.99 14.695 2 12 2a9.997 9.997 0 0 0-8.936 5.509l3.34 2.591C7.191 7.737 9.395 5.977 12 5.977Z"/>
                                </svg>
                                <span>{{ __('front.auth.continue_google') }}</span>
                            </a>
                            <div class="google-login-divider" aria-hidden="true"><span>{{ __('front.auth.or') }}</span></div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label" for="si-email">{{ __('front.auth.email') }}</label>
                            <input class="form-control @if($loginOpen && $errors->has('email')) is-invalid @endif" type="email" id="si-email" name="email" value="{{ $loginOpen ? old('email') : '' }}" required autocomplete="email">
                            <div class="invalid-feedback">{{ $errors->first('email') ?: __('front.auth.email_invalid') }}</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="si-password">{{ __('front.auth.password') }}</label>
                            <div class="auth-password-field">
                                <input class="form-control" type="password" name="password" id="si-password" required autocomplete="current-password">
                                <button class="password-visibility-toggle" type="button" aria-controls="si-password" aria-pressed="false" aria-label="{{ __('front.auth.show_password') }}">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div class="form-check mb-0">
                                <input class="form-check-input" id="si-remember" name="remember" type="checkbox">
                                <label class="form-check-label" for="si-remember">{{ __('front.checkout.remember_me') }}</label>
                            </div>
                            <a class="fs-sm" href="{{ route('forget.password.get') }}">{{ __('front.auth.forgot_password') }}</a>
                        </div>

                        <button class="btn btn-primary btn-shadow d-block w-100" type="submit">{{ __('front.auth.login_button') }}</button>
                    </form>

                    <form class="needs-validation tab-pane fade {{ $loginOpen ? '' : 'show active' }}" method="POST" action="{{ route('register') }}" autocomplete="on" novalidate id="signup-tab" role="tabpanel" aria-labelledby="pills-signup-tab">
                        @csrf
                        <input type="hidden" name="_auth_form" value="signup">

                        @if (! $loginOpen && $hasAuthErrors)
                            <div class="alert alert-danger py-2 px-3" role="alert">{{ $errors->first() }}</div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label" for="su-name">{{ __('front.auth.username') }}</label>
                            <input class="form-control @if(!$loginOpen && $errors->has('name')) is-invalid @endif" type="text" name="name" id="su-name" value="{{ ! $loginOpen ? old('name') : '' }}" required autocomplete="name">
                            <div class="invalid-feedback">{{ $errors->first('name') ?: __('front.auth.username_required') }}</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="su-email">{{ __('front.auth.email') }}</label>
                            <input class="form-control @if(!$loginOpen && $errors->has('email')) is-invalid @endif" type="email" name="email" id="su-email" value="{{ ! $loginOpen ? old('email') : '' }}" required autocomplete="email">
                            <div class="invalid-feedback">{{ $errors->first('email') ?: __('front.auth.email_invalid') }}</div>
                        </div>

                        <div class="row gx-3">
                            <div class="col-sm-6 mb-3">
                                <label class="form-label" for="su-password">{{ __('front.auth.password') }}</label>
                                <div class="auth-password-field">
                                    <input class="form-control @if(!$loginOpen && $errors->has('password')) is-invalid @endif" type="password" name="password" minlength="8" id="su-password" required autocomplete="new-password">
                                    <button class="password-visibility-toggle" type="button" aria-controls="su-password" aria-pressed="false" aria-label="{{ __('front.auth.show_password') }}">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div class="form-text">{{ __('front.auth.password_hint') }}</div>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <label class="form-label" for="su-password-confirm">{{ __('front.auth.password_confirmation') }}</label>
                                <div class="auth-password-field">
                                    <input class="form-control" type="password" name="password_confirmation" minlength="8" id="su-password-confirm" required autocomplete="new-password">
                                    <button class="password-visibility-toggle" type="button" aria-controls="su-password-confirm" aria-pressed="false" aria-label="{{ __('front.auth.show_password') }}">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="terms" id="auth-terms" required>
                            <label class="form-check-label auth-terms" for="auth-terms">
                                {!! __('front.auth.terms_agree', ['url' => \App\Helpers\LocaleHelper::route('catalog.route.page', ['page' => 'opci-uvjeti-kupnje'])]) !!}
                            </label>
                            <div class="invalid-feedback">{{ __('front.auth.terms_required') }}</div>
                        </div>

                        <input type="hidden" name="recaptcha" id="auth-recaptcha">
                        <button class="btn btn-primary btn-shadow d-block w-100" type="submit">{{ __('front.auth.register_button') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endguest
