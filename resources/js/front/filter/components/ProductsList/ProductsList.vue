<template>
    <section class="col-lg-9">
        <!-- Toolbar-->
        <div class="d-flex justify-content-center justify-content-sm-between align-items-center pt-2 pb-4 pb-sm-5">
            <div class="d-flex flex-wrap">
                <div class="dropdown me-2 d-sm-none"><a class="btn btn-primary dropdown-toggle collapsed" href="#shop-sidebar" data-bs-toggle="collapse" aria-expanded="false"><i class="ci-filter-alt"></i></a></div>
                <div class="d-flex align-items-center flex-nowrap me-3 me-sm-4 pb-3">
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
            <div class="d-flex pb-3"><span class="fs-sm text-light btn btn-primary btn-sm text-nowrap ms-2 d-none d-sm-block">{{ labels.total }} {{ formatNumber(products.total || 0) }} {{ labels.items }}</span></div>
        </div>

        <!-- Products grid-->

        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-4  mb-3 px-2" v-if="products.total">



            <div class="col px-2 mb-4" v-for="(product, index) in products.data" :key="product.id">
                <div class="card product-card shadow mb-2">
                    <span class="badge  bg-dark mt-1 ms-1 badge-shadow" v-if="product.special">-{{ ($store.state.service.getDiscountAmount(product.price, product.special)) }}%</span>
                    <div class="product-thumb">
                        <a :href="origin + product.url">
                        <img
                            :loading="index < 8 ? 'eager' : 'lazy'"
                            :fetchpriority="index < 2 ? 'high' : 'auto'"
                            decoding="async"
                            sizes="(max-width: 575px) 50vw, (max-width: 991px) 33vw, (max-width: 1399px) 25vw, 250px"
                            :src="product.image.replace('.webp', '-thumb.webp')"
                            width="250"
                            height="300"
                            :alt="product.name">
                        </a>
                    </div>
                    <div class="card-body pt-2">
                        <div class="d-flex flex-wrap justify-content-between align-items-start pb-2">
                            <div class="text-muted fs-xs me-1">
                                <a class="product-meta fw-medium" :href="productMeta(product) ? (origin + productMeta(product).url) : '#'">{{ productMeta(product) ? productMeta(product).title : labels.unknown }}</a>
                            </div>

                        </div>
                        <h3 class="product-title fs-sm mb-0"><a :href="origin + product.url">{{ product.name }}</a></h3>
                        <div class="d-flex flex-wrap justify-content-between align-items-center" v-if="product.category_string">
                            <div class="fs-sm me-2 one-line"><i class="ci-book text-muted" style="font-size: 11px;"></i> <span v-html="product.category_string"></span></div>
                        </div>

                        <div class="d-flex flex-wrap justify-content-between align-items-center price-box mt-2">
                            <div class="bg-faded-accent text-accent fs-sm rounded-1 py-1 px-2" v-if="product.special" style="text-decoration: line-through;">{{ product.main_price_text }}</div>
                            <div class="bg-faded-accent text-accent fs-sm rounded-1 py-1 px-2" v-if="product.special">{{ product.main_special_text }}</div>
                            <div class="bg-faded-accent text-accent fs-sm rounded-1 py-1 px-2" v-if="!product.special">{{ product.main_price_text }}</div>
                        </div>

                        <div class="d-flex flex-wrap justify-content-between align-items-center price-box mt-2" v-if="product.secondary_price">
                            <div class="bg-faded-accent text-accent fs-sm rounded-1 py-1 px-2" v-if="product.special" style="text-decoration: line-through;">{{ product.secondary_price_text }}</div>
                            <div class="bg-faded-accent text-accent fs-sm rounded-1 py-1 px-2" v-if="product.special">{{ product.secondary_special_text }}</div>
                            <div class="bg-faded-accent text-accent fs-sm rounded-1 py-1 px-2" v-if="!product.special">{{ product.secondary_price_text }}</div>
                        </div>
                    </div>
                    <div class="product-floating-btn">
                       <button type="button" class="btn btn-primary  btn-sm" v-on:click="add(product.id)">+<i class="ci-cart"></i></button>
                    </div>
                </div>

            </div>
        </div>

        <pagination :data="products" align="center" :show-disabled="true" :limit="5" @pagination-change-page="getProductsPage"></pagination>

        <div class="row" v-if="!products_loaded">
            <div class="col-md-12 d-flex justify-content-center mt-4">
                <div class="spinner-border text-muted opacity-75" role="status" style="width: 9rem; height: 9rem;"></div>
            </div>
            <div class="col-md-12 d-flex justify-content-center mt-4">
                <p class="fs-3 fw-lighter opacity-50">{{ labels.loading }}</p>
            </div>
        </div>

        <div class="col-md-12 d-flex justify-content-center mt-4" v-if="products.total">
            <p class="fs-sm">{{ labels.shown }}
                <span class="font-weight-bolder mx-1">{{ formatNumber(products.from || 0) }}</span> {{ labels.to }}
                <span class="font-weight-bolder mx-1">{{ formatNumber(products.to || 0) }}</span> {{ labels.of }}
                <span class="font-weight-bold mx-1">{{ formatNumber(products.total || 0) }}</span> {{ hr_total }}
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
                start: '',
                end: '',
                sorting: '',
                search_query: '',
                page: 1,
                origin: location.origin + '/',
                hr_total: this.locale === 'en' ? 'results' : 'rezultata',
                products_loaded: !!bootstrappedProducts,
                search_zero_result: false,
                navigation_zero_result: false,
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
            this.syncQuery(this.$route);

            if (this.products_loaded) {
                this.checkHrTotal();
                this.checkSpecials();
                this.setZeroState();
                return;
            }

            this.loadProductsForCurrentState();
        },

        methods: {
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
                this.closeFilter();
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
                    autor: this.autor,
                    nakladnik: this.nakladnik,
                    sort: this.sorting && this.sorting !== this.defaultSortForState() ? this.sorting : '',
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
                this.autor = params.query.autor ? params.query.autor : '';
                this.nakladnik = params.query.nakladnik ? params.query.nakladnik : '';
                this.page = params.query.page ? Number(params.query.page) : 1;
                this.search_query = params.query.pojam ? params.query.pojam : '';
                this.sorting = params.query.sort ? params.query.sort : this.defaultSortForState();
            },

            loadProductsForCurrentState() {
                this.requestProducts(this.page || 1);
            },

            defaultSortForState() {
                return this.isRootBooksListing() ? 'novi' : '';
            },

            isRootBooksListing() {
                const group = this.normalizeGroup(this.group);
                const hasBooksGroup = group === 'knjige' || group === 'books';
                const hasRouteTarget = this.cat || this.subcat || this.author || this.publisher || this.ids;
                const hasActiveFilters = this.autor || this.nakladnik || this.start || this.end || this.search_query;

                return hasBooksGroup && !hasRouteTarget && !hasActiveFilters;
            },

            normalizeGroup(group) {
                return (group || '')
                    .toString()
                    .trim()
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
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
                    start: this.start,
                    end: this.end,
                    sort: this.sorting || this.defaultSortForState(),
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

                return product.author || null;
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
            },

            /**
             *
             */
            closeFilter() {
                $('#shop-sidebar').removeClass('collapse show');
            }
        }
    };
</script>

<style>
</style>
