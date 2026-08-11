<header class="shadow-sm navbar-sticky bg-dark">
    <!-- GORNJI RED: logo + VIDLJIV SEARCH + toolbar -->
    <div class="navbar navbar-expand-lg navbar-light bg-light paper-white-bck front-header-main">
        <div class="container front-header-row">
            @php
                $isEnglish = app()->getLocale() === \App\Helpers\LocaleHelper::ENGLISH_LOCALE;
                $languageSwitcherUrls = $languageSwitcherUrls ?? \App\Helpers\LocaleHelper::languageSwitcherUrls();
                $isCatalogRoute = request()->routeIs('catalog.route', 'en.catalog.route');
                $activeCatalogGroup = request()->route('group') ?? ($group ?? null);
                $isBooksActive = $isCatalogRoute && in_array($activeCatalogGroup, ['knjige', 'books'], true);
                $isMapsActive = $isCatalogRoute && in_array($activeCatalogGroup, ['zemljovidi-i-vedute', 'maps-and-views'], true);
                $initialMobileNavigationView = $isBooksActive ? 'books' : ($isMapsActive ? 'maps' : 'main');
            @endphp

            <!-- Logo -->
            <a class="navbar-brand d-none d-lg-block flex-shrink-0 me-3 p-0" href="{{ \App\Helpers\LocaleHelper::route('index') }}">
                <img src="{{ asset('media/img/logodark.svg') }}" width="180" height="76" alt="Antikvarijat Biblos">
            </a>
            <a class="navbar-brand d-lg-none me-2 p-0 front-header-logo-mobile" href="{{ \App\Helpers\LocaleHelper::route('index') }}">
                <img src="{{ asset('media/img/logobijeli.svg') }}" width="140" alt="Antikvarijat Biblos">
            </a>

            <!-- VIDLJIV SEARCH (desktop) -->
            <form action="{{ \App\Helpers\LocaleHelper::route('pretrazi') }}" id="search-form-first" method="get" class="front-search-form d-none d-lg-flex flex-nowrap mx-3 mx-lg-5 flex-grow-1" role="search" data-autosuggest-form>

                <div class="dropdown w-100">
                <div class="input-group ">
                    <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y text-muted fs-base ms-3"></i>
                    <input class="form-control rounded-start ps-5" type="text"
                           name="{{ config('settings.search_keyword') }}"
                           value="{{ request()->query('pojam') ?: '' }}"
                           placeholder="{{ __('front.search.placeholder') }}" id="search_box" autocomplete="off" aria-autocomplete="list" aria-controls="search_result" aria-expanded="false" data-autosuggest-input data-results-id="search_result">
                    <button type="submit" class="btn btn-primary btn-lg fs-base" aria-label="{{ __('front.search.search') }}"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
                </div>
                <div id="search_result" class="live-search" role="listbox"></div>
                </div>
            </form>

            <!-- Toolbar -->
            <div class="navbar-toolbar d-flex align-items-center front-header-toolbar">
                <button class="navbar-toggler front-header-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNavigation" aria-controls="mobileNavigation" aria-label="{{ __('front.nav.menu') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                @auth
                    <a class="navbar-tool ms-3 front-header-account" href="{{ \App\Helpers\LocaleHelper::route('moj-racun') }}" aria-label="{{ __('front.nav.account') }}">
                        <span class="navbar-tool-tooltip">{{ __('front.nav.account') }}</span>
                        <div class="navbar-tool-icon-box"><i class="navbar-tool-icon fa-regular fa-user front-header-account-icon" aria-hidden="true"></i></div>
                    </a>
                @else
                    <button class="navbar-tool ms-3 front-header-account border-0 bg-transparent p-0" type="button" data-auth-tab="signin" data-bs-toggle="modal" data-bs-target="#signin-modal" aria-label="{{ __('front.auth.login_title') }}">
                        <span class="navbar-tool-tooltip">{{ __('front.auth.login_title') }}</span>
                        <span class="navbar-tool-icon-box"><i class="navbar-tool-icon fa-regular fa-user front-header-account-icon" aria-hidden="true"></i></span>
                    </button>
                @endauth

                <nav class="front-language-switch d-none d-lg-inline-flex" aria-label="{{ __('front.nav.language') }}">
                    @foreach ($languageSwitcherUrls as $language)
                        <a
                            class="front-language-switch__option{{ $language['active'] ? ' is-active' : '' }}"
                            href="{{ $language['url'] }}"
                            lang="{{ $language['locale'] }}"
                            title="{{ $language['name'] }}"
                            @if($language['active']) aria-current="page" @endif
                        >{{ strtoupper($language['locale']) }}</a>
                    @endforeach
                </nav>

                <div class="ms-3 front-header-cart">
                    <cart-nav-icon carturl="{{ \App\Helpers\LocaleHelper::route('kosarica') }}" checkouturl="{{ \App\Helpers\LocaleHelper::route('naplata') }}"></cart-nav-icon>
                </div>
            </div>

        </div>
    </div>

    <!-- Stalno vidljiva mobilna pretraga -->
    <div class="mobile-header-search d-lg-none">
        <div class="container">
            <form action="{{ \App\Helpers\LocaleHelper::route('pretrazi') }}" id="search-form-mobile" method="get" class="mobile-header-search-form" role="search" data-autosuggest-form>
                <label class="visually-hidden" for="mobile_search_box">{{ __('front.search.placeholder') }}</label>
                <div class="mobile-header-search-field">
                    <input id="mobile_search_box" type="search"
                           name="{{ config('settings.search_keyword') }}"
                           value="{{ request()->query('pojam') ?: '' }}"
                           placeholder="{{ __('front.search.placeholder') }}"
                           autocomplete="off" aria-autocomplete="list" aria-controls="mobile_search_result" aria-expanded="false" data-autosuggest-input data-results-id="mobile_search_result">
                    <button class="mobile-search-close" type="button" aria-label="{{ __('front.search.close') }}" data-mobile-search-close>
                        <i class="fa-regular fa-xmark" aria-hidden="true"></i>
                    </button>
                    <button type="submit" aria-label="{{ __('front.search.search') }}">
                        <i class="fa-regular fa-magnifying-glass" aria-hidden="true"></i>
                    </button>
                </div>
                <div id="mobile_search_result" class="live-search mobile-search-results" role="listbox"></div>
            </form>
        </div>
    </div>

    <!-- Desktop navigacija -->
    <div class="navbar navbar-expand-lg navbar-dark bg-dark navbar-stuck-menu d-none d-lg-flex mt-0 pt-0 pb-0">
        <div class="container">
            <div class="navbar-collapse w-100">
                <ul class="navbar-nav pe-lg-2 me-lg-2 w-100">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ \App\Helpers\LocaleHelper::route('catalog.route', ['group' => 'knjige']) }}">
                            <i class="fa-regular fa-books d-none d-xl-inline-block align-middle me-1 icon-gold" aria-hidden="true"></i>
                            {{ __('front.nav.books') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ \App\Helpers\LocaleHelper::route('catalog.route', ['group' => 'zemljovidi-i-vedute']) }}">
                            <i class="fa-regular fa-map d-none d-xl-inline-block align-middle me-1 icon-gold" aria-hidden="true"></i>{{ __('front.nav.maps_and_vedute') }}
                        </a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.author') }}"><i class="fa-regular fa-user d-none d-xl-inline-block align-middle me-1 icon-gold" aria-hidden="true"></i>{{ __('front.nav.authors') }}</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.publisher') }}"><i class="fa-regular fa-building d-none d-xl-inline-block align-middle me-1 icon-gold" aria-hidden="true"></i>{{ __('front.nav.publishers') }}</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.blog') }}"><i class="fa-regular fa-newspaper d-none d-xl-inline-block align-middle me-1 icon-gold" aria-hidden="true"></i>{{ __('front.nav.blog') }}</a></li>
                    <li class="nav-item ms-lg-auto"><a class="nav-link" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.page',['page' => 'o-nama']) }}"><i class="fa-regular fa-circle-info d-none d-xl-inline-block align-middle me-1 icon-gold" aria-hidden="true"></i>{{ __('front.nav.about_us') }}</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ \App\Helpers\LocaleHelper::route('otkup.knjiga') }}"><i class="fa-regular fa-book-open d-none d-xl-inline-block align-middle me-1 icon-gold" aria-hidden="true"></i>{{ __('front.nav.book_purchase') }}</a></li>
                    <li class="nav-item "><a class="nav-link" href="{{ \App\Helpers\LocaleHelper::route('faq') }}"><i class="fa-regular fa-circle-question d-none d-xl-inline-block align-middle me-1 icon-gold" aria-hidden="true"></i>{{ __('front.nav.faq') }}</a></li>
                    <li class="nav-item "><a class="nav-link" href="{{ \App\Helpers\LocaleHelper::route('kontakt') }}"><i class="fa-regular fa-envelope d-none d-xl-inline-block align-middle me-1 icon-gold" aria-hidden="true"></i>{{ __('front.nav.contact') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Mobilna navigacija -->
    <div class="offcanvas mobile-navigation d-lg-none" tabindex="-1" id="mobileNavigation" aria-label="{{ __('front.nav.menu') }}">
        <div class="mobile-navigation-shell" data-active-view="{{ $initialMobileNavigationView }}" data-initial-view="{{ $initialMobileNavigationView }}">
            <section class="mobile-navigation-view{{ $initialMobileNavigationView === 'main' ? ' is-active' : '' }}" data-mobile-nav-view="main" aria-hidden="{{ $initialMobileNavigationView === 'main' ? 'false' : 'true' }}">
                <div class="mobile-navigation-header">
                    <a class="mobile-navigation-brand" href="{{ \App\Helpers\LocaleHelper::route('index') }}" aria-label="Antikvarijat Biblos">
                        <img src="{{ asset('media/img/logobijeli.svg') }}" width="112" alt="">
                    </a>
                    <div class="mobile-navigation-header-actions">
                        <div class="mobile-language-top" aria-label="{{ __('front.nav.language') }}">
                            @foreach ($languageSwitcherUrls as $language)
                                <a class="{{ $language['active'] ? 'is-active' : '' }}" href="{{ $language['url'] }}" @if($language['active']) aria-current="page" @endif>{{ strtoupper($language['locale']) }}</a>
                            @endforeach
                        </div>
                        <button class="mobile-navigation-close" type="button" data-bs-dismiss="offcanvas" aria-label="{{ __('front.nav.close_menu') }}">
                            <i class="fa-regular fa-xmark" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="mobile-navigation-scroll">
                    <nav aria-label="{{ __('front.nav.menu') }}">
                        <ul class="mobile-menu-list">
                            <li><button class="{{ $isBooksActive ? 'is-active' : '' }}" type="button" data-mobile-nav-open="books" aria-controls="mobileNavigationBooks"><span class="mobile-menu-icon"><i class="fa-regular fa-books" aria-hidden="true"></i></span><span>{{ __('front.nav.books') }}</span><i class="fa-regular fa-chevron-right" aria-hidden="true"></i></button></li>
                            <li><button class="{{ $isMapsActive ? 'is-active' : '' }}" type="button" data-mobile-nav-open="maps" aria-controls="mobileNavigationMaps"><span class="mobile-menu-icon"><i class="fa-regular fa-map" aria-hidden="true"></i></span><span>{{ __('front.nav.maps_and_vedute') }}</span><i class="fa-regular fa-chevron-right" aria-hidden="true"></i></button></li>
                            <li class="mobile-menu-separator" role="separator"></li>
                            <li><a class="{{ request()->routeIs('catalog.route.author', 'en.catalog.route.author') ? 'is-active' : '' }}" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.author') }}"><span class="mobile-menu-icon"><i class="fa-regular fa-user-pen" aria-hidden="true"></i></span><span>{{ __('front.nav.authors') }}</span><i class="fa-regular fa-chevron-right" aria-hidden="true"></i></a></li>
                            <li><a class="{{ request()->routeIs('catalog.route.publisher', 'en.catalog.route.publisher') ? 'is-active' : '' }}" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.publisher') }}"><span class="mobile-menu-icon"><i class="fa-regular fa-building" aria-hidden="true"></i></span><span>{{ __('front.nav.publishers') }}</span><i class="fa-regular fa-chevron-right" aria-hidden="true"></i></a></li>
                            <li><a class="{{ request()->routeIs('catalog.route.blog', 'en.catalog.route.blog') ? 'is-active' : '' }}" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.blog') }}"><span class="mobile-menu-icon"><i class="fa-regular fa-newspaper" aria-hidden="true"></i></span><span>{{ __('front.nav.blog') }}</span><i class="fa-regular fa-chevron-right" aria-hidden="true"></i></a></li>
                            <li class="mobile-menu-separator" role="separator"></li>
                            <li><a href="{{ \App\Helpers\LocaleHelper::route('catalog.route.page',['page' => 'o-nama']) }}"><span class="mobile-menu-icon"><i class="fa-regular fa-circle-info" aria-hidden="true"></i></span><span>{{ __('front.nav.about_us') }}</span><i class="fa-regular fa-chevron-right" aria-hidden="true"></i></a></li>
                            <li><a class="{{ request()->routeIs('otkup.knjiga', 'en.otkup.knjiga') ? 'is-active' : '' }}" href="{{ \App\Helpers\LocaleHelper::route('otkup.knjiga') }}"><span class="mobile-menu-icon"><i class="fa-regular fa-book-open" aria-hidden="true"></i></span><span>{{ __('front.nav.book_purchase') }}</span><i class="fa-regular fa-chevron-right" aria-hidden="true"></i></a></li>
                            <li><a class="{{ request()->routeIs('faq', 'en.faq') ? 'is-active' : '' }}" href="{{ \App\Helpers\LocaleHelper::route('faq') }}"><span class="mobile-menu-icon"><i class="fa-regular fa-circle-question" aria-hidden="true"></i></span><span>{{ __('front.nav.faq') }}</span><i class="fa-regular fa-chevron-right" aria-hidden="true"></i></a></li>
                            <li><a class="{{ request()->routeIs('kontakt', 'en.kontakt') ? 'is-active' : '' }}" href="{{ \App\Helpers\LocaleHelper::route('kontakt') }}"><span class="mobile-menu-icon"><i class="fa-regular fa-envelope" aria-hidden="true"></i></span><span>{{ __('front.nav.contact') }}</span><i class="fa-regular fa-chevron-right" aria-hidden="true"></i></a></li>
                        </ul>
                    </nav>

                </div>
            </section>

            <section class="mobile-navigation-view{{ $initialMobileNavigationView === 'books' ? ' is-active' : '' }}" id="mobileNavigationBooks" data-mobile-nav-view="books" aria-labelledby="mobileNavigationBooksTitle" aria-hidden="{{ $initialMobileNavigationView === 'books' ? 'false' : 'true' }}">
                <div class="mobile-navigation-header mobile-navigation-subheader">
                    <button class="mobile-navigation-back" type="button" data-mobile-nav-back>
                        <i class="fa-regular fa-arrow-left" aria-hidden="true"></i><span>{{ __('front.nav.back') }}</span>
                    </button>
                    <h2 id="mobileNavigationBooksTitle">{{ __('front.nav.books') }}</h2>
                    <button class="mobile-navigation-close" type="button" data-bs-dismiss="offcanvas" aria-label="{{ __('front.nav.close_menu') }}"><i class="fa-regular fa-xmark" aria-hidden="true"></i></button>
                </div>
                <div class="mobile-navigation-scroll mobile-category-view">
                    <label class="mobile-category-filter">
                        <i class="fa-regular fa-magnifying-glass" aria-hidden="true"></i>
                        <span class="visually-hidden">{{ __('front.nav.filter_categories') }}</span>
                        <input type="search" placeholder="{{ __('front.nav.filter_categories') }}" autocomplete="off" data-mobile-category-filter="mobileBookCategoryList">
                    </label>
                    <a class="mobile-category-all" href="{{ \App\Helpers\LocaleHelper::route('catalog.route', ['group' => 'knjige']) }}"><span class="mobile-menu-icon"><i class="fa-regular fa-books" aria-hidden="true"></i></span><span>{{ __('front.nav.all_books') }}</span><i class="fa-regular fa-chevron-right" aria-hidden="true"></i></a>
                    <ul class="mobile-category-list" id="mobileBookCategoryList">
                        @foreach($knjige as $navitem)
                            @php
                                $categoryUrl = rtrim(\App\Helpers\LocaleHelper::categoryUrl($navitem), '/');
                                $isCurrentCategory = request()->url() === $categoryUrl || \Illuminate\Support\Str::startsWith(request()->url(), $categoryUrl . '/');
                            @endphp
                            <li><a class="{{ $isCurrentCategory ? 'is-active' : '' }}" href="{{ $categoryUrl }}" @if($isCurrentCategory) aria-current="page" @endif><span>{{ $navitem->title }}</span><i class="fa-regular fa-chevron-right" aria-hidden="true"></i></a></li>
                        @endforeach
                    </ul>
                    <p class="mobile-category-empty" data-mobile-category-empty hidden>{{ __('front.nav.no_categories') }}</p>
                </div>
            </section>

            <section class="mobile-navigation-view{{ $initialMobileNavigationView === 'maps' ? ' is-active' : '' }}" id="mobileNavigationMaps" data-mobile-nav-view="maps" aria-labelledby="mobileNavigationMapsTitle" aria-hidden="{{ $initialMobileNavigationView === 'maps' ? 'false' : 'true' }}">
                <div class="mobile-navigation-header mobile-navigation-subheader">
                    <button class="mobile-navigation-back" type="button" data-mobile-nav-back>
                        <i class="fa-regular fa-arrow-left" aria-hidden="true"></i><span>{{ __('front.nav.back') }}</span>
                    </button>
                    <h2 id="mobileNavigationMapsTitle">{{ __('front.nav.maps_and_vedute') }}</h2>
                    <button class="mobile-navigation-close" type="button" data-bs-dismiss="offcanvas" aria-label="{{ __('front.nav.close_menu') }}"><i class="fa-regular fa-xmark" aria-hidden="true"></i></button>
                </div>
                <div class="mobile-navigation-scroll mobile-category-view">
                    <label class="mobile-category-filter">
                        <i class="fa-regular fa-magnifying-glass" aria-hidden="true"></i>
                        <span class="visually-hidden">{{ __('front.nav.filter_categories') }}</span>
                        <input type="search" placeholder="{{ __('front.nav.filter_categories') }}" autocomplete="off" data-mobile-category-filter="mobileMapCategoryList">
                    </label>
                    <a class="mobile-category-all" href="{{ \App\Helpers\LocaleHelper::route('catalog.route', ['group' => 'zemljovidi-i-vedute']) }}"><span class="mobile-menu-icon"><i class="fa-regular fa-map" aria-hidden="true"></i></span><span>{{ __('front.nav.all_maps_and_vedute') }}</span><i class="fa-regular fa-chevron-right" aria-hidden="true"></i></a>
                    <ul class="mobile-category-list" id="mobileMapCategoryList">
                        @foreach($zemljovidi_vedute as $nav_item)
                            @php
                                $categoryUrl = rtrim(\App\Helpers\LocaleHelper::categoryUrl($nav_item), '/');
                                $isCurrentCategory = request()->url() === $categoryUrl || \Illuminate\Support\Str::startsWith(request()->url(), $categoryUrl . '/');
                            @endphp
                            <li><a class="{{ $isCurrentCategory ? 'is-active' : '' }}" href="{{ $categoryUrl }}" @if($isCurrentCategory) aria-current="page" @endif><span>{{ $nav_item->title }}</span><i class="fa-regular fa-chevron-right" aria-hidden="true"></i></a></li>
                        @endforeach
                    </ul>
                    <p class="mobile-category-empty" data-mobile-category-empty hidden>{{ __('front.nav.no_categories') }}</p>
                </div>
            </section>
        </div>
    </div>
</header>

@push('js_after')
    <script>
        const DEBOUNCE_MS = 200;
        let searchDebounceTimer = null;
        let activeSearchRequest = null;
        let mobileSearchScrollPosition = 0;

        function debouncedLoad(input){
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(function () {
                load_data(input.value, input.dataset.resultsId, input.id);
            }, DEBOUNCE_MS);
        }

        function escapeHtml(s){ return String(s ?? '')
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }

        function openMobileSearch() {
            if (!window.matchMedia('(max-width: 991.98px)').matches || document.body.classList.contains('mobile-search-open')) {
                return;
            }

            mobileSearchScrollPosition = window.scrollY;
            document.body.style.top = '-' + mobileSearchScrollPosition + 'px';
            document.body.classList.add('mobile-search-open');
        }

        function closeMobileSearch() {
            if (!document.body.classList.contains('mobile-search-open')) {
                return;
            }

            document.body.classList.remove('mobile-search-open');
            document.body.style.top = '';
            window.scrollTo(0, mobileSearchScrollPosition);
        }


        // Close helpers
        function closeSearch(){
            $('.live-search').removeClass('show').empty();
            $('#search_overlay').addClass('d-none');
            $('[data-autosuggest-input]').attr('aria-expanded', 'false');
            closeMobileSearch();
        }

        // Overlay klik zatvara
        $(document).on('click', '#search_overlay', closeSearch);
        $(document).on('click', '[data-mobile-search-close]', closeSearch);

        // ESC zatvara
        $(document).on('keydown', function(e){
            if(e.key === 'Escape') closeSearch();
        });

        // Klik izvan aktivne pretrage zatvara rezultate.
        $(document).on('click', function(e){
            if (!$(e.target).closest('[data-autosuggest-form], .live-search').length) {
                closeSearch();
            }
        });

        const CAT_GROUP = '{{ $group ?? "knjige" }}'; // fallback ako nije definiran
        const SEARCH_LOCALE = '{{ app()->getLocale() }}';
        const SEARCH_URL = '{{ \App\Helpers\LocaleHelper::route('pretrazi') }}';
        const TEXT_FOUND = '{{ __('front.search.found') }}';
        const TEXT_RESULTS = '{{ __('front.search.results') }}';
        const TEXT_PRODUCTS = '{{ __('front.search.products') }}';
        const TEXT_AUTHORS = '{{ __('front.search.authors') }}';
        const TEXT_CATEGORIES = '{{ __('front.search.categories') }}';
        const TEXT_AUTHORS_TITLE = '{{ __('front.search.authors_title') }}';
        const TEXT_CATEGORIES_TITLE = '{{ __('front.search.categories_title') }}';
        const TEXT_PRODUCTS_TITLE = '{{ __('front.search.products_title') }}';
        const TEXT_SOLD_OUT = '{{ __('front.product.sold_out') }}';
        const TEXT_VIEW_ALL = '{{ __('front.search.view_all') }}';
        const TEXT_NO_RESULTS = '{{ __('front.search.no_results') }}';
        const TEXT_SEARCH_ERROR = '{{ __('front.search.error') }}';

        function load_data(query, resultId, inputId) {
            const $result = $('#' + resultId);
            const $input = $('#' + inputId);

            if (query.length > 2) {
                let all = SEARCH_URL + '?pojam=' + encodeURIComponent(query);

                if (activeSearchRequest) {
                    activeSearchRequest.abort();
                }

                activeSearchRequest = $.ajax({
                    method: 'get',
                    url: '{{ route('api.front.autocomplete') }}' + '?pojam_api=' + encodeURIComponent(query)+ '&group=' + encodeURIComponent(CAT_GROUP) + '&locale=' + encodeURIComponent(SEARCH_LOCALE),
                    success: function(json, textStatus, xhr) {

                        // pokušaj pročitati ukupan broj iz HTTP headera (helper ga šalje u legacy modu)
                        const headerTotal = parseInt(xhr.getResponseHeader('X-Total-Count') || '0', 10);

                        let html = '';

                        // Strukturirani format (object) ili legacy (array)?
                        const isStructured = json && (json.counts || json.products || json.authors || json.categories);

                        if (isStructured) {
                            const c = json.counts || {products:0, authors:0, categories:0};
                            // ako postoji headerTotal (>0), koristi njega kao total; inače zbroji counts
                            const total = headerTotal > 0 ? headerTotal : ((c.products|0) + (c.authors|0) + (c.categories|0));

                            // HEADER s countovima
                            html += '<div class="px-3 py-2 border-bottom fs-md text-dark">'
                                + TEXT_FOUND + ': <strong>' + total + '</strong> ' + TEXT_RESULTS + ' '
                                + '(' + TEXT_PRODUCTS + ' ' + (c.products||0) + ', ' + TEXT_AUTHORS + ' ' + (c.authors||0) + ', ' + TEXT_CATEGORIES + ' ' + (c.categories||0) + ')'
                                + '</div>';

                            // AUTORI
                            if (json.authors && json.authors.length > 0) {
                                html += '<div class="px-3  pt-2 pb-2 fw-medium  fs-md bg-secondary text-dark">' + TEXT_AUTHORS_TITLE + '</div>';
                                html += '<ul class="list-group list-group-flush">';
                                json.authors.forEach(function(a){
                                    html += '<li class="list-group-item py-2"><a class="text-dark fs-md" href="'+a.url+'">'+escapeHtml(a.name)+'</a></li>';
                                });
                                html += '</ul>';
                            }

                            // KATEGORIJE
                            if (json.categories && json.categories.length > 0) {
                                html += '<div class="px-3  pt-2 pb-2 fw-medium  fs-md bg-secondary text-dark">' + TEXT_CATEGORIES_TITLE + '</div>';
                                html += '<ul class="list-group list-group-flush cat">';
                                json.categories.forEach(function(cg){
                                    html += '<li class="list-group-item py-2"><a class="text-dark fs-md" href="'+cg.url+'">'+escapeHtml(cg.name)+'</a></li>';
                                });
                                html += '</ul>';
                            }


                            // PROIZVODI (tvoj markup)
                            if (json.products && json.products.length > 0) {
                                html += '<div class="px-3  pt-2 pb-2 fw-medium  fs-md bg-secondary  text-dark">' + TEXT_PRODUCTS_TITLE + '</div>';
                                html += '<table class="px-3 table products"><tbody>';
                                json.products.forEach(function (item) {
                                    html += '<tr>';

                                    html += '<td class="image"><a href="'+item.url+'"><img width="80" alt="'+escapeHtml(item.name)+'" src="'+item.image+'"></a></td>';

                                    // naziv + eventualno rasprodano
                                    html += '<td class="main"><a href="'+item.url+'">'+escapeHtml(item.name)+'<br>';
                                    html += '<small>'+escapeHtml(item.author_title || '')+'</small>';
                                    if (parseInt(item.quantity, 10) <= 0) {
                                        html += '<br><span class="badge badge-xs bg-warning">' + TEXT_SOLD_OUT + '</span>';
                                    }
                                    html += '</a></td>';

                                    html += '<td class="price text-end"><a href="'+item.url+'"><div class="price"><span class="price">'+(item.main_price_text || '')+'</span></div></a></td>';

                                    html += '</tr>';
                                });
                                html += '</tbody></table>';
                            }




                            // FOOTER
                            html += '<div class="result-text"><a href="'+(SEARCH_URL + '?pojam=' + encodeURIComponent(query))+'" class="btn btn-sm btn-primary w-100">' + TEXT_VIEW_ALL + '</a></div>';

                            if (total === 0) {
                                html = '<div class="result-text text-muted p-3">' + TEXT_NO_RESULTS + '</div>';
                            }

                        } else {
                            // LEGACY – json je niz proizvoda
                            const total = headerTotal > 0 ? headerTotal : (Array.isArray(json) ? json.length : 0);

                            if (Array.isArray(json) && json.length > 0) {
                                html += '<div class="px-3 py-2 border-bottom small text-muted">' + TEXT_FOUND + ': <strong>'+ total +'</strong> ' + TEXT_RESULTS + '</div>';
                                html += '<table class="table products"><tbody>';
                                json.slice(0, 15).forEach(function (item) {
                                    html += '<tr>'
                                        +   '<td class="image"><a href="'+item.url+'"><img width="80" alt="'+escapeHtml(item.name)+'" src="'+item.image+'"></a></td>'
                                        +   '<td class="main"><a href="'+item.url+'">'+escapeHtml(item.name)+'<br><small>'+escapeHtml(item.author_title)+'</small><br><small>'+escapeHtml(item.sku)+'</small></a></td>'
                                        +   '<td class="price text-end"><a href="'+item.url+'"><div class="price"><span class="price">'+item.main_price_text+'</span></div></a></td>'
                                        + '</tr>';
                                });
                                html += '</tbody></table>';
                                html += '<div class="result-text"><a href="'+(SEARCH_URL + '?pojam=' + encodeURIComponent(query))+'" class="btn btn-sm btn-primary w-100">' + TEXT_VIEW_ALL + '</a></div>';
                            } else {
                                html += '<div class="result-text text-muted">' + TEXT_NO_RESULTS + '</div>';
                            }


                        }

                        $result.html(html).addClass('show');
                        $('#search_overlay').removeClass('d-none'); // overlay ostaje proziran ali klikatljiv
                        $input.attr('aria-expanded', 'true');

                        if (resultId === 'mobile_search_result') {
                            openMobileSearch();
                        }
                    },

                    error: function(xhr, ajaxOptions, thrownError) {
                        if (xhr.statusText === 'abort') {
                            return;
                        }

                        $result.html('<div class="result-text text-danger">' + TEXT_SEARCH_ERROR + '</div>').addClass('show');
                        $('#search_overlay').removeClass('d-none');
                        $input.attr('aria-expanded', 'true');

                        if (resultId === 'mobile_search_result') {
                            openMobileSearch();
                        }
                    },

                    complete: function () {
                        activeSearchRequest = null;
                    }
                });
            } else {
                closeSearch();
            }
        }

        document.querySelectorAll('[data-autosuggest-input]').forEach(function (input) {
            input.addEventListener('input', function () {
                debouncedLoad(input);
            });
        });

        const mobileNavigation = document.getElementById('mobileNavigation');
        const mobileNavigationShell = mobileNavigation?.querySelector('.mobile-navigation-shell');
        let mobileNavigationTrigger = null;

        function scrollToActiveMobileNavigationItem() {
            const activeView = mobileNavigation?.querySelector('.mobile-navigation-view.is-active');
            const scrollContainer = activeView?.querySelector('.mobile-navigation-scroll');
            const activeItem = scrollContainer?.querySelector('[aria-current="page"]');

            if (!scrollContainer || !activeItem) {
                return;
            }

            const containerRect = scrollContainer.getBoundingClientRect();
            const activeItemRect = activeItem.getBoundingClientRect();
            const centeredScrollTop = scrollContainer.scrollTop
                + activeItemRect.top
                - containerRect.top
                - ((containerRect.height - activeItemRect.height) / 2);

            scrollContainer.scrollTo({
                top: Math.max(0, centeredScrollTop),
                behavior: 'auto'
            });
        }

        function showMobileNavigationView(viewName) {
            if (!mobileNavigationShell) {
                return;
            }

            mobileNavigationShell.dataset.activeView = viewName;
            mobileNavigationShell.querySelectorAll('[data-mobile-nav-view]').forEach(function (view) {
                const isActive = view.dataset.mobileNavView === viewName;
                view.classList.toggle('is-active', isActive);
                view.setAttribute('aria-hidden', isActive ? 'false' : 'true');

                if (isActive) {
                    view.querySelector('.mobile-navigation-scroll')?.scrollTo(0, 0);
                }
            });

            window.requestAnimationFrame(scrollToActiveMobileNavigationItem);
        }

        mobileNavigation?.querySelectorAll('[data-mobile-nav-open]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                mobileNavigationTrigger = trigger;
                showMobileNavigationView(trigger.dataset.mobileNavOpen);
                mobileNavigationShell.querySelector('.mobile-navigation-view.is-active [data-mobile-nav-back]')?.focus();
            });
        });

        mobileNavigation?.querySelectorAll('[data-mobile-nav-back]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                showMobileNavigationView('main');
                mobileNavigationTrigger?.focus();
            });
        });

        mobileNavigation?.querySelectorAll('[data-mobile-category-filter]').forEach(function (input) {
            input.addEventListener('input', function () {
                const list = document.getElementById(input.dataset.mobileCategoryFilter);
                const normalizedQuery = input.value.toLocaleLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
                let visibleCount = 0;

                list?.querySelectorAll('li').forEach(function (item) {
                    const normalizedLabel = item.textContent.toLocaleLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                    const isVisible = normalizedLabel.includes(normalizedQuery);
                    item.hidden = !isVisible;
                    visibleCount += isVisible ? 1 : 0;
                });

                const emptyState = input.closest('.mobile-category-view')?.querySelector('[data-mobile-category-empty]');
                if (emptyState) {
                    emptyState.hidden = visibleCount > 0;
                }
            });
        });

        mobileNavigation?.addEventListener('hidden.bs.offcanvas', function () {
            showMobileNavigationView(mobileNavigationShell?.dataset.initialView || 'main');
            mobileNavigationTrigger = null;

            mobileNavigation.querySelectorAll('[data-mobile-category-filter]').forEach(function (input) {
                input.value = '';
                input.dispatchEvent(new Event('input'));
            });
        });

        mobileNavigation?.addEventListener('shown.bs.offcanvas', function () {
            window.requestAnimationFrame(scrollToActiveMobileNavigationItem);
        });
    </script>
@endpush
