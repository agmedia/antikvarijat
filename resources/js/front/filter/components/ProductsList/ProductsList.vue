<template>
    <section class="col-lg-9 catalog-products-section">
        <h2 class="visually-hidden">{{ heading }}</h2>
        <!-- Toolbar-->
        <div class="catalog-products-toolbar d-flex justify-content-center justify-content-sm-between align-items-center pt-2 pb-4 pb-sm-5">
            <div class="catalog-products-toolbar__controls d-flex flex-wrap">
                <div class="me-2 d-lg-none">
                    <a class="btn collapsed catalog-filter-trigger" href="#shop-sidebar" v-on:click.prevent="openFilters" aria-expanded="false" aria-controls="shop-sidebar" :aria-label="filterButtonLabel">
                        <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                        <span class="visually-hidden">{{ filterButtonLabel }}</span>
                        <span class="catalog-filter-trigger-count" v-if="activeFilterCount">{{ activeFilterCount }}</span>
                    </a>
                </div>
                <div class="catalog-sort-control d-flex align-items-center flex-nowrap me-3 me-sm-4 pb-3">
                    <label class="text-light opacity-75 text-nowrap fs-sm me-2 d-none d-sm-block" for="sorting"></label>
                    <select class="form-select" v-model="sorting">
                        <option value="" disabled>{{ labels.sort }}</option>
                        <option value="novi">{{ labels.newest }}</option>
                        <option value="price_up">{{ labels.lowestPrice }}</option>
                        <option value="price_down">{{ labels.highestPrice }}</option>
                        <option value="naziv_up">A - Ž</option>
                        <option value="naziv_down">Ž - A</option>
                    </select>
                </div>
            </div>
            <div class="catalog-products-toolbar__aside d-flex pb-3">
                <div class="catalog-view-switch d-flex d-lg-none" role="group" :aria-label="viewLabels.group">
                    <button type="button" class="catalog-view-switch__button" :class="{ 'is-active': mobileColumns === 1 }" v-on:click="setMobileColumns(1)" :aria-pressed="mobileColumns === 1 ? 'true' : 'false'" :aria-label="viewLabels.oneColumn">
                        <i class="fa-regular fa-square" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="catalog-view-switch__button" :class="{ 'is-active': mobileColumns === 2 }" v-on:click="setMobileColumns(2)" :aria-pressed="mobileColumns === 2 ? 'true' : 'false'" :aria-label="viewLabels.twoColumns">
                        <i class="fa-regular fa-grid-2" aria-hidden="true"></i>
                    </button>
                </div>
                <span class="fs-sm text-light btn btn-primary btn-sm text-nowrap ms-2 d-none d-lg-block">{{ labels.total }} {{ formatNumber(products.total || 0) }} {{ labels.items }}</span>
            </div>
        </div>

        <!-- Products grid-->

        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-4 mb-3 px-2 catalog-products-grid" :class="'catalog-products-grid--mobile-' + mobileColumns" v-if="products.total">



            <div class="col px-2 mb-4" v-for="(product, index) in products.data" :key="product.id">
                <div class="card product-card shadow mb-2 catalog-product-card">
                    <span class="badge  bg-dark mt-1 ms-1 badge-shadow" v-if="product.special">-{{ ($store.state.service.getDiscountAmount(product.price, product.special)) }}%</span>
                    <div class="product-thumb">
                        <a :href="origin + product.url">
                        <img
                            :loading="index < 4 ? 'eager' : 'lazy'"
                            :fetchpriority="index < 1 ? 'high' : 'auto'"
                            decoding="async"
                            sizes="(max-width: 575px) 50vw, (max-width: 991px) 33vw, (max-width: 1399px) 25vw, 250px"
                            :srcset="productSrcset(product)"
                            :src="productImage(product)"
                            width="250"
                            height="300"
                            :alt="product.name">
                        </a>
                        <span
                            v-if="product.sales_badge_type"
                            class="product-sales-badge"
                            :class="'product-sales-badge--' + product.sales_badge_type"
                            role="img"
                            :aria-label="salesBadgeLabel(product)"
                            :data-tooltip="salesBadgeLabel(product)"
                            tabindex="0">
                            <i class="fa-duotone" :class="salesBadgeIcon(product)" aria-hidden="true"></i>
                        </span>
                    </div>
                    <div class="card-body pt-2">
                        <div class="d-flex flex-wrap justify-content-between align-items-start pb-2" v-if="productMeta(product)">
                            <div class="text-muted fs-xs me-1">
                                <a class="product-meta fw-medium" :href="origin + productMeta(product).url">{{ productMeta(product).title }}</a>
                            </div>

                        </div>
                        <a
                            v-if="reviewCount(product) > 0"
                            class="d-inline-flex align-items-center gap-1 mb-2 text-decoration-none"
                            :href="origin + product.url + '#reviews'"
                            :aria-label="reviewRatingLabel(product)">
                            <span class="star-rating" aria-hidden="true">
                                <i
                                    v-for="star in 5"
                                    :key="star"
                                    class="star-rating-icon"
                                    :class="star <= Math.round(reviewAverage(product)) ? 'fa-solid fa-star active' : 'fa-duotone fa-star'"></i>
                            </span>
                            <span class="fs-xs text-muted">{{ formatReviewAverage(product) }} ({{ reviewCount(product) }})</span>
                        </a>
                        <h3 class="product-title fs-sm mb-0"><a :href="origin + product.url">{{ product.name }}</a></h3>
                        <div class="d-flex flex-wrap justify-content-between align-items-center" v-if="product.card_category">
                            <div class="fs-sm me-2 one-line">
                                <i class="fa-duotone fa-books text-muted fs-xs" aria-hidden="true"></i>
                                <a class="product-category-link fs-xs ms-1" :href="product.card_category.url">{{ product.card_category.title }}</a>
                            </div>
                        </div>

                        <div class="catalog-product-purchase mt-2">
                            <div class="catalog-product-prices">
                                <div class="d-flex flex-wrap justify-content-between align-items-center price-box">
                                    <div class="bg-faded-accent text-accent fs-sm rounded-1 py-1 px-2 text-decoration-line-through" v-if="product.special">{{ product.main_price_text }}</div>
                                    <div class="bg-faded-accent text-accent fs-sm rounded-1 py-1 px-2" v-if="product.special">{{ product.main_special_text }}</div>
                                    <div class="bg-faded-accent text-accent fs-sm rounded-1 py-1 px-2" v-if="!product.special">{{ product.main_price_text }}</div>
                                </div>

                                <div class="d-flex flex-wrap justify-content-between align-items-center price-box mt-2" v-if="product.secondary_price">
                                    <div class="bg-faded-accent text-accent fs-sm rounded-1 py-1 px-2 text-decoration-line-through" v-if="product.special">{{ product.secondary_price_text }}</div>
                                    <div class="bg-faded-accent text-accent fs-sm rounded-1 py-1 px-2" v-if="product.special">{{ product.secondary_special_text }}</div>
                                    <div class="bg-faded-accent text-accent fs-sm rounded-1 py-1 px-2" v-if="!product.special">{{ product.secondary_price_text }}</div>
                                </div>
                            </div>

                            <div class="product-floating-btn">
                               <button type="button" class="btn btn-primary btn-sm" v-on:click="add(product.id)">+<i class="fa-regular fa-bag-shopping ms-1" aria-hidden="true"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" v-if="!products_loaded">
            <div class="col-md-12 d-flex justify-content-center mt-4">
                <div class="spinner-border text-muted opacity-75" role="status" style="width: 9rem; height: 9rem;"></div>
            </div>
            <div class="col-md-12 d-flex justify-content-center mt-4">
                <p class="fs-3 fw-lighter opacity-50">{{ labels.loading }}</p>
            </div>
        </div>

        <div class="catalog-pagination-wrap" v-if="products.total">
            <pagination :data="products" align="center" :show-disabled="true" :limit="2" @pagination-change-page="getProductsPage"></pagination>
            <p class="catalog-pagination-summary mb-0">
                {{ labels.shown }}
                <strong>{{ formatNumber(products.from || 0) }}–{{ formatNumber(products.to || 0) }}</strong>
                {{ labels.of }}
                <strong>{{ formatNumber(products.total || 0) }}</strong>
                {{ hr_total }}
            </p>
        </div>

        <div class="col-md-12 px-2 mb-4" v-if="products_loaded && search_zero_result">
            <h2>{{ labels.noSearchResultsTitle }}</h2>
            <p>{{ labels.yourSearchFor }} <mark>{{ search_query }}</mark> {{ labels.zeroResults }}</p>
            <h4 class="h5">{{ labels.tipsTitle }}</h4>
            <ul class="list-style">
                <li>{{ labels.tipSpelling }}</li>
                <li>{{ labels.tipShorter }}</li>
                <li>{{ labels.tipBroader }}</li>
            </ul>
            <hr class="d-sm-none">
        </div>

        <div class="col-md-12 px-2 mb-4" v-if="products_loaded && navigation_zero_result">
            <h2>{{ labels.noProductsTitle }}</h2>
            <p>{{ labels.noProductsText }}</p>
            <hr class="d-sm-none">
        </div>


    </section>
