<!DOCTYPE html>
<html lang="hr">
<head>
    @php
        $defaultTitle = 'Antikvarijat Biblos - Knjige, vedute i zemljovidi';
        $defaultDescription = 'Dobrodošli na stranice Antikvarijata Biblos, Palmotićeva 28, Zagreb. Radno vrijeme pon-pet 09-20h, sub 09-14h.';
        $title = trim($__env->yieldContent('title')) ?: $defaultTitle;
        $description = trim($__env->yieldContent('description')) ?: $defaultDescription;
        $canonicalUrl = trim($__env->yieldContent('canonical')) ?: request()->url();
        $defaultNoIndexRoutes = [
            'kosarica',
            'naplata',
            'pregled',
            'checkout',
            'checkout.success',
            'checkout.error',
            'moj-racun',
            'moje-narudzbe',
            'login',
            'register',
            'verification.notice',
            'password.request',
            'password.reset',
        ];
        $robots = trim($__env->yieldContent('robots'));
        if (! $robots) {
            $robots = request()->routeIs($defaultNoIndexRoutes)
                ? 'noindex,follow,noarchive'
                : 'index,follow,max-image-preview:large';
        }
        $ogType = trim($__env->yieldContent('og_type')) ?: 'website';
        $ogImage = trim($__env->yieldContent('og_image'));
        $twitterCard = $ogImage ? 'summary_large_image' : 'summary';
        $imagesDomain = config('settings.images_domain');
        $organizationSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Antikvarijat Biblos',
            'url' => $imagesDomain,
            'logo' => $imagesDomain . 'apple-touch-icon.png',
            'email' => 'info@antikvarijat-biblos.hr',
            'telephone' => '+38514816574',
            'sameAs' => [
                'https://www.facebook.com/AntikvarijatBiblos/',
                'https://www.instagram.com/antikvarijat_biblos/',
            ],
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'Palmoticeva 28',
                'addressLocality' => 'Zagreb',
                'postalCode' => '10000',
                'addressCountry' => 'HR',
            ],
        ];
        $webSiteSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'Antikvarijat Biblos',
            'url' => $imagesDomain,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => $imagesDomain . 'pretrazi?' . config('settings.search_keyword') . '={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    @endphp
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <!-- SEO Meta Tags-->
    <meta name="description" content="{{ $description }}">
    <meta name="language" content="hr">
    <meta name="robots" content="{{ $robots }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:locale" content="hr_HR">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:site_name" content="Antikvarijat Biblos">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:image:secure_url" content="{{ $ogImage }}">
    @endif
    <meta name="twitter:card" content="{{ $twitterCard }}">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    @if ($ogImage)
        <meta name="twitter:image" content="{{ $ogImage }}">
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
    <script type="application/ld+json">{!! json_encode($organizationSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <script type="application/ld+json">{!! json_encode($webSiteSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

    <!-- Vendor Styles including: Font Icons, Plugins, etc.-->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <!-- Main Theme Styles + Bootstrap-->
    <link rel="stylesheet" media="screen" href="{{ asset('css/theme.min.css?v=' . filemtime(public_path('css/theme.min.css'))) }}">
    <link rel="stylesheet" media="screen" href="{{ config('settings.images_domain') . 'css/tiny-slider.css?v=1.2' }}"/>
    <style>
        #gdpr-cookie-message {
            position: fixed;
            left: 30px;
            bottom: 30px;
            max-width: 375px;
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0px 0px 20px rgba(0,0,0,.2);
            margin-left: 0px;
            z-index:10000;

        }
        #gdpr-cookie-message h4 {
            color: #373f50;

            font-size: 18px;
            font-weight: 500;
            margin-bottom: 10px;
        }
        #gdpr-cookie-message h5 {
            color: #373f50;

            font-size: 15px;
            font-weight: 500;
            margin-bottom: 10px;
        }
        #gdpr-cookie-message p, #gdpr-cookie-message ul {
            color: #373f50;
            font-size: 13px;
            line-height: 1.5em;
            margin-bottom: 20px;
        }
        #gdpr-cookie-message p:last-child {
            margin-bottom: 0;
            text-align: right;
        }
        #gdpr-cookie-message li {
            width: 49%;
            display: inline-block;
        }
        #gdpr-cookie-message a {
            color: #bf9f4c;
            text-decoration: none;
            font-size: 13px;
            padding-bottom: 2px;
            border-bottom: 1px dotted rgba(255,255,255,0.75);
            transition: all 0.3s ease-in;
        }
        #gdpr-cookie-message a:hover {
            color: #bf9f4c;
            border-bottom-color: #D90700;
            transition: all 0.3s ease-in;
        }
        #gdpr-cookie-message button {
            border: none;
            background: #bf9f4c;
            color: white;

            font-size: 14px;
            padding: 7px;
            border-radius: 3px;
            margin-left: 15px;
            cursor: pointer;
            transition: all 0.3s ease-in;
        }
        #gdpr-cookie-message button:hover {
            background: white;
            color: #bf9f4c;
            transition: all 0.3s ease-in;
        }
        button#gdpr-cookie-advanced {
            background: white;
            color:#bf9f4c;
        }
        #gdpr-cookie-message button:disabled {
            opacity: 0.3;
        }
        #gdpr-cookie-message input[type="checkbox"] {
            float: none;
            margin-top: 0;
            margin-right: 5px;
        }

        @media(max-width:400px) {
            #gdpr-cookie-message {
                position: fixed;
                left: 10px;
                bottom: 10px;
                max-width: 95vw;
                background: #f6f9fc;
                padding: 20px;
                border-radius: 5px;
                margin-left: 0px;
                z-index: 10000;
                box-shadow: 0px 0px 20px rgba(0,0,0,.2);
            }
        }

    </style>



    @if (config('app.env') == 'production')
        <script>
            window.dataLayer = window.dataLayer || [];
            window.gtag = window.gtag || function () {
                window.dataLayer.push(arguments);
            };
            window.__biblosTracking = {
                gtmId: 'GTM-TV7RKFH',
                facebookPixelId: '659899245170060',
                loaded: {
                    gtm: false,
                    facebook: false
                }
            };
        </script>
        @yield('google_data_layer')
    @endif

    @stack('css_after')

    <style>
        [v-cloak] { display:none !important; }
    </style>



