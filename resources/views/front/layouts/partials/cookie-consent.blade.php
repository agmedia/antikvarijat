@php
    $isEnglishCookieConsent = app()->getLocale() === \App\Helpers\LocaleHelper::ENGLISH_LOCALE;
    $cookieCopy = $isEnglishCookieConsent
        ? [
            'title' => 'Cookies for easier browsing',
            'message' => 'Biblos uses cookies to keep the website working properly, improve search, and provide more useful recommendations.',
            'accept_all' => 'Accept all',
            'necessary_only' => 'Necessary only',
            'settings' => 'Settings',
            'preferences_title' => 'Choose cookies',
            'save' => 'Save selection',
            'necessary_title' => 'Necessary cookies',
            'necessary_description' => '<strong>Biblos session and XSRF protection</strong> — keep the cart, login, and forms secure and working properly. These cookies are always enabled.',
            'analytics_title' => 'Analytics',
            'analytics_description' => '<strong>Google Analytics 4 via Google Tag Manager</strong> — measures visits and webshop events, including product views, <em>add_to_cart</em>, checkout, and purchases.',
            'marketing_title' => 'Marketing',
            'marketing_description' => '<strong>Google Ads and Meta Pixel</strong> — measure campaign performance and enable more relevant advertising on Google, Facebook, and Instagram.',
        ]
        : [
            'title' => 'Kolačići za ugodnije listanje',
            'message' => 'Biblos koristi kolačiće kako bi stranica radila kako treba, pretraga bila brža, a preporuke korisnije.',
            'accept_all' => 'Prihvati sve',
            'necessary_only' => 'Samo nužni',
            'settings' => 'Postavke',
            'preferences_title' => 'Odaberi kolačiće',
            'save' => 'Spremi odabir',
            'necessary_title' => 'Nužni kolačići',
            'necessary_description' => '<strong>Biblos session i XSRF zaštita</strong> — čuvaju košaricu, prijavu i obrasce sigurnima i funkcionalnima. Ovi su kolačići uvijek uključeni.',
            'analytics_title' => 'Analitika',
            'analytics_description' => '<strong>Google Analytics 4 putem Google Tag Managera</strong> — mjeri posjete i događaje webshopa, uključujući pregled proizvoda, <em>add_to_cart</em>, naplatu i kupnju.',
            'marketing_title' => 'Marketing',
            'marketing_description' => '<strong>Google Ads i Meta Pixel</strong> — mjere uspješnost kampanja i omogućuju relevantnije oglase na Googleu, Facebooku i Instagramu.',
        ];
@endphp

