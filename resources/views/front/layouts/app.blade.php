<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    @php
        $locale = app()->getLocale();
        $isEnglish = $locale === \App\Helpers\LocaleHelper::ENGLISH_LOCALE;
        $localeSettings = config('localization.locales.' . $locale, config('localization.locales.hr'));
        $defaultTitle = __('front.meta.default_title');
        $defaultDescription = __('front.meta.default_description');
        $sectionTitle = trim($__env->yieldContent('title'));
        $sectionDescription = trim($__env->yieldContent('description'));
        $title = $sectionTitle !== ''
            ? html_entity_decode($sectionTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            : $defaultTitle;
        $description = $sectionDescription !== ''
            ? html_entity_decode($sectionDescription, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            : $defaultDescription;
        $seoMeta = \App\Helpers\Metatags::resolve(request());
        $sectionCanonical = trim($__env->yieldContent('canonical'));
        $canonicalUrl = $sectionCanonical !== ''
            ? html_entity_decode($sectionCanonical, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            : $seoMeta['canonical'];
        $alternateUrls = \App\Helpers\LocaleHelper::currentAlternateUrls();
        $languageSwitcherUrls = \App\Helpers\LocaleHelper::languageSwitcherUrls();
        $robots = trim($__env->yieldContent('robots')) ?: $seoMeta['robots'];
        $ogType = trim($__env->yieldContent('og_type')) ?: 'website';
        $sectionOgImage = trim($__env->yieldContent('og_image'));
        $sectionOgImageAlt = trim($__env->yieldContent('og_image_alt'));
        $ogImage = html_entity_decode($sectionOgImage, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $ogImageAlt = $sectionOgImageAlt !== ''
            ? html_entity_decode($sectionOgImageAlt, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            : $title;
        $ogImageType = trim($__env->yieldContent('og_image_type')) ?: \App\Helpers\StructuredData::imageMimeType($ogImage);
        $twitterCard = $ogImage ? 'summary_large_image' : 'summary';
        $imagesDomain = config('settings.images_domain');
        $schemaPageType = trim($__env->yieldContent('schema_page_type')) ?: 'WebPage';
        $returnPolicySettings = app(\App\Services\ContractWithdrawalSettingsService::class)->get();
        $returnPolicySchema = \App\Helpers\StructuredData::merchantReturnPolicy(
            $returnPolicySettings,
            \App\Helpers\LocaleHelper::route('contract-withdrawal.create')
        );
        $siteSchema = \App\Helpers\StructuredData::siteGraph(
            $canonicalUrl,
            $title,
            $description,
            $locale,
            $schemaPageType,
            $returnPolicySchema
        );
    @endphp
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <!-- SEO Meta Tags-->
    <meta name="description" content="{{ $description }}">
    <meta name="language" content="{{ $locale }}">
    <meta name="robots" content="{{ $robots }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    @foreach ($alternateUrls as $alternateLocale => $alternateUrl)
        <link rel="alternate" hreflang="{{ config('localization.locales.' . $alternateLocale . '.hreflang', $alternateLocale) }}" href="{{ $alternateUrl }}">
    @endforeach
    @if (! empty($alternateUrls))
        <link rel="alternate" hreflang="x-default" href="{{ $alternateUrls['hr'] ?? reset($alternateUrls) }}">
    @endif
    <meta property="og:locale" content="{{ $localeSettings['og_locale'] ?? 'hr_HR' }}">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:site_name" content="Antikvarijat Biblos">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:image:secure_url" content="{{ $ogImage }}">
        @if ($ogImageType)<meta property="og:image:type" content="{{ $ogImageType }}">@endif
        <meta property="og:image:alt" content="{{ $ogImageAlt }}">
    @endif
    <meta name="twitter:card" content="{{ $twitterCard }}">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    @if ($ogImage)
        <meta name="twitter:image" content="{{ $ogImage }}">
        <meta name="twitter:image:alt" content="{{ $ogImageAlt }}">
    @endif
    <meta name="author" content="Biblos">
    @stack('meta_tags')
    <!-- Viewport-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta name="facebook-domain-verification" content="16b3jag78m5ywwi9xfdmmt7r4mmsws" />
    <!-- Favicon and Touch Icons-->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $imagesDomain . 'apple-touch-icon.png' }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $imagesDomain . 'favicon-32x32.png' }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ $imagesDomain . 'favicon-16x16.png' }}">
    <link rel="shortcut icon" href="{{ $imagesDomain . 'apple-touch-icon.png' }}">
    <link rel="manifest" href="{{ $imagesDomain . 'site.webmanifest' }}">
    <link rel="mask-icon" href="{{ $imagesDomain . 'safari-pinned-tab.svg' }}" color="#314837">
    <meta name="msapplication-TileColor" content="#314837">
    <meta name="theme-color" content="#ffffff">
    <script type="application/ld+json">{!! \App\Helpers\StructuredData::toJson($siteSchema) !!}</script>

    <!-- Vendor Styles including: Font Icons, Plugins, etc.-->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <!-- Main Theme Styles + Bootstrap-->
    <link rel="stylesheet" media="screen" href="{{ \App\Helpers\Asset::url('css/theme.min.css') }}">
    <link rel="stylesheet" media="screen" href="{{ \App\Helpers\Asset::url('css/tiny-slider.css') }}"/>
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('vendor/fontawesome-pro/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('vendor/fontawesome-pro/css/solid.min.css') }}">
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('vendor/fontawesome-pro/css/regular.min.css') }}">
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('vendor/fontawesome-pro/css/duotone.min.css') }}">
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('vendor/fontawesome-pro/css/brands.min.css') }}">
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('vendor/cookieconsent/cookieconsent.css') }}">
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('css/account-auth.css') }}">
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('css/account.css') }}">
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('css/mobile-navigation.css') }}">
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('css/widgets.css') }}">
    @include('front.layouts.partials.cookie-consent-head')

    @if (config('app.env') == 'production')
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            window.cookieAnalyticsAllowed = false;
            window.cookieMarketingAllowed = false;
            window.canTrackAnalytics = () => false;

            function getStoredCookieConsent() {
                const match = document.cookie.match(/(?:^|;\s*)cc_cookie=([^;]+)/);

                if (!match) {
                    return null;
                }

                try {
                    return JSON.parse(decodeURIComponent(match[1]));
                } catch (error) {
                    return null;
                }
            }

            window.applyGooglePrivacySettings = function (marketingGranted) {
                const marketingAllowed = marketingGranted === true;

                gtag('set', 'ads_data_redaction', !marketingAllowed);
                gtag('set', 'allow_google_signals', marketingAllowed);
                gtag('set', 'allow_ad_personalization_signals', marketingAllowed);
            };

            window.updateGoogleConsentFromCookie = function (analyticsGranted, marketingGranted) {
                window.cookieAnalyticsAllowed = analyticsGranted === true;
                window.cookieMarketingAllowed = marketingGranted === true;
                window.canTrackAnalytics = () => window.cookieAnalyticsAllowed === true;
                window.applyGooglePrivacySettings(marketingGranted);

                gtag('consent', 'update', {
                    analytics_storage: analyticsGranted ? 'granted' : 'denied',
                    ad_storage: marketingGranted ? 'granted' : 'denied',
                    ad_user_data: marketingGranted ? 'granted' : 'denied',
                    ad_personalization: marketingGranted ? 'granted' : 'denied'
                });
            };

            gtag('consent', 'default', {
                analytics_storage: 'denied',
                ad_storage: 'denied',
                ad_user_data: 'denied',
                ad_personalization: 'denied',
                wait_for_update: 500
            });
            window.applyGooglePrivacySettings(false);

            window.__biblosFacebookLoaded = false;
            window.updateFacebookConsentFromCookie = function (marketingGranted) {
                if (marketingGranted !== true) {
                    if (typeof window.fbq === 'function') {
                        window.fbq('consent', 'revoke');
                    }
                    return;
                }

                const shouldTrackPageView = window.__biblosFacebookLoaded !== true;

                if (shouldTrackPageView) {
                    !function(f,b,e,v,n,t,s)
                    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
                        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
                        n.queue=[];t=b.createElement(e);t.async=!0;
                        t.src=v;s=b.getElementsByTagName(e)[0];
                        s.parentNode.insertBefore(t,s)}(window, document,'script',
                        'https://connect.facebook.net/en_US/fbevents.js');
                    window.fbq('init', '659899245170060');
                    window.__biblosFacebookLoaded = true;
                }

                window.fbq('consent', 'grant');

                if (shouldTrackPageView) {
                    window.fbq('track', 'PageView');
                }
            };

            const storedConsent = getStoredCookieConsent();

            if (storedConsent && Array.isArray(storedConsent.categories)) {
                window.updateGoogleConsentFromCookie(
                    storedConsent.categories.includes('analytics'),
                    storedConsent.categories.includes('marketing')
                );
                window.updateFacebookConsentFromCookie(storedConsent.categories.includes('marketing'));
            }
        </script>

        @yield('google_data_layer')

        <!-- Google Tag Manager -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
                j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
                'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer','GTM-TV7RKFH');</script>
        <!-- End Google Tag Manager -->
    @endif

    @stack('css_after')



