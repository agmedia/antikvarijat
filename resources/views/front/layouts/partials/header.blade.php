<header class="shadow-sm navbar-sticky bg-dark">
    <!-- GORNJI RED: logo + VIDLJIV SEARCH + toolbar -->
    <div class="navbar navbar-expand-lg navbar-light bg-light paper-white-bck front-header-main">
        <div class="container front-header-row">
            @php
                $isEnglish = app()->getLocale() === \App\Helpers\LocaleHelper::ENGLISH_LOCALE;
                $languageSwitcherUrls = $languageSwitcherUrls ?? \App\Helpers\LocaleHelper::languageSwitcherUrls();
            @endphp

            <!-- Logo -->
            <a class="navbar-brand d-none d-sm-block flex-shrink-0 me-3 p-0" href="{{ \App\Helpers\LocaleHelper::route('index') }}">
                <img src="{{ asset('media/img/logodark.svg') }}" width="180" height="76" alt="Antikvarijat Biblos">
            </a>
            <a class="navbar-brand d-sm-none me-2 p-0 front-header-logo-mobile" href="{{ \App\Helpers\LocaleHelper::route('index') }}">
                <img src="{{ asset('media/img/logodark.svg') }}" width="140" alt="Antikvarijat Biblos">
            </a>

            <!-- VIDLJIV SEARCH (desktop) -->
            <form action="{{ \App\Helpers\LocaleHelper::route('pretrazi') }}" id="search-form-first" method="get" class="d-none d-lg-flex flex-nowrap mx-3 mx-lg-5 flex-grow-1"  role="search">

                <div class="dropdown w-100">
                <div class="input-group ">
                    <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y text-muted fs-base ms-3"></i>
                    <input class="form-control rounded-start ps-5" type="text"
                           name="{{ config('settings.search_keyword') }}"
                           value="{{ request()->query('pojam') ?: '' }}"
                           placeholder="{{ __('front.search.placeholder') }}" id="search_box" data-toggle="dropdown" aria-haspopup="true" autocomplete="off" aria-expanded="false" onkeyup="javascript:load_data(this.value)">
                    <button type="submit" class="btn btn-primary btn-lg fs-base"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
                <div id="search_result" class="live-search"></div>
                </div>
            </form>

           {{--  <form action="{{ route('pretrazi') }}" id="search-form-first" class="w-100 d-none d-lg-flex flex-nowrap mx-4" method="get">
                <div class="dropdown w-100">
                    <div class="input-group "><i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3"></i>
                        <input class="form-control rounded-start w-100" type="text" name="{{ config('settings.search_keyword') }}" value="{{ request()->query('pojam') ?: '' }}" placeholder="Search books" id="search_box" data-toggle="dropdown" aria-haspopup="true" autocomplete="off" aria-expanded="false" onkeyup="javascript:load_data(this.value)">
                    </div>

                    <div id="search_result" class="live-search"></div>
                </div>
            </form>--}}

            <!-- Toolbar -->
            <div class="navbar-toolbar d-flex align-items-center front-header-toolbar">
                <button class="navbar-toggler front-header-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapseMain">
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

                <div class="dropdown ms-3 front-header-language">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle front-language-toggle" type="button" data-bs-toggle="dropdown">
                        {{ strtoupper(app()->getLocale()) }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @foreach ($languageSwitcherUrls as $language)
                            <li>
                                <a class="dropdown-item{{ $language['active'] ? ' active' : '' }}" href="{{ $language['url'] }}">
                                    {{ $language['name'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="ms-3 front-header-cart">
                    <cart-nav-icon carturl="{{ \App\Helpers\LocaleHelper::route('kosarica') }}" checkouturl="{{ \App\Helpers\LocaleHelper::route('naplata') }}"></cart-nav-icon>
                </div>
            </div>

        </div>
    </div>

    <!-- DONJI RED: NAVIGACIJA (ispod searcha) -->
    <div class="navbar navbar-expand-lg navbar-dark bg-dark navbar-xs-light bg-xs-light navbar-stuck-menu mt-0 pt-0 pb-0 ">
        <div class="container">
            <div class="collapse navbar-collapse w-100" id="navbarCollapseMain">

                <!-- Mobile search (ostaje za mobitel) -->
                <form action="{{ \App\Helpers\LocaleHelper::route('pretrazi') }}" id="search-form-mobile" method="get" class="w-100 d-lg-none my-3">
                    <div class="input-group">
                        <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y text-muted fs-base ms-3"></i>
                        <input class="form-control rounded-start ps-5" type="text"
                               name="{{ config('settings.search_keyword') }}"
                               value="{{ request()->query('pojam') ?: '' }}"
                               placeholder="{{ __('front.search.placeholder') }}">
                        <button type="submit" class="btn btn-primary btn-lg fs-base"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </div>
                </form>

                <!-- Linkovi -->
                <ul class="navbar-nav pe-lg-2 me-lg-2 w-100">
                    <!-- Knjige -->
                    <li class="nav-item d-none d-lg-block">
                        <a class="nav-link" href="{{ \App\Helpers\LocaleHelper::route('catalog.route', ['group' => 'knjige']) }}">
                            <i class="fa-regular fa-books d-none d-xl-inline-block align-middle me-1 icon-gold" aria-hidden="true"></i>
                            {{ __('front.nav.books') }}
                        </a>
                    </li>
                    <li class="nav-item d-lg-none dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fa-regular fa-books d-none d-xl-inline-block align-middle me-1 icon-gold" aria-hidden="true"></i>
                            {{ __('front.nav.books') }}
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ \App\Helpers\LocaleHelper::route('catalog.route', ['group' => 'knjige']) }}">{{ __('front.nav.all_books') }}</a></li>
                            @foreach($knjige as $navitem)
                                <li><a class="dropdown-item" href="{{ \App\Helpers\LocaleHelper::categoryUrl($navitem) }}">{{ $navitem->title }}</a></li>
                            @endforeach
                        </ul>
                    </li>

                    <!-- Zemljovidi i vedute -->
                    <li class="nav-item d-none d-lg-block">
                        <a class="nav-link" href="{{ \App\Helpers\LocaleHelper::route('catalog.route', ['group' => 'zemljovidi-i-vedute']) }}">
                            <i class="fa-regular fa-map d-none d-xl-inline-block align-middle me-1 icon-gold" aria-hidden="true"></i>{{ __('front.nav.maps_and_vedute') }}
                        </a>
                    </li>
                    <li class="nav-item d-lg-none dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fa-regular fa-map d-none d-xl-inline-block align-middle me-1 icon-gold" aria-hidden="true"></i>{{ __('front.nav.maps_and_vedute') }}
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ \App\Helpers\LocaleHelper::route('catalog.route', ['group' => 'zemljovidi-i-vedute']) }}">{{ __('front.nav.all_maps_and_vedute') }}</a></li>
                            @foreach($zemljovidi_vedute as $nav_item)
                                <li><a class="dropdown-item" href="{{ \App\Helpers\LocaleHelper::categoryUrl($nav_item) }}">{{ $nav_item->title }}</a></li>
                            @endforeach
                        </ul>
                    </li>

                    <!-- Ostalo -->
                    <li class="nav-item"><a class="nav-link" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.author') }}"><i class="fa-regular fa-user d-none d-xl-inline-block align-middle me-1 icon-gold" aria-hidden="true"></i>{{ __('front.nav.authors') }}</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.publisher') }}"><i class="fa-regular fa-building d-none d-xl-inline-block align-middle me-1 icon-gold" aria-hidden="true"></i>{{ __('front.nav.publishers') }}</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.blog') }}"><i class="fa-regular fa-newspaper d-none d-xl-inline-block align-middle me-1 icon-gold" aria-hidden="true"></i>{{ __('front.nav.blog') }}</a></li>

                    <!-- Mobile-only dodatni linkovi -->
                    <li class="nav-item ms-lg-auto"><a class="nav-link" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.page',['page' => 'o-nama']) }}"><i class="fa-regular fa-circle-info d-none d-xl-inline-block align-middle me-1 icon-gold" aria-hidden="true"></i>{{ __('front.nav.about_us') }}</a></li>
                    <li class="nav-item d-lg-none"><a class="nav-link" href="{{ \App\Helpers\LocaleHelper::route('catalog.route.blog') }}">{{ __('front.nav.media') }}</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ \App\Helpers\LocaleHelper::route('otkup.knjiga') }}"><i class="fa-regular fa-book-open d-none d-xl-inline-block align-middle me-1 icon-gold" aria-hidden="true"></i>{{ __('front.nav.book_purchase') }}</a></li>
                    <li class="nav-item "><a class="nav-link" href="{{ \App\Helpers\LocaleHelper::route('faq') }}"><i class="fa-regular fa-circle-question d-none d-xl-inline-block align-middle me-1 icon-gold" aria-hidden="true"></i>{{ __('front.nav.faq') }}</a></li>
                    <li class="nav-item "><a class="nav-link" href="{{ \App\Helpers\LocaleHelper::route('kontakt') }}"><i class="fa-regular fa-envelope d-none d-xl-inline-block align-middle me-1 icon-gold" aria-hidden="true"></i>{{ __('front.nav.contact') }}</a></li>
                </ul>

            </div>
        </div>
    </div>
