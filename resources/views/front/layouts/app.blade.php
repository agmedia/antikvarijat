<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <title> @yield('title') </title>
    <!-- SEO Meta Tags-->
    <meta name="description" content="@yield('description')">

    <meta name="author" content="Antikvarijat Biblos">
    @stack('meta_tags')
    <!-- Viewport-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta name="facebook-domain-verification" content="16b3jag78m5ywwi9xfdmmt7r4mmsws" />
    <!-- Favicon and Touch Icons-->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ config('settings.images_domain') . 'media/img/favicon-32x32.png' }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ config('settings.images_domain') . 'media/img/favicon-32x32.png' }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ config('settings.images_domain') . 'media/img/favicon-16x16.png' }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ config('settings.images_domain') . 'apple-touch-icon.png' }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ config('settings.images_domain') . 'favicon-32x32.png' }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ config('settings.images_domain') . 'favicon-16x16.png' }}">
    <link rel="mask-icon" href="{{ config('settings.images_domain') . 'safari-pinned-tab.svg' }}" color="#314837">
    <meta name="msapplication-TileColor" content="#314837">
    <meta name="theme-color" content="#ffffff">

    <!-- Vendor Styles including: Font Icons, Plugins, etc.-->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <!-- Main Theme Styles + Bootstrap-->
    <link rel="stylesheet" media="screen" href="{{ config('settings.images_domain') . 'css/theme.min.css?v=1.4' }}">
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
        @yield('google_data_layer')
        <!-- Google Tag Manager -->
            <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
                                                                  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
                    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
                })(window,document,'script','dataLayer','GTM-TV7RKFH');
            </script>

        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-YY35049KQZ"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', 'G-YY35049KQZ');
        </script>
    @endif

    @stack('css_after')

    @if (config('app.env') == 'production')
        <!-- Facebook Pixel Code -->
        <script>
            !function(f,b,e,v,n,t,s)
            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                n.callMethod.apply(n,arguments):n.queue.push(arguments)};
                if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
                n.queue=[];t=b.createElement(e);t.async=!0;
                t.src=v;s=b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t,s)}(window, document,'script',
                'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '659899245170060');
            fbq('track', 'PageView');
        </script>
        <noscript><img height="1" width="1" style="display:none"
                       src="https://www.facebook.com/tr?id=659899245170060&ev=PageView&noscript=1"
            /></noscript>
    @endif

    <style>
        [v-cloak] { display:none !important; }
    </style>

</head>
<!-- Body-->
<body class="handheld-toolbar-enabled">

@if (config('app.env') == 'production')
    <!-- Google Tag Manager (noscript) -->
    <noscript>
        <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-TV7RKFH" height="0" width="0" style="display:none;visibility:hidden"></iframe>
    </noscript>
@endif

<!-- Topbar-->
<div class="topbar topbar-light bg-light d-none d-md-block">
    <div class="container">
        <div class="topbar-text text-nowrap">
            <a class="topbar-link me-4" href="tel:+38514816574"><i class="ci-phone"></i> +385 1 48 16 574</a>
            <a class="topbar-link me-4" href="https://www.google.com/maps/place/Biblos/@45.810942,15.9794894,17.53z/data=!4m5!3m4!1s0x4765d7aac4f8b023:0xb60bceb791b31ede!8m2!3d45.8106161!4d15.9816921?hl=hr" target="_blank"><i class="ci-location"></i> Palmotićeva 28, Zagreb </a>
           <a class="topbar-link d-none d-md-inline-block me-4" href="{{ route('kontakt') }}"><i class="ci-time"></i> PON-PET: 9-20 | SUB: 9-14</a>
            <a class="topbar-link d-none d-xl-inline-block" href="mailto:info@antikvarijat-biblos.hr"><i class="ci-mail"></i> info@antikvarijat-biblos.hr</a>
        </div>
        <div class="ms-3 text-nowrap">
            <a class="topbar-link d-none d-md-inline-block" href="{{ route('faq') }}">Česta pitanja</a>
            <a class="topbar-link ms-3 ps-3 border-start border-dark d-none d-md-inline-block" href="{{ route('catalog.route.page',['page' => 'o-nama']) }}">O nama</a>
            <a class="topbar-link ms-3 ps-3 border-start border-dark d-none d-md-inline-block" href="{{ route('kontakt') }}">Kontakt</a>
        </div>
    </div>
</div>

<div id="agapp">
    @include('front.layouts.partials.header')

    @yield('content')

    @include('front.layouts.partials.footer')

    @include('front.layouts.partials.handheld')
</div>

<!-- Back To Top Button-->
<a class="btn-scroll-top" href="#top" data-scroll><span class="btn-scroll-top-tooltip text-muted fs-sm me-2">Top</span><i class="btn-scroll-top-icon ci-arrow-up"></i></a>
<!-- Vendor Styles including: Font Icons, Plugins, etc.-->
<link rel="stylesheet" media="screen" href="{{ config('settings.images_domain') . 'css/tiny-slider.css?v=1.2' }}"/>
<!-- Vendor scrits: js libraries and plugins-->
<script src="{{ asset('js/jquery/jquery-2.1.1.min.js?v=1.3') }}"></script>
<script src="{{ asset('js/jquery.ihavecookies.js?v=1.32') }}"></script>
<script src="{{ asset('js/bootstrap.bundle.min.js?v=1.3') }}"></script>
<script src="{{ asset('js/tiny-slider.js?v=1.2') }}"></script>
<script src="{{ asset('js/smooth-scroll.polyfills.min.js?v=1.3') }}"></script>

<script src="{{ asset('js/imagesloaded/imagesloaded.pkgd.min.js') }}"></script>
<script src="{{ asset('js/shufflejs/dist/shuffle.min.js') }}"></script>

<!-- Main theme script-->

<script src="{{ asset('js/cart.js?v=2.2.1') }}"></script>

<script src="{{ asset('js/theme.min.js') }}"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('body').ihavecookies({

            delay: 600,
            expires: 90,

            onAccept: function(){
                var myPreferences = $.fn.ihavecookies.cookie();

            },
            uncheckBoxes: false
        });

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

</body>
</html>