</head>
<!-- Body-->
<body class="paper-white-bck">
@if (config('app.env') == 'production')
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe class="gtm-noscript-frame" src="https://www.googletagmanager.com/ns.html?id=GTM-TV7RKFH"
                      height="0" width="0"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
@endif

<!-- Topbar-->
<div class="topbar topbar-light front-topbar-pattern d-none d-md-block">
    <div class="container">
        <div class="topbar-text text-nowrap">
            <a class="topbar-link me-4" href="tel:+38514816574"><i class="fa-solid fa-phone"></i> +385 1 48 16 574</a>

            <a class="topbar-link d-none d-xl-inline-block" href="mailto:info@antikvarijat-biblos.hr"><i class="fa-solid fa-envelope"></i> info@antikvarijat-biblos.hr</a>
        </div>
        <div class="ms-3 text-nowrap">
            <a class="topbar-link me-4" href="https://www.google.com/maps/place/Biblos/@45.810942,15.9794894,17.53z/data=!4m5!3m4!1s0x4765d7aac4f8b023:0xb60bceb791b31ede!8m2!3d45.8106161!4d15.9816921?hl=hr" target="_blank"><i class="fa-solid fa-location-dot"></i> {{ __('front.general.address_value') }} </a>
            <a class="topbar-link d-none d-md-inline-block me-0" href="{{ \App\Helpers\LocaleHelper::route('kontakt') }}"><i class="fa-solid fa-clock"></i> {{ __('front.general.opening_hours_short') }}</a>
        </div>
    </div>