</template>

<script>
    export default {
        name: 'ProductsList',
        props: {
            ids: String,
            group: String,
            cat: String,
            subcat: String,
            author: String,
            publisher: String,
            heading: {
                type: String,
                default: ''
            },
            locale: {
                type: String,
                default: 'hr'
            },
            initialProducts: {
                type: Object,
                default: () => ({})
            }
        },
        //
        computed: {
            labels() {
                const en = this.locale === 'en';
                const t = (window.FrontTranslations && window.FrontTranslations.js && window.FrontTranslations.js.products)
                    ? window.FrontTranslations.js.products
                    : {};

                return en ? {
                    sort: t.sort || 'Sort',
                    newest: t.newest || 'Newest',
                    lowestPrice: t.lowest_price || 'Lowest price',
                    highestPrice: t.highest_price || 'Highest price',
                    total: t.total || 'Total',
                    items: t.items || 'items',
                    unknown: t.unknown || 'Unknown',
                    loading: t.loading || 'Loading books...',
                    shown: t.shown || 'Showing',
                    to: t.to || 'to',
                    of: t.of || 'of',
                    result: t.result || 'result',
                    results: t.results || 'results',
                    noSearchResultsTitle: t.no_search_results_title || 'No search results',
                    yourSearchFor: t.your_search_for || 'Your search for',
                    zeroResults: t.zero_results || 'found 0 results.',
                    tipsTitle: t.tips_title || 'Tips',
                    tipSpelling: t.tip_spelling || 'Double-check the spelling.',
                    tipShorter: t.tip_shorter || 'Limit your search to one or two terms.',
                    tipBroader: t.tip_broader || 'Use broader terms to find similar and related items.',
                    noProductsTitle: t.no_products_title || 'There are currently no products',
                    noProductsText: t.no_products_text || 'Browse another category or try the search.'
                } : {
                    sort: t.sort || 'Sort',
                    newest: t.newest || 'Newest',
                    lowestPrice: t.lowest_price || 'Lowest price',
                    highestPrice: t.highest_price || 'Highest price',
                    total: t.total || 'Total',
                    items: t.items || 'items',
                    unknown: t.unknown || 'Unknown',
                    loading: t.loading || 'Loading books...',
                    shown: t.shown || 'Showing',
                    to: t.to || 'to',
                    of: t.of || 'of',
                    result: t.result || 'result',
                    results: t.results || 'results',
                    noSearchResultsTitle: t.no_search_results_title || 'No search results',
                    yourSearchFor: t.your_search_for || 'Your search for',
                    zeroResults: t.zero_results || 'found 0 results.',
                    tipsTitle: t.tips_title || 'Tips',
                    tipSpelling: t.tip_spelling || 'Double-check the spelling.',
                    tipShorter: t.tip_shorter || 'Limit your search to one or two terms.',
                    tipBroader: t.tip_broader || 'Use broader terms to find similar and related items.',
                    noProductsTitle: t.no_products_title || 'There are currently no products',
                    noProductsText: t.no_products_text || 'Browse another category or try the search.'
                };
            },

            numberLocale() {
                return this.locale === 'en' ? 'en-US' : 'hr-HR';
            },

            viewLabels() {
                const t = (window.FrontTranslations && window.FrontTranslations.js && window.FrontTranslations.js.filter)
                    ? window.FrontTranslations.js.filter
                    : {};

                return {
                    group: t.view || (this.locale === 'en' ? 'Product view' : 'Prikaz proizvoda'),
                    oneColumn: t.one_column || (this.locale === 'en' ? 'One column' : 'Jedan stupac'),
                    twoColumns: t.two_columns || (this.locale === 'en' ? 'Two columns' : 'Dva stupca')
                };
            },

            filterButtonLabel() {
                const t = (window.FrontTranslations && window.FrontTranslations.js && window.FrontTranslations.js.filter)
                    ? window.FrontTranslations.js.filter
                    : {};

                return t.filters || (this.locale === 'en' ? 'Filters' : 'Filtri');
            },

            activeFilterCount() {
                const query = this.$route.query || {};
                const countValues = value => value ? String(value).split('+').filter(Boolean).length : 0;

                return countValues(query.autor)
                    + countValues(query.nakladnik)
                    + countValues(query.prevoditelj)
                    + (query.start ? 1 : 0)
                    + (query.end ? 1 : 0)
                    + (query.pismo ? 1 : 0)
                    + (query.stanje ? 1 : 0)
                    + (query.uvez ? 1 : 0);
            }
        },
        //
        data() {
            const bootstrappedProducts = this.initialProducts && Object.keys(this.initialProducts).length
                ? this.initialProducts
                : null;

            return {
                products: bootstrappedProducts || {},
                autor: '',
                nakladnik: '',
                prevoditelj: '',
                start: '',
                end: '',
                pismo: '',
                stanje: '',
                uvez: '',
                sorting: '',
                search_query: '',
                page: 1,
                origin: location.origin + '/',
                hr_total: this.locale === 'en' ? 'results' : 'rezultata',
                products_loaded: !!bootstrappedProducts,
                search_zero_result: false,
                navigation_zero_result: false,
                mobileColumns: 2,
                mobileViewport: window.matchMedia('(max-width: 991.98px)').matches,
            }
        },
        //
        watch: {
            sorting(value) {
                this.setQueryParam('sort', value);
            },
            $route(params) {
                this.syncQuery(params);
                this.loadProductsForCurrentState();
            }
        },
        //
        mounted() {
            this.mobileViewportMedia = window.matchMedia('(max-width: 991.98px)');
            this.mobileViewportMedia.addEventListener('change', this.handleMobileViewportChange);
            this.restoreMobileColumns();
            this.syncQuery(this.$route);

            if (this.products_loaded) {
                this.checkHrTotal();
                this.checkSpecials();
                this.setZeroState();
                return;
            }

            this.loadProductsForCurrentState();
        },

        beforeDestroy() {
            if (this.mobileViewportMedia) {
                this.mobileViewportMedia.removeEventListener('change', this.handleMobileViewportChange);
            }
        },

        methods: {
            handleMobileViewportChange(event) {
                this.mobileViewport = event.matches;
            },

            productImage(product) {
                const image = String(product.image || '');

                if (this.mobileViewport && this.mobileColumns === 1) {
                    return image;
                }

                return image.replace(/\.webp(?=([?#]|$))/i, '-thumb.webp');
            },

            productSrcset(product) {
                const image = String(product.image || '');
                const thumb = image.replace(/\.webp(?=([?#]|$))/i, '-thumb.webp');

                return thumb === image ? image : `${thumb} 250w, ${image} 600w`;
            },

            salesBadgeLabel(product) {
                const labels = (window.FrontTranslations && window.FrontTranslations.sales_badges)
                    ? window.FrontTranslations.sales_badges
                    : {};

                if (product.sales_badge_type === 'bestseller') {
                    return labels.bestseller || 'Bestseller';
                }

                return labels.popular || 'Popular';
            },

            salesBadgeIcon(product) {
                if (product.sales_badge_type === 'bestseller') return 'fa-tag';

                return 'fa-fire-flame-curved';
            },

            restoreMobileColumns() {
                try {
                    const savedColumns = Number(window.localStorage.getItem('catalog-mobile-columns'));

                    if ([1, 2].includes(savedColumns)) this.mobileColumns = savedColumns;
                } catch (error) {
                    this.mobileColumns = 2;
                }
            },

            setMobileColumns(columns) {
                this.mobileColumns = columns === 1 ? 1 : 2;

                try {
                    window.localStorage.setItem('catalog-mobile-columns', String(this.mobileColumns));
                } catch (error) {
                    // Prikaz i dalje radi kada preglednik blokira lokalnu pohranu.
                }
            },

            openFilters(event) {
                const sidebar = document.getElementById('shop-sidebar');
                const trigger = event && event.currentTarget ? event.currentTarget : null;

                if (!sidebar) return;

                sidebar.classList.remove('collapse', 'collapsing');
                sidebar.style.height = '';
                sidebar.classList.add('show');
                sidebar.setAttribute('aria-hidden', 'false');
                document.body.classList.add('catalog-filter-open');

                if (trigger) {
                    trigger.classList.remove('collapsed');
                    trigger.setAttribute('aria-expanded', 'true');
                }

                this.$nextTick(() => {
                    const closeButton = sidebar.querySelector('.catalog-filter-close');
                    if (closeButton) closeButton.focus();
                });
            },

            /**
             *
             */
            getProducts() {
                this.requestProducts();
            },

            requestProducts(page = 1) {
                this.search_zero_result = false;
                this.navigation_zero_result = false;
                this.products_loaded = false;
                let params = this.setParams();
                const pageSuffix = page > 1 ? '?page=' + page : '';

                axios.post('filter/getProducts' + pageSuffix, { params }).then(response => {
                    this.products_loaded = true;
                    this.products = response.data;
                    this.page = page;
                    this.checkHrTotal();
                    this.checkSpecials();
                    this.setZeroState();
                });
            },

            /**
             *
             * @param page
             */
            getProductsPage(page = 1) {
                this.page = page;
                this.setQueryParam('page', page);
                window.scrollTo({top: 0, behavior: 'smooth'});
            },

            /**
             *
             * @param type
             * @param value
             */
            setQueryParam(type, value) {
                this.$router.push({query: this.resolveQuery()}).catch(()=>{});

                if (value == '' || value == 1) {
                    this.$router.push({query: this.resolveQuery()}).catch(()=>{});
                }
            },

            /**
             *
             * @return {{}}
             */
            resolveQuery() {
                let params = {
                    start: this.start,
                    end: this.end,
                    pismo: this.pismo,
                    stanje: this.stanje,
                    uvez: this.uvez,
                    autor: this.autor,
                    nakladnik: this.nakladnik,
                    prevoditelj: this.prevoditelj,
                    sort: this.sorting,
                    pojam: this.search_query,
                    page: this.page > 1 ? this.page : ''
                };

                return Object.entries(params).reduce((acc, [key, val]) => {
                    if (!val) return acc
                    return { ...acc, [key]: val }
                }, {});
            },

            /**
             *
             * @param params
             */
            syncQuery(params) {
                this.start = params.query.start ? params.query.start : '';
                this.end = params.query.end ? params.query.end : '';
                this.pismo = params.query.pismo ? params.query.pismo : '';
                this.stanje = params.query.stanje ? params.query.stanje : '';
                this.uvez = params.query.uvez ? params.query.uvez : '';
                this.autor = params.query.autor ? params.query.autor : '';
                this.nakladnik = params.query.nakladnik ? params.query.nakladnik : '';
                this.prevoditelj = params.query.prevoditelj ? params.query.prevoditelj : '';
                this.page = params.query.page ? Number(params.query.page) : 1;
                this.search_query = params.query.pojam ? params.query.pojam : '';
                this.sorting = params.query.sort ? params.query.sort : '';
            },

            loadProductsForCurrentState() {
                this.requestProducts(this.page || 1);
            },

            /**
             *
             * @return {{cat: String, start: string, pojam: string, subcat: String, end: string, sort: string, nakladnik: string, autor: string, group: String}}
             */
            setParams() {
                let params = {
                    ids: this.ids,
                    group: this.group,
                    cat: this.cat,
                    subcat: this.subcat,
                    autor: this.autor,
                    nakladnik: this.nakladnik,
                    prevoditelj: this.prevoditelj,
                    start: this.start,
                    end: this.end,
                    pismo: this.pismo,
                    stanje: this.stanje,
                    uvez: this.uvez,
                    sort: this.sorting,
                    pojam: this.search_query,
                    locale: this.locale
                };

                if (this.author != '') {
                    params.autor = this.author;
                }
                if (this.publisher != '') {
                    params.nakladnik = this.publisher;
                }

                return params;
            },


            checkSpecials() {
                if (!this.products.data) {
                    return;
                }

                for (let i = 0; i < this.products.data.length; i++) {
                    if (Number(this.products.data[i].main_price) <= Number(this.products.data[i].main_special)) {
                        this.products.data[i].special = false;
                    }
                }
            },

            /**
             *
             */
            checkHrTotal() {
                if (!this.products.total && this.products.total !== 0) {
                    return;
                }

                this.hr_total = this.labels.results;

                if (this.locale !== 'en' && (this.products.total).toString().slice(-1) == '1') {
                    this.hr_total = this.labels.result;
                }
            },

            formatNumber(number) {
                return Number(number).toLocaleString(this.numberLocale);
            },

            productMeta(product) {
                if (this.publisher && product.publisher) {
                    return product.publisher;
                }

                const author = product.author || null;

                return author && this.hasMeaningfulLabel(author.title) ? author : null;
            },

            hasMeaningfulLabel(label) {
                return /[\p{L}\p{N}]/u.test(String(label || '').trim());
            },

            reviewCount(product) {
                return Number(product.approved_reviews_count || 0);
            },

            reviewAverage(product) {
                return Number(product.approved_reviews_average || 0);
            },

            formatReviewAverage(product) {
                return this.reviewAverage(product).toLocaleString(this.numberLocale, {
                    minimumFractionDigits: 1,
                    maximumFractionDigits: 1
                });
            },

            reviewRatingLabel(product) {
                const rating = this.formatReviewAverage(product);
                const count = this.reviewCount(product);

                return this.locale === 'en'
                    ? `${rating} out of 5 based on ${count} reviews`
                    : `${rating} od 5 na temelju ${count} recenzija`;
            },

            setZeroState() {
                this.search_zero_result = this.search_query != '' && !this.products.total;
                this.navigation_zero_result = this.search_query == '' && !this.products.total;
            },

            /**
             *
             * @param id
             */
            add(id) {
                this.$store.dispatch('addToCart', {
                    id: id,
                    quantity: 1
                })
            }
        }
    };
</script>

<style>
</style>