</head>
<!-- Body-->
<body class="paper-white-bck">

<!-- Topbar-->
<div class="topbar topbar-light  d-none d-md-block" style="background-image: url({{ asset('media/img/farmer.png') }});background-repeat: repeat">
    <div class="container">
        <div class="topbar-text text-nowrap">
            <a class="topbar-link me-4" href="tel:+38514816574"><i class="ci-phone"></i> +385 1 48 16 574</a>

            <a class="topbar-link d-none d-xl-inline-block" href="mailto:info@antikvarijat-biblos.hr"><i class="ci-mail"></i> info@antikvarijat-biblos.hr</a>
        </div>
        <div class="ms-3 text-nowrap">
            <a class="topbar-link me-4" href="https://www.google.com/maps/place/Biblos/@45.810942,15.9794894,17.53z/data=!4m5!3m4!1s0x4765d7aac4f8b023:0xb60bceb791b31ede!8m2!3d45.8106161!4d15.9816921?hl=hr" target="_blank"><i class="ci-location"></i> Palmotićeva 28, Zagreb </a>
            <a class="topbar-link d-none d-md-inline-block me-0" href="{{ route('kontakt') }}"><i class="ci-time"></i> PON-PET: 9-20 | SUB: 9-14</a>
        </div>
    </div>
</div>

<div id="agapp">
    @include('front.layouts.partials.header')

    @yield('content')

    @include('front.layouts.partials.newsletter')

    @include('front.layouts.partials.footer')

    @include('front.layouts.partials.handheld')
</div>

<!-- Back To Top Button-->
<a class="btn-scroll-top" href="#top" data-scroll><span class="btn-scroll-top-tooltip text-muted fs-sm me-2">Top</span><i class="btn-scroll-top-icon ci-arrow-up"></i></a>
<!-- Vendor scrits: js libraries and plugins-->
<script src="{{ asset('js/jquery/jquery-2.1.1.min.js?v=1.3') }}"></script>
<script src="{{ asset('js/jquery.ihavecookies.js?v=1.32') }}"></script>
<script src="{{ asset('js/bootstrap.bundle.min.js?v=1.3') }}"></script>
<script src="{{ asset('js/tiny-slider.js?v=1.2') }}"></script>
<script src="{{ asset('js/smooth-scroll.polyfills.min.js?v=1.3') }}"></script>

