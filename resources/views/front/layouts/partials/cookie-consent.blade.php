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
            'marketing_description' => '<strong>Google Ads, Meta Pixel, and Mailchimp attribution</strong> — measure campaign performance and enable more relevant advertising on Google, Facebook, Instagram, and email.',
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
            'marketing_description' => '<strong>Google Ads, Meta Pixel i Mailchimp atribucija</strong> — mjere uspješnost kampanja i omogućuju relevantnije oglase na Googleu, Facebooku, Instagramu i e-mailu.',
        ];
@endphp

<script>
    (() => {
        window.cookieAnalyticsAllowed = window.cookieAnalyticsAllowed === true;
        window.cookieMarketingAllowed = window.cookieMarketingAllowed === true;
        window.canTrackAnalytics = () => window.cookieAnalyticsAllowed === true;

        const mailchimpAttributionCookies = {
            biblos_mc_cid: 'mc_cid'
        };

        const validMailchimpIdentifier = (value) => /^[a-z0-9_-]{1,100}$/i.test(value || '');
        const pendingMailchimpCampaignId = new URL(window.location.href).searchParams.get('mc_cid');
        const mailchimpConsentCookie = 'biblos_marketing_consent';
        const mailchimpCookieMaxAge = 60 * 60 * 24 * 30;

        const setMailchimpConsentState = (state) => {
            const secure = window.location.protocol === 'https:' ? '; Secure' : '';
            document.cookie = `${mailchimpConsentCookie}=${state}; Max-Age=${mailchimpCookieMaxAge}; Path=/; SameSite=Lax${secure}`;
        };

        const clearMailchimpAttribution = () => {
            Object.keys(mailchimpAttributionCookies).forEach((cookieName) => {
                document.cookie = `${cookieName}=; Max-Age=0; Path=/; SameSite=Lax`;
            });

            setMailchimpConsentState('denied');
        };

        const syncMailchimpAttribution = (marketingGranted) => {
            if (!marketingGranted) {
                clearMailchimpAttribution();
                return;
            }

            const secure = window.location.protocol === 'https:' ? '; Secure' : '';
            setMailchimpConsentState('granted');

            Object.entries(mailchimpAttributionCookies).forEach(([cookieName, parameterName]) => {
                const value = parameterName === 'mc_cid'
                    ? pendingMailchimpCampaignId
                    : new URL(window.location.href).searchParams.get(parameterName);

                if (validMailchimpIdentifier(value)) {
                    document.cookie = `${cookieName}=${encodeURIComponent(value)}; Max-Age=${mailchimpCookieMaxAge}; Path=/; SameSite=Lax${secure}`;
                }
            });
        };

        const syncConsent = () => {
            if (!window.CookieConsent) {
                return;
            }

            const analyticsGranted = window.CookieConsent.acceptedCategory('analytics');
            const marketingGranted = window.CookieConsent.acceptedCategory('marketing');

            window.cookieAnalyticsAllowed = analyticsGranted;
            window.cookieMarketingAllowed = marketingGranted;
            window.canTrackAnalytics = () => window.cookieAnalyticsAllowed === true;
            syncMailchimpAttribution(marketingGranted);

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
            if (hasStoredCookieConsent() || validMailchimpIdentifier(pendingMailchimpCampaignId)) {
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