<script>
    (() => {
        window.cookieAnalyticsAllowed = window.cookieAnalyticsAllowed === true;
        window.cookieMarketingAllowed = window.cookieMarketingAllowed === true;
        window.canTrackAnalytics = () => window.cookieAnalyticsAllowed === true;

        const syncConsent = () => {
            if (!window.CookieConsent) {
                return;
            }

            const analyticsGranted = window.CookieConsent.acceptedCategory('analytics');
            const marketingGranted = window.CookieConsent.acceptedCategory('marketing');

            window.cookieAnalyticsAllowed = analyticsGranted;
            window.cookieMarketingAllowed = marketingGranted;
            window.canTrackAnalytics = () => window.cookieAnalyticsAllowed === true;

            if (typeof window.updateGoogleConsentFromCookie === 'function') {
                window.updateGoogleConsentFromCookie(analyticsGranted, marketingGranted);
            }

            if (typeof window.updateFacebookConsentFromCookie === 'function') {
                window.updateFacebookConsentFromCookie(marketingGranted);
            }
        };

        const cookieConsentConfig = {
            disablePageInteraction: true,
            guiOptions: {
                consentModal: {
                    layout: 'box',
                    position: 'middle center',
                    equalWeightButtons: true,
                    flipButtons: false
                },
                preferencesModal: {
                    layout: 'box',
                    position: 'middle center'
                }
            },
            categories: {
                necessary: {
                    enabled: true,
                    readOnly: true
                },
                analytics: {
                    enabled: false,
                    readOnly: false
                },
                marketing: {
                    enabled: false,
                    readOnly: false
                }
            },
            onFirstConsent: syncConsent,
            onConsent: syncConsent,
            onChange: syncConsent,
            language: {
                default: @json(app()->getLocale()),
                translations: {
                    @json(app()->getLocale()): {
                        consentModal: {
                            title: @json($cookieCopy['title']),
                            description: @json($cookieCopy['message']),
                            acceptAllBtn: @json($cookieCopy['accept_all']),
                            acceptNecessaryBtn: @json($cookieCopy['necessary_only']),
                            showPreferencesBtn: @json($cookieCopy['settings'])
                        },
                        preferencesModal: {
                            title: @json($cookieCopy['preferences_title']),
                            acceptAllBtn: @json($cookieCopy['accept_all']),
                            acceptNecessaryBtn: @json($cookieCopy['necessary_only']),
                            savePreferencesBtn: @json($cookieCopy['save']),
                            sections: [
                                {
                                    title: @json($cookieCopy['necessary_title']),
                                    description: @json($cookieCopy['necessary_description']),
                                    linkedCategory: 'necessary'
                                },
                                {
                                    title: @json($cookieCopy['analytics_title']),
                                    description: @json($cookieCopy['analytics_description']),
                                    linkedCategory: 'analytics'
                                },
                                {
                                    title: @json($cookieCopy['marketing_title']),
                                    description: @json($cookieCopy['marketing_description']),
                                    linkedCategory: 'marketing'
                                }
                            ]
                        }
                    }
                }
            }
        };

        const ensureCookieConsentAssets = () => {
            if (window.CookieConsent && typeof window.CookieConsent.run === 'function') {
                return Promise.resolve();
            }

            return Promise.reject(new Error('Cookie consent assets are not available.'));
        };

        const runCookieConsent = ({ showConsentIfNeeded = true } = {}) => {
            if (!window.CookieConsent || typeof window.CookieConsent.run !== 'function') {
                return;
            }

            if (window.__cookieConsentInitialized === true) {
                syncConsent();
                return;
            }

            window.__cookieConsentInitialized = true;
            window.CookieConsent.run(cookieConsentConfig);
            syncConsent();

            if (showConsentIfNeeded && !window.CookieConsent.validConsent()) {
                window.CookieConsent.show();
            }
        };

        const bootCookieConsent = () => {
            ensureCookieConsentAssets()
                .then(runCookieConsent)
                .catch(() => {
                    window.__cookieConsentInitialized = false;
                });
        };

        const openCookiePreferences = () => {
            ensureCookieConsentAssets().then(() => {
                runCookieConsent({ showConsentIfNeeded: false });

                window.setTimeout(() => {
                    if (window.CookieConsent && typeof window.CookieConsent.showPreferences === 'function') {
                        window.CookieConsent.showPreferences();
                        return;
                    }

                    if (window.CookieConsent && typeof window.CookieConsent.show === 'function') {
                        window.CookieConsent.show();
                    }
                }, 60);
            });
        };

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-cookie-consent-trigger]');

            if (!trigger) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            openCookiePreferences();
        });

        const hasStoredCookieConsent = () => document.cookie
            .split(';')
            .some((entry) => entry.trim().startsWith('cc_cookie='));

        const scheduleCookieConsentBoot = () => {
            if (hasStoredCookieConsent()) {
                bootCookieConsent();
                return;
            }

            let booted = false;
            const runBootOnce = () => {
                if (booted) {
                    return;
                }

                booted = true;
                bootCookieConsent();
            };

            ['pointerdown', 'keydown', 'touchstart', 'scroll'].forEach((eventName) => {
                window.addEventListener(eventName, runBootOnce, { once: true, passive: true });
            });

            window.setTimeout(runBootOnce, 6000);
        };

        if (document.readyState === 'complete') {
            scheduleCookieConsentBoot();
        } else {
            window.addEventListener('load', scheduleCookieConsentBoot, { once: true });
        }
    })();
</script>