</header>

@push('js_after')
    <script>
        const DEBOUNCE_MS = 200;
        let t = null;

        function debouncedLoad(q){ clearTimeout(t); t = setTimeout(()=>load_data(q), DEBOUNCE_MS); }

        function escapeHtml(s){ return String(s ?? '')
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }


        // Close helpers
        function closeSearch(){
            $('#search_result').removeClass('show').empty();
            $('#search_overlay').addClass('d-none');
            $('#search_box').attr('aria-expanded', 'false');
        }

        // Overlay klik zatvara
        $(document).on('click', '#search_overlay', closeSearch);

        // ESC zatvara
        $(document).on('keydown', function(e){
            if(e.key === 'Escape') closeSearch();
        });

        // klik izvan forme zatvara
        $(document).on('click', function(e){
            const $form = $('#search-form-first');
            if(!$form.is(e.target) && $form.has(e.target).length === 0){
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

        function load_data(query) {
            if (query.length > 2) {
                let all = SEARCH_URL + '?pojam=' + encodeURIComponent(query);

                $.ajax({
                    method: 'get',
                    url: '{{ route('api.front.autocomplete') }}' + '?pojam_api=' + encodeURIComponent(query)+ '&group=' + encodeURIComponent(CAT_GROUP) + '&locale=' + encodeURIComponent(SEARCH_LOCALE),
                    success: function(json, textStatus, xhr) {

                        // pokušaj pročitati ukupan broj iz HTTP headera (helper ga šalje u legacy modu)
                        const headerTotal = parseInt(xhr.getResponseHeader('X-Total-Count') || '0', 10);

                        console.log(headerTotal);

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

                        $('#search_result').html(html).addClass('show');
                        $('#search_overlay').removeClass('d-none'); // overlay ostaje proziran ali klikatljiv
                        $('#search_box').attr('aria-expanded', 'true');
                    },

                    error: function(xhr, ajaxOptions, thrownError) {
                        console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                        $('#search_result').html('<div class="result-text text-danger">' + TEXT_SEARCH_ERROR + '</div>').addClass('show');
                        $('#search_overlay').removeClass('d-none');
                        $('#search_box').attr('aria-expanded', 'true');
                    }
                });
            } else {
                closeSearch();
            }
        }

        // ako želiš debounce na keyup bez mijenjanja HTML-a:
        document.getElementById('search_box')?.addEventListener('input', function(e){
            debouncedLoad(e.target.value);
        });
    </script>
@endpush
