<template>
    <aside class="col-lg-3">
        <!-- Sidebar-->
        <div class="offcanvas offcanvas-collapse bg-white w-100 rounded-3 shadow-lg py-1 catalog-shop-sidebar" id="shop-sidebar">
            <div class="offcanvas-cap align-items-center shadow-sm">
                <h2 class="h5 mb-0">{{ labels.filter }}</h2>
                <button class="btn-close ms-auto" type="button" data-bs-dismiss="offcanvas" v-on:click="closeWindow" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body py-grid-gutter px-lg-grid-gutter">
                <!-- Categories-->
                <div class="widget widget-categories mb-3 pb-4 "   v-if="categories">

                    <h3 class="widget-title" v-if="!category && !subcategory">{{ labels.categories }}</h3>

                    <h3 class="widget-title" v-if="category && !subcategory">{{ category.title }}<span class="badge bg-secondary float-end">{{ Number(category.count).toLocaleString('hr-HR') }} </span></h3>
<!--                    <p class="fs-xs text-muted" v-if="category && !subcategory">Podkategorije</p>-->

                    <h3 class="widget-title" v-if="category && subcategory">{{ subcategory.title }}<span class="badge bg-secondary float-end">{{ Number(subcategory.count).toLocaleString('hr-HR') }}</span></h3>

                    <div class="accordion mt-n1"
                         id="shop-categories"
                         :class="{ 'is-expanded': expanded }"
                         data-simplebar
                         data-simplebar-auto-hide="false">
                        <h3 class="accordion-header" v-for="category in categories" :key="category.id">
                            <a :href="category.url" class="accordion-button py-2 border-bottom none collapsed" role="link">
                                {{ category.title }}
                                <span class="badge bg-secondary ms-2 position-absolute end-0">
                                   {{ Number(category.count).toLocaleString('hr-HR') }}
                                </span>
                            </a>
                        </h3>
                    </div>


                    <button class="btn btn-sm btn-outline-primary mt-3"
                            @click="goToParentCategory"
                            v-if="category || subcategory">
                        <i class="fa-solid fa-arrow-left fs-xs me-1" aria-hidden="true"></i> {{ labels.back }}
                    </button>

                    <div class=" mt-3" v-if="categories.length > 16">
                        <button class="btn btn-primary btn-sm" @click="expanded = !expanded">
                            {{ expanded ? labels.showLess : labels.showAllCategories }}
                        </button>
                    </div>

                </div>

                <!-- Date range-->
                <div class="widget mb-3 pb-4 ">
                    <h3 class="widget-title">{{ labels.publicationYear }}</h3>
                    <div >
                        <div class="d-flex pb-1">
                            <div class="w-50 pe-2 me-2">
                                <div class="input-group input-group-sm">
                                    <input class="form-control range-slider-value-min" :placeholder="labels.from" type="text" v-model="start">
                                    <span class="input-group-text">{{ labels.yearShort }}</span>
                                </div>
                            </div>
                            <div class="w-50 ps-2">
                                <div class="input-group input-group-sm">
                                    <input class="form-control range-slider-value-max" :placeholder="labels.to" type="text" v-model="end">
                                    <span class="input-group-text">{{ labels.yearShort }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="widget widget-filter mb-3 pb-4 " v-if="show_authors">
                    <h3 class="widget-title">{{ labels.authors }}<span v-if="!authors_loaded" class="spinner-border spinner-border-sm float-end"></span></h3>
                    <div class="input-group input-group-sm mb-2 autocomplete">
                        <input type="search" v-model="searchAuthor" class="form-control rounded-end pe-5" :placeholder="labels.searchAuthor"><i class="fa-solid fa-magnifying-glass position-absolute top-50 end-0 translate-middle-y fs-sm me-3" aria-hidden="true"></i>
                    </div>
                    <ul class="widget-list widget-filter-list list-unstyled pt-1 catalog-filter-options" data-simplebar data-simplebar-auto-hide="false">
                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1" v-for="author in authors">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" :id="author.slug" :value="author.slug" v-model="selectedAuthors">
                                <label class="form-check-label widget-filter-item-text" :for="author.slug">{{ author.title }}</label>
                            </div><span class="fs-xs text-muted"><a :href="origin + author.url">{{ Number(author.products_count).toLocaleString('hr-HR') }}</a></span>
                        </li>
                    </ul>
                </div>

                <div class="widget widget-filter mb-3 pb-4" v-if="show_publishers">
                    <h3 class="widget-title">{{ labels.publishers }}<span v-if="!publishers_loaded" class="spinner-border spinner-border-sm float-end"></span></h3>
                    <div class="input-group input-group-sm mb-2 autocomplete">
                        <input type="search" v-model="searchPublisher" class="form-control rounded-end pe-5" :placeholder="labels.searchPublisher"><i class="fa-solid fa-magnifying-glass position-absolute top-50 end-0 translate-middle-y fs-sm me-3" aria-hidden="true"></i>
                    </div>
                    <ul class="widget-list widget-filter-list list-unstyled pt-1 catalog-filter-options" data-simplebar data-simplebar-auto-hide="false">
                        <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1" v-for="publisher in publishers">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" :id="publisher.slug" :value="publisher.slug" v-model="selectedPublishers">
                                <label class="form-check-label widget-filter-item-text" :for="publisher.slug">{{ publisher.title }}</label>
                            </div><span class="fs-xs text-muted"><a :href="origin + publisher.url">{{ Number(publisher.products_count).toLocaleString('hr-HR') }}</a></span>
                        </li>
                    </ul>
                </div>
                <button type="button" class="btn btn-primary mt-4" v-on:click="cleanQuery"><i class="fa-solid fa-trash-can" aria-hidden="true"></i> {{ labels.clearAll }}</button>
            </div>
        </div>
    </aside>
</template>

<script>
    export default {
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
            initialCategories: {
                type: Array,
                default: () => []
            }
        },
        //
        computed: {
            labels() {
                const t = (window.FrontTranslations && window.FrontTranslations.js && window.FrontTranslations.js.filter)
                    ? window.FrontTranslations.js.filter
                    : {};
                return this.locale === 'en' ? {
                    filter: t.filter || 'Filter',
                    categories: t.categories || 'Categories',
                    back: t.back || 'Back',
                    showLess: t.show_less || 'Show less',
                    showAllCategories: t.show_all_categories || 'Show all categories',
                    publicationYear: t.publication_year || 'Year of publication',
                    from: t.from || 'From',
                    to: t.to || 'To',
                    yearShort: t.year_short || 'y',
                    authors: t.authors || 'Authors',
                    publishers: t.publishers || 'Publishers',
                    searchAuthor: t.search_author || 'Search author',
                    searchPublisher: t.search_publisher || 'Search publishers',
                    clearAll: t.clear_all || 'Clear all'
                } : {
                    filter: t.filter || 'Filter',
                    categories: t.categories || 'Categories',
                    back: t.back || 'Back',
                    showLess: t.show_less || 'Show less',
                    showAllCategories: t.show_all_categories || 'Show all categories',
                    publicationYear: t.publication_year || 'Year of publication',
                    from: t.from || 'From',
                    to: t.to || 'To',
                    yearShort: t.year_short || 'y',
                    authors: t.authors || 'Authors',
                    publishers: t.publishers || 'Publishers',
                    searchAuthor: t.search_author || 'Search author',
                    searchPublisher: t.search_publisher || 'Search publishers',
                    clearAll: t.clear_all || 'Clear all'
                };
            }
        },
        //
        data() {
            return {
                expanded: false,
                categories: this.initialCategories.length ? this.initialCategories : [],
                category: null,
                subcategory: null,
                authors: [],
                publishers: [],
                selectedAuthors: [],
                selectedPublishers: [],
                start: '',
                end: '',
                autor: '',
                nakladnik: '',
                search_query: '',
                searchAuthor: '',
                searchPublisher: '',
                show_authors: false,
                authors_loaded: false,
                show_publishers: false,
                publishers_loaded: false,
                origin: location.origin + '/'
            }
        },
        //
        watch: {
            start(currentValue) {
                this.setQueryParam('start', currentValue);
            },
            end(currentValue) {
                this.setQueryParam('end', currentValue);
            },
            selectedAuthors(value) {
                this.autor = value.join('+');
                this.setQueryParamOther('autor', this.autor);
            },
            selectedPublishers(value) {
                this.nakladnik = value.join('+');
                this.setQueryParamOther('nakladnik', this.nakladnik);
            },
            searchAuthor(value) {
                if (value.length > 2 || value == '') {
                    return this.getAuthors();
                }
            },
            searchPublisher(value) {
                if (value.length > 2 || value == '') {
                    return this.getPublishers();
                }
            },
            $route(params) {
                this.checkQuery(params);
            }
        },

        //
        mounted() {
            this.checkQuery(this.$route);
            this.checkCategory();
            if (!this.categories.length) {
                this.getCategories();
            }

            if (this.author == '') {
                this.show_authors = true;
            }

            if (this.publisher == '') {
                this.show_publishers = true;
            }

            this.preselect();
            this.deferFilterCollectionsLoad();
        },

        methods: {
            deferFilterCollectionsLoad() {
                const loadCollections = () => {
                    if (this.show_authors && !this.authors.length) {
                        this.getAuthors();
                    }

                    if (this.show_publishers && !this.publishers.length) {
                        this.getPublishers();
                    }
                };

                if (typeof window.requestIdleCallback === 'function') {
                    window.requestIdleCallback(loadCollections, { timeout: 1200 });
                    return;
                }

                window.setTimeout(loadCollections, 250);
            },

            /**
            *
            **/
            getCategories() {
                let params = this.setParams();

                axios.post('filter/getCategories', { params }).then(response => {
                    this.categories = response.data;
                });
            },

            _joinUrl(base, path='') {
                if (!base) return path || '/';
                if (/^https?:\/\//i.test(path)) return path;         // već apsolutni
                const b = base.replace(/\/+$/,'');                   // skini završne /
                const p = (path||'').toString().replace(/^\/+/, ''); // skini početne /
                return p ? `${b}/${p}` : `${b}/`;
            },

            _slugify(s) {
                return (s||'')
                    .toString().trim().toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
            },

            goToParentCategory() {
                const origin = (this.origin || (location.origin + '/'));

                if (this.subcategory && this.category) {
                    // prioritet: subcategory.parent_url → category.url → /{group}/{category.slug}
                    const parentUrl =
                        this.subcategory.parent_url
                        || this.category.url
                        || `/${this._slugify(this.group)}/${this.category.slug}`;

                    window.location.href = this._joinUrl(origin, parentUrl);
                    return;
                }

                if (this.category) {
                    // root grupe: backend-proslijeđen group_url → /{group}
                    const groupUrl = this.group_url || `/${this._slugify(this.group)}`;
                    window.location.href = this._joinUrl(origin, groupUrl);
                }
            },

            /**
             *
             **/
            checkCategory() {
                if (this.cat != '') {
                    this.category = JSON.parse(this.cat);
                }
                if (this.subcat != '') {
                    this.subcategory = JSON.parse(this.subcat);
                }
            },

            /**
             *
             **/
            getAuthors() {
                this.authors_loaded = false;
                let params = this.setParams();

                axios.post('filter/getAuthors', { params }).then(response => {
                    this.authors_loaded = true;
                    this.authors = response.data;
                });
            },

            /**
             *
             **/
            getPublishers() {
                this.publishers_loaded = false;
                let params = this.setParams();

                axios.post('filter/getPublishers', { params }).then(response => {
                    this.publishers_loaded = true;
                    this.publishers = response.data;
                });
            },

            /**
             *
             **/
            setQueryParam(type, value) {
                if (value.length > 3 && value.length < 5) {
                    this.closeWindow();
                    this.$router.push({query: this.resolveQuery()}).catch(()=>{});
                }

                if (value == '') {
                    this.closeWindow();
                    this.$router.push({query: this.resolveQuery()}).catch(()=>{});
                }
            },

            /**
             *
             **/
            setQueryParamOther(type, value) {
                this.closeWindow();
                this.$router.push({query: this.resolveQuery()}).catch(()=>{});

                if (value == '') {
                    this.$router.push({query: this.resolveQuery()}).catch(()=>{});
                }
            },

            /**
             *
             **/
            resolveQuery() {
                let params = {
                    start: this.start,
                    end: this.end,
                    autor: this.autor,
                    nakladnik: this.nakladnik,
                    page: this.page,
                    pojam: this.search_query,
                };

                this.checkNoFollowQuery(params);

                return Object.entries(params).reduce((acc, [key, val]) => {
                    if (!val) return acc
                    return { ...acc, [key]: val }
                }, {});
            },

            /**
             *
             */
            checkNoFollowQuery(param) {
                if (param.nakladnik || param.autor || param.start || param.end) {
                    if (!document.querySelectorAll('meta[name="robots"]').length > 0) {
                        $('head').append('<meta name=robots content=noindex,nofollow>');
                    }
                } else {
                    if (document.querySelectorAll('meta[name="robots"]').length > 0) {
                        document.querySelector("[name='robots']").remove()
                    }
                }
            },

            /**
             *
             **/
            checkQuery(params) {
                this.start = params.query.start ? params.query.start : '';
                this.end = params.query.end ? params.query.end : '';
                this.autor = params.query.autor ? params.query.autor : '';
                this.nakladnik = params.query.nakladnik ? params.query.nakladnik : '';
                this.search_query = params.query.pojam ? params.query.pojam : '';
            },

            /**
             *
             */
            setParams() {
                let params = {
                    ids: this.ids,
                    group: this.group,
                    cat: this.category ? this.category.id : this.cat,
                    subcat: this.subcategory ? this.subcategory.id : this.subcat,
                    author: this.author,
                    publisher: this.publisher,
                    search_author: this.searchAuthor,
                    search_publisher: this.searchPublisher,
                    pojam: this.search_query,
                    locale: this.locale
                };

                if (this.author != '') {
                    params.author = this.author;
                }
                if (this.publisher != '') {
                    params.publisher = this.publisher;
                }

                return params;
            },

            /**
             *
             */
            preselect() {
                if (this.autor != '') {
                    if ((this.autor).includes('+')) {
                        this.selectedAuthors = (this.autor).split('+');
                    } else {
                        this.selectedAuthors = [this.autor];
                    }
                }
                if (this.nakladnik != '') {
                    if ((this.nakladnik).includes('+')) {
                        this.selectedPublishers = (this.nakladnik).split('+');
                    } else {
                        this.selectedPublishers = [this.nakladnik];
                    }
                }
            },

            /**
             *
             */
            cleanQuery() {
                this.$router.push({query: {}}).catch(()=>{});
                this.selectedAuthors = [];
                this.selectedPublishers = [];
                this.start = '';
                this.end = '';
            },

            /**
             *
             */
            closeWindow() {
                $('#shop-sidebar').removeClass('collapse show');
            }
        }
    };
</script>


<style>

</style>