</div>

@include('front.layouts.modals.login')

<div id="agapp">
    @include('front.layouts.partials.header')

    @yield('content')

    @unless (request()->routeIs([
        'kosarica', 'naplata', 'pregled', 'checkout', 'checkout.*',
        'en.kosarica', 'en.naplata', 'en.pregled', 'en.checkout', 'en.checkout.*',
    ]))
        @include('front.layouts.partials.newsletter')
    @endunless

    @include('front.layouts.partials.footer')

</div>

<button type="button"
        class="cookie-consent-trigger"
        aria-label="{{ $isEnglish ? 'Cookie settings' : 'Postavke kolačića' }}"
        title="{{ $isEnglish ? 'Cookie settings' : 'Postavke kolačića' }}"
        data-cookie-consent-trigger>
    <i class="fa-solid fa-cookie-bite" aria-hidden="true"></i>
</button>

<!-- Back To Top Button-->
<a class="btn-scroll-top" href="#top" data-scroll><span class="btn-scroll-top-tooltip text-muted fs-sm me-2">Top</span><i class="btn-scroll-top-icon fa-solid fa-arrow-up"></i></a>
<!-- Vendor scrits: js libraries and plugins-->
<script src="{{ \App\Helpers\Asset::url('js/jquery/jquery-2.1.1.min.js') }}"></script>
<script src="{{ \App\Helpers\Asset::url('js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ \App\Helpers\Asset::url('js/tiny-slider.js') }}"></script>
<script src="{{ \App\Helpers\Asset::url('js/smooth-scroll.polyfills.min.js') }}"></script>