<!-- Main theme script-->

<script src="{{ asset('js/cart.js?v=' . filemtime(public_path('js/cart.js'))) }}"></script>





<script src="{{ asset('js/theme.min.js') }}"></script>
@if (config('app.env') == 'production')
    <script>
        (function () {
            var tracking = window.__biblosTracking;

            function getCookie(name) {
                var match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '=([^;]*)'));
                return match ? decodeURIComponent(match[1]) : null;
            }

            function getConsentPrefs() {
                var raw = getCookie('cookieControlPrefs');

                if (!raw) {
                    return [];
                }

                try {
                    var prefs = JSON.parse(raw);
                    return Array.isArray(prefs) ? prefs : [];
                } catch (error) {
                    return [];
                }
            }

            function hasConsent(type) {
                return getConsentPrefs().indexOf(type) !== -1;
            }

            function schedule(task) {
                if (window.requestIdleCallback) {
                    window.requestIdleCallback(task, { timeout: 4000 });
                    return;
                }

                window.setTimeout(task, 2500);
            }

            function loadScript(src) {
                return new Promise(function (resolve, reject) {
                    var script = document.createElement('script');
                    script.async = true;
                    script.src = src;
                    script.onload = resolve;
                    script.onerror = reject;
                    document.head.appendChild(script);
                });
            }

            function updateGoogleConsent() {
                window.gtag('consent', 'default', {
                    ad_storage: 'denied',
                    ad_user_data: 'denied',
                    ad_personalization: 'denied',
                    analytics_storage: 'denied',
                    wait_for_update: 500
                });

                window.gtag('consent', 'update', {
                    ad_storage: hasConsent('marketing') ? 'granted' : 'denied',
                    ad_user_data: hasConsent('marketing') ? 'granted' : 'denied',
                    ad_personalization: hasConsent('marketing') ? 'granted' : 'denied',
                    analytics_storage: hasConsent('analytics') ? 'granted' : 'denied'
                });
            }

            function loadGtm() {
                if (tracking.loaded.gtm || (!hasConsent('analytics') && !hasConsent('marketing'))) {
                    return;
                }

                tracking.loaded.gtm = true;
                updateGoogleConsent();
                window.dataLayer.push({
                    'gtm.start': new Date().getTime(),
                    event: 'gtm.js'
                });

                loadScript('https://www.googletagmanager.com/gtm.js?id=' + tracking.gtmId).catch(function () {
                    tracking.loaded.gtm = false;
                });
            }

            function loadFacebookPixel() {
                if (tracking.loaded.facebook || !hasConsent('marketing')) {
                    return;
                }

                tracking.loaded.facebook = true;

                !function(f,b,e,v,n,t,s)
                {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
                    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
                    n.queue=[];t=b.createElement(e);t.async=!0;
                    t.src=v;s=b.getElementsByTagName(e)[0];
                    s.parentNode.insertBefore(t,s)}(window, document,'script',
                    'https://connect.facebook.net/en_US/fbevents.js');
                fbq('init', tracking.facebookPixelId);
                fbq('track', 'PageView');
            }

            window.loadBiblosTracking = function () {
                schedule(function () {
                    loadGtm();
                    loadFacebookPixel();
                });
            };
        }());
    </script>
@endif
<script type="text/javascript">
    $(document).ready(function() {
        $('body').ihavecookies({

            delay: 600,
            expires: 90,

            onAccept: function(){
                if (window.loadBiblosTracking) {
                    window.loadBiblosTracking();
                }

            },
            uncheckBoxes: false
        });

        if (window.loadBiblosTracking) {
            window.loadBiblosTracking();
        }
    });
</script>

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
     class="position-fixed top-0 start-0 w-100 h-100 bg-semi-transparent  d-none"
     style="z-index:1040;"></div>
</body>
</html>