<!-- Main theme script-->

<script>
    window.AppLocale = @json(app()->getLocale());
    window.FrontTranslations = @json(trans('front'), JSON_UNESCAPED_UNICODE);
    window.FrontCartUrl = @json(\App\Helpers\LocaleHelper::route('kosarica'));
</script>
<script src="{{ \App\Helpers\Asset::url('js/cart.js') }}"></script>





<script src="{{ \App\Helpers\Asset::url('js/theme.min.js') }}"></script>
@include('front.layouts.partials.cookie-consent')

@guest
    <script>
        (function () {
            const modalElement = document.getElementById('signin-modal');
            const recaptchaSiteKey = @json(config('services.recaptcha.sitekey'));
            const requestedForm = @json(old('_auth_form') ?: (request()->routeIs('register') ? 'signup' : 'signin'));
            const shouldOpen = @json((bool) (session('auth_error') || old('_auth_form') || request()->routeIs('login', 'register')));
            let recaptchaLoader = null;
            let modalInstance = null;

            if (!modalElement) {
                return;
            }

            function activateTab(name) {
                const trigger = document.getElementById(name === 'signup' ? 'pills-signup-tab' : 'pills-signin-tab');

                if (trigger) {
                    new bootstrap.Tab(trigger).show();
                }
            }

            function loadRecaptcha() {
                if (!recaptchaSiteKey || (window.grecaptcha && typeof window.grecaptcha.ready === 'function')) {
                    return Promise.resolve(window.grecaptcha || null);
                }

                if (recaptchaLoader) {
                    return recaptchaLoader;
                }

                recaptchaLoader = new Promise(function (resolve, reject) {
                    const script = document.createElement('script');
                    script.src = 'https://www.google.com/recaptcha/api.js?render=' + encodeURIComponent(recaptchaSiteKey);
                    script.async = true;
                    script.defer = true;
                    script.onload = function () { resolve(window.grecaptcha); };
                    script.onerror = reject;
                    document.head.appendChild(script);
                });

                return recaptchaLoader;
            }

            function refreshRecaptcha() {
                const input = document.getElementById('auth-recaptcha');

                if (!input || !window.grecaptcha || !recaptchaSiteKey) {
                    return;
                }

                window.grecaptcha.ready(function () {
                    window.grecaptcha.execute(recaptchaSiteKey, { action: 'register' }).then(function (token) {
                        input.value = token || '';
                    });
                });
            }

            modalElement.addEventListener('show.bs.modal', function (event) {
                const form = event.relatedTarget && event.relatedTarget.getAttribute('data-auth-tab');
                activateTab(form || requestedForm || 'signin');

                loadRecaptcha().then(refreshRecaptcha).catch(function () {});
            });

            modalElement.querySelectorAll('.password-visibility-toggle').forEach(function (toggle) {
                toggle.addEventListener('click', function () {
                    const input = document.getElementById(toggle.getAttribute('aria-controls'));
                    const icon = toggle.querySelector('i');

                    if (!input) {
                        return;
                    }

                    const isVisible = input.type === 'text';
                    input.type = isVisible ? 'password' : 'text';
                    toggle.setAttribute('aria-pressed', isVisible ? 'false' : 'true');

                    if (icon) {
                        icon.classList.toggle('fa-eye', isVisible);
                        icon.classList.toggle('fa-eye-slash', !isVisible);
                    }
                });
            });

            modalElement.querySelectorAll('form.needs-validation').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }

                    form.classList.add('was-validated');
                });
            });

            if (shouldOpen) {
                activateTab(requestedForm || 'signin');
                modalInstance = new bootstrap.Modal(modalElement);
                modalInstance.show();
            }
        })();
    </script>
@endguest

<script>
    $(() => {
        $('#search-input').on('keyup', (e) => {
            if (e.keyCode == 13) {
                e.preventDefault();
                $('search-form').submit();
            }
        })
    });
</script>



@stack('js_after')
<div id="search_overlay"
     class="search-overlay position-fixed top-0 start-0 w-100 h-100 bg-semi-transparent d-none"></div>
</body>
</html>
