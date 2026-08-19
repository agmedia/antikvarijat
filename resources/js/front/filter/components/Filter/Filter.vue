<template>
    <aside class="col-lg-3 catalog-filter-column" aria-labelledby="catalog-filter-title">
        <!-- Sidebar-->
        <div class="offcanvas offcanvas-collapse bg-white w-100 catalog-shop-sidebar catalog-filter-panel" id="shop-sidebar">
            <div class="offcanvas-cap catalog-filter-header align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <h2 class="mb-0" id="catalog-filter-title"><i class="fa-solid fa-sliders" aria-hidden="true"></i><span>{{ labels.filter }}</span></h2>
                    <span class="catalog-filter-active-count" v-if="activeFilterCount">{{ activeFilterCount }}</span>
                </div>
                <button class="catalog-filter-close ms-auto" type="button" v-on:click="closeWindow" :aria-label="labels.close">
                    <i class="fa-regular fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <div class="offcanvas-body catalog-filter-body py-grid-gutter px-lg-grid-gutter">
                <div class="catalog-filter-desktop-summary d-none d-lg-flex" v-if="hasActiveFilters">
                    <span class="catalog-filter-desktop-summary__label">{{ labels.filters }} <span class="catalog-filter-active-count">{{ activeFilterCount }}</span></span>
                    <button type="button" class="catalog-filter-desktop-summary__clear" v-on:click="cleanQuery"><i class="fa-solid fa-trash-can" aria-hidden="true"></i> {{ labels.clearAll }}</button>
                </div>
                <!-- Categories-->
                <div class="widget widget-categories catalog-filter-section" v-if="categories">
                    <button class="catalog-filter-section-toggle" type="button" v-on:click="toggleFilterSection('categories')" :class="{ 'is-open': sectionIsOpen('categories') }" :aria-expanded="sectionIsOpen('categories') ? 'true' : 'false'" aria-controls="catalog-filter-categories">
                        <span class="catalog-filter-section-toggle__label"><i class="fa-regular fa-books" aria-hidden="true"></i><span>{{ selectedCategoryTitle }}</span></span>
                        <span class="catalog-filter-section-toggle__meta">
                            <span class="catalog-filter-result-count" v-if="selectedCategoryCount !== null">{{ Number(selectedCategoryCount).toLocaleString(numberLocale) }}</span>
                            <i class="fa-regular fa-chevron-down catalog-filter-section-toggle__chevron" aria-hidden="true"></i>
                        </span>
                    </button>
                    <div class="catalog-filter-section-content" id="catalog-filter-categories" v-show="sectionIsOpen('categories')">
                        <div class="accordion"
                             id="shop-categories"
                             :class="{ 'is-expanded': expanded, 'is-scrolling': categoryListIsScrolling }"
                             @wheel.passive="showCategoryScrollbar"
                             @touchmove.passive="showCategoryScrollbar"
                             @keydown="showCategoryScrollbarOnKeydown"
                             data-simplebar
                             data-simplebar-auto-hide="false">
                            <h3 class="accordion-header" v-for="category in categories" :key="category.id">
                                <a :href="category.url" class="accordion-button py-2 border-bottom none collapsed" role="link">
                                    {{ category.title }}
                                    <span class="badge bg-secondary ms-2 position-absolute end-0">{{ Number(category.count).toLocaleString(numberLocale) }}</span>
                                </a>
                            </h3>
                        </div>
                        <button class="catalog-filter-back mt-2" type="button" @click="goToParentCategory" v-if="category || subcategory">
                            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> {{ labels.back }}
                        </button>
                        <div class="mt-2" v-if="categories.length > 16">
                            <button class="catalog-filter-more" type="button" @click="expanded = !expanded" :aria-expanded="expanded ? 'true' : 'false'">
                                <span>{{ expanded ? labels.showLess : labels.showAllCategories }}</span>
                                <i class="fa-regular fa-chevron-down" :class="{ 'is-open': expanded }" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Date range-->
                <div class="widget catalog-filter-section">
                    <button class="catalog-filter-section-toggle" type="button" v-on:click="toggleFilterSection('year')" :class="{ 'is-open': sectionIsOpen('year') }" :aria-expanded="sectionIsOpen('year') ? 'true' : 'false'" aria-controls="catalog-filter-year">
                        <span class="catalog-filter-section-toggle__label"><i class="fa-regular fa-calendar-range" aria-hidden="true"></i><span>{{ labels.publicationYear }}</span></span>
                        <span class="catalog-filter-section-toggle__meta"><span class="catalog-filter-selected-count" v-if="yearFilterCount">{{ yearFilterCount }}</span><i class="fa-regular fa-chevron-down catalog-filter-section-toggle__chevron" aria-hidden="true"></i></span>
                    </button>
                    <div class="catalog-filter-section-content catalog-filter-year-grid" id="catalog-filter-year" v-show="sectionIsOpen('year')">
                            <div>
                                <div class="input-group">
                                    <input class="form-control range-slider-value-min" :placeholder="labels.from" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="4" v-model="start" :aria-label="labels.fromYear">
                                    <span class="input-group-text">{{ labels.yearShort }}</span>
                                </div>
                            </div>
                            <div>
                                <div class="input-group">
                                    <input class="form-control range-slider-value-max" :placeholder="labels.to" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="4" v-model="end" :aria-label="labels.toYear">
                                    <span class="input-group-text">{{ labels.yearShort }}</span>
                                </div>
                            </div>
                    </div>
                </div>

                <div class="widget catalog-filter-section" v-if="hasAttributeOptions">
                    <button class="catalog-filter-section-toggle" type="button" v-on:click="toggleFilterSection('details')" :class="{ 'is-open': sectionIsOpen('details') }" :aria-expanded="sectionIsOpen('details') ? 'true' : 'false'" aria-controls="catalog-filter-details">
                        <span class="catalog-filter-section-toggle__label"><i class="fa-regular fa-book-open-cover" aria-hidden="true"></i><span>{{ labels.details }}</span></span>
                        <span class="catalog-filter-section-toggle__meta"><span class="catalog-filter-selected-count" v-if="attributeFilterCount">{{ attributeFilterCount }}</span><i class="fa-regular fa-chevron-down catalog-filter-section-toggle__chevron" aria-hidden="true"></i></span>
                    </button>
                    <div class="catalog-filter-section-content catalog-attribute-filters" id="catalog-filter-details" v-show="sectionIsOpen('details')">
                        <fieldset class="catalog-attribute-group" v-for="group in attributeGroups" :key="group.key">
                            <legend class="catalog-attribute-group__title">{{ group.label }}</legend>
                            <div class="catalog-attribute-choices" role="radiogroup" :aria-label="group.label">
                                <button class="catalog-attribute-choice"
                                        type="button"
                                        v-for="option in group.options"
                                        :key="group.key + '-' + (option.value || 'all')"
                                        v-on:click="selectAttribute(group.model, option.value)"
                                        :class="{ 'is-active': group.selected === option.value }"
                                        :aria-checked="group.selected === option.value ? 'true' : 'false'"
                                        role="radio">
                                    <span>{{ option.label }}</span>
                                    <span class="catalog-attribute-choice__count" v-if="option.count !== null">{{ Number(option.count).toLocaleString(numberLocale) }}</span>
                                </button>
                            </div>
                        </fieldset>
                    </div>
                </div>

                <div class="widget widget-filter catalog-filter-section" v-if="show_authors">
                    <button class="catalog-filter-section-toggle" type="button" v-on:click="toggleFilterSection('authors')" :class="{ 'is-open': sectionIsOpen('authors') }" :aria-expanded="sectionIsOpen('authors') ? 'true' : 'false'" aria-controls="catalog-filter-authors">
                        <span class="catalog-filter-section-toggle__label"><i class="fa-regular fa-user-pen" aria-hidden="true"></i><span>{{ labels.authors }}</span></span>
                        <span class="catalog-filter-section-toggle__meta"><span class="catalog-filter-selected-count" v-if="selectedAuthors.length">{{ selectedAuthors.length }}</span><span v-if="!authors_loaded" class="spinner-border spinner-border-sm"></span><i class="fa-regular fa-chevron-down catalog-filter-section-toggle__chevron" aria-hidden="true"></i></span>
                    </button>
                    <div class="catalog-filter-section-content" id="catalog-filter-authors" v-show="sectionIsOpen('authors')">
                        <div class="input-group mb-2 autocomplete catalog-filter-search">
                            <input type="search" v-model="searchAuthor" class="form-control rounded-end pe-5" :placeholder="labels.searchAuthor" :aria-label="labels.searchAuthor"><i class="fa-solid fa-magnifying-glass position-absolute top-50 end-0 translate-middle-y fs-sm me-3" aria-hidden="true"></i>
                        </div>
                        <ul class="widget-list widget-filter-list list-unstyled catalog-filter-options">
                            <li class="widget-filter-item d-flex justify-content-between align-items-center" v-for="author in authors" :key="author.slug">
                                <label class="catalog-filter-checkbox-row" :for="'filter-author-' + author.slug">
                                    <input class="form-check-input" type="checkbox" :id="'filter-author-' + author.slug" :value="author.slug" v-model="selectedAuthors">
                                    <span class="catalog-filter-checkbox-label">{{ author.title }}</span>
                                    <span class="catalog-filter-option-count">{{ Number(author.products_count).toLocaleString(numberLocale) }}</span>
                                </label>
                            </li>
                        </ul>
                        <p class="catalog-filter-empty mb-0" v-if="authors_loaded && searchAuthor.length > 2 && !authors.length">{{ labels.noMatches }}</p>
                    </div>
                </div>

                <div class="widget widget-filter catalog-filter-section" v-if="show_publishers">
                    <button class="catalog-filter-section-toggle" type="button" v-on:click="toggleFilterSection('publishers')" :class="{ 'is-open': sectionIsOpen('publishers') }" :aria-expanded="sectionIsOpen('publishers') ? 'true' : 'false'" aria-controls="catalog-filter-publishers">
                        <span class="catalog-filter-section-toggle__label"><i class="fa-regular fa-building" aria-hidden="true"></i><span>{{ labels.publishers }}</span></span>
                        <span class="catalog-filter-section-toggle__meta"><span class="catalog-filter-selected-count" v-if="selectedPublishers.length">{{ selectedPublishers.length }}</span><span v-if="!publishers_loaded" class="spinner-border spinner-border-sm"></span><i class="fa-regular fa-chevron-down catalog-filter-section-toggle__chevron" aria-hidden="true"></i></span>
                    </button>
                    <div class="catalog-filter-section-content" id="catalog-filter-publishers" v-show="sectionIsOpen('publishers')">
                        <div class="input-group mb-2 autocomplete catalog-filter-search">
                            <input type="search" v-model="searchPublisher" class="form-control rounded-end pe-5" :placeholder="labels.searchPublisher" :aria-label="labels.searchPublisher"><i class="fa-solid fa-magnifying-glass position-absolute top-50 end-0 translate-middle-y fs-sm me-3" aria-hidden="true"></i>
                        </div>
                        <ul class="widget-list widget-filter-list list-unstyled catalog-filter-options">
                            <li class="widget-filter-item d-flex justify-content-between align-items-center" v-for="publisher in publishers" :key="publisher.slug">
                                <label class="catalog-filter-checkbox-row" :for="'filter-publisher-' + publisher.slug">
                                    <input class="form-check-input" type="checkbox" :id="'filter-publisher-' + publisher.slug" :value="publisher.slug" v-model="selectedPublishers">
                                    <span class="catalog-filter-checkbox-label">{{ publisher.title }}</span>
                                    <span class="catalog-filter-option-count">{{ Number(publisher.products_count).toLocaleString(numberLocale) }}</span>
                                </label>
                            </li>
                        </ul>
                        <p class="catalog-filter-empty mb-0" v-if="publishers_loaded && searchPublisher.length > 2 && !publishers.length">{{ labels.noMatches }}</p>
                    </div>
                </div>
                <button type="button" class="catalog-filter-clear-desktop d-none d-lg-inline-flex" v-if="hasActiveFilters" v-on:click="cleanQuery"><i class="fa-solid fa-trash-can" aria-hidden="true"></i> {{ labels.clearAll }}</button>
            </div>
            <div class="catalog-filter-actions d-lg-none">
                <button type="button" class="catalog-filter-clear" v-on:click="cleanQuery" :disabled="!hasActiveFilters"><i class="fa-solid fa-trash-can" aria-hidden="true"></i> {{ labels.clear }}</button>
                <button type="button" class="btn btn-primary catalog-filter-apply" v-on:click="applyFilters">
                    {{ labels.showResults }}
                </button>
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
            parentUrl: {
                type: String,
                default: ''
            },
            locale: {
                type: String,
                default: 'hr'
            },
            initialCategories: {
                type: Array,
                default: () => []
            },
            initialAttributes: {
                type: Object,
                default: () => ({})
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
                    filters: t.filters || 'Filters',
                    categories: t.categories || 'Categories',
                    back: t.back || 'Back',
                    showLess: t.show_less || 'Show less',
                    showAllCategories: t.show_all_categories || 'Show all categories',
                    publicationYear: t.publication_year || 'Year of publication',
                    details: t.details || 'Book details',
                    letter: t.letter || 'Script',
                    allLetters: t.all_letters || 'All scripts',
                    condition: t.condition || 'Condition',
                    allConditions: t.all_conditions || 'All conditions',
                    binding: t.binding || 'Binding',
                    allBindings: t.all_bindings || 'All bindings',
                    attributeValues: t.attribute_values || {},
                    from: t.from || 'From',
                    to: t.to || 'To',
                    yearShort: t.year_short || 'y',
                    authors: t.authors || 'Authors',
                    publishers: t.publishers || 'Publishers',
                    searchAuthor: t.search_author || 'Search author',
                    searchPublisher: t.search_publisher || 'Search publishers',
                    clearAll: t.clear_all || 'Clear all',
                    clear: t.clear || 'Clear',
                    showResults: t.show_results || 'Show results',
                    close: t.close || 'Close filters',
                    fromYear: t.from_year || 'From year',
                    toYear: t.to_year || 'To year',
                    noMatches: t.no_matches || 'No matches'
                } : {
                    filter: t.filter || 'Filter',
                    filters: t.filters || 'Filtri',
                    categories: t.categories || 'Categories',
                    back: t.back || 'Back',
                    showLess: t.show_less || 'Show less',
                    showAllCategories: t.show_all_categories || 'Show all categories',
                    publicationYear: t.publication_year || 'Year of publication',
                    details: t.details || 'Book details',
                    letter: t.letter || 'Script',
                    allLetters: t.all_letters || 'All scripts',
                    condition: t.condition || 'Condition',
                    allConditions: t.all_conditions || 'All conditions',
                    binding: t.binding || 'Binding',
                    allBindings: t.all_bindings || 'All bindings',
                    attributeValues: t.attribute_values || {},
                    from: t.from || 'From',
                    to: t.to || 'To',
                    yearShort: t.year_short || 'y',
                    authors: t.authors || 'Authors',
                    publishers: t.publishers || 'Publishers',
                    searchAuthor: t.search_author || 'Search author',
                    searchPublisher: t.search_publisher || 'Search publishers',
                    clearAll: t.clear_all || 'Clear all',
                    clear: t.clear || 'Clear',
                    showResults: t.show_results || 'Show results',
                    close: t.close || 'Close filters',
                    fromYear: t.from_year || 'From year',
                    toYear: t.to_year || 'To year',
                    noMatches: t.no_matches || 'No matches'
                };
            },

            numberLocale() {
                return this.locale === 'en' ? 'en-US' : 'hr-HR';
            },

            selectedCategoryTitle() {
                if (this.subcategory) return this.subcategory.title;
                if (this.category) return this.category.title;

                return this.labels.categories;
            },

            selectedCategoryCount() {
                if (this.subcategory) return this.subcategory.count;
                if (this.category) return this.category.count;

                return null;
            },

            yearFilterCount() {
                return (this.start ? 1 : 0) + (this.end ? 1 : 0);
            },

            attributeFilterCount() {
                return (this.pismo ? 1 : 0) + (this.stanje ? 1 : 0) + (this.uvez ? 1 : 0);
            },

            activeFilterCount() {
                return this.selectedAuthors.length
                    + this.selectedPublishers.length
                    + (this.start ? 1 : 0)
                    + (this.end ? 1 : 0)
                    + (this.pismo ? 1 : 0)
                    + (this.stanje ? 1 : 0)
                    + (this.uvez ? 1 : 0);
            },

            hasActiveFilters() {
                return this.activeFilterCount > 0;
            },

            hasAttributeOptions() {
                return ['letter', 'condition', 'binding'].some(key => this.attributes[key].length);
            },

            attributeGroups() {
                const buildGroup = (key, model, label, allLabel, selected) => ({
                    key,
                    model,
                    label,
                    selected,
                    options: [
                        { value: '', label: allLabel, count: null },
                        ...this.attributes[key].map(option => ({
                            value: option.value,
                            label: this.attributeLabel(key, option.value),
                            count: option.count
                        }))
                    ]
                });

                return [
                    buildGroup('letter', 'pismo', this.labels.letter, this.labels.allLetters, this.pismo),
                    buildGroup('condition', 'stanje', this.labels.condition, this.labels.allConditions, this.stanje),
                    buildGroup('binding', 'uvez', this.labels.binding, this.labels.allBindings, this.uvez)
                ].filter(group => this.attributes[group.key].length);
            }
        },
        //
        data() {
            return {
                expanded: false,
                openSections: {
                    categories: true,
                    year: false,
                    details: false,
                    authors: false,
                    publishers: false
                },
                categories: this.initialCategories.length ? this.initialCategories : [],
                attributes: {
                    letter: this.initialAttributes.letter || [],
                    condition: this.initialAttributes.condition || [],
                    binding: this.initialAttributes.binding || []
                },
                category: null,
                subcategory: null,
                authors: [],
                publishers: [],
                selectedAuthors: [],
                selectedPublishers: [],
                start: '',
                end: '',
                pismo: '',
                stanje: '',
                uvez: '',
                autor: '',
                nakladnik: '',
                search_query: '',
                searchAuthor: '',
                searchPublisher: '',
                show_authors: false,
                authors_loaded: false,
                show_publishers: false,
                publishers_loaded: false,
                categoryListIsScrolling: false,
                categoryScrollTimer: null,
                authorSearchTimer: null,
                publisherSearchTimer: null,
                authorRequestId: 0,
                publisherRequestId: 0,
                syncingQuery: false,
                originalRobotsContent: null,
                origin: location.origin + '/'
            }
        },
        //
        watch: {
            start(currentValue) {
                if (this.syncingQuery) return;
                this.setQueryParam('start', currentValue);
            },
            end(currentValue) {
                if (this.syncingQuery) return;
                this.setQueryParam('end', currentValue);
            },
            selectedAuthors(value) {
                if (this.syncingQuery) return;
                this.autor = value.join('+');
                this.setQueryParamOther('autor', this.autor);
            },
            selectedPublishers(value) {
                if (this.syncingQuery) return;
                this.nakladnik = value.join('+');
                this.setQueryParamOther('nakladnik', this.nakladnik);
            },
            searchAuthor(value) {
                this.scheduleCollectionSearch('author', value);
            },
            searchPublisher(value) {
                this.scheduleCollectionSearch('publisher', value);
            },
            $route(params) {
                this.checkQuery(params);
            }
        },

        //
        mounted() {
            document.addEventListener('keydown', this.handleFilterKeydown);
            const robotsMeta = document.querySelector('meta[name="robots"]');
            this.originalRobotsContent = robotsMeta ? robotsMeta.getAttribute('content') : null;
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

            this.openSectionsWithSelections();
            this.deferFilterCollectionsLoad();
        },

        beforeDestroy() {
            window.clearTimeout(this.categoryScrollTimer);
            window.clearTimeout(this.authorSearchTimer);
            window.clearTimeout(this.publisherSearchTimer);
            document.removeEventListener('keydown', this.handleFilterKeydown);
            document.body.classList.remove('catalog-filter-open');
        },

        methods: {
            sectionIsOpen(section) {
                return Boolean(this.openSections[section]);
            },

            toggleFilterSection(section) {
                const willOpen = !this.sectionIsOpen(section);

                this.$set(this.openSections, section, willOpen);

                if (!willOpen) return;

                if (section === 'authors' && !this.authors.length) this.getAuthors();
                if (section === 'publishers' && !this.publishers.length) this.getPublishers();
            },

            openSectionsWithSelections() {
                if (this.yearFilterCount) this.$set(this.openSections, 'year', true);
                if (this.attributeFilterCount) this.$set(this.openSections, 'details', true);
                if (this.selectedAuthors.length) this.$set(this.openSections, 'authors', true);
                if (this.selectedPublishers.length) this.$set(this.openSections, 'publishers', true);
            },

            handleFilterKeydown(event) {
                const sidebar = document.getElementById('shop-sidebar');

                if (event.key === 'Escape' && sidebar && sidebar.classList.contains('show')) {
                    this.closeWindow();
                }
            },

            showCategoryScrollbarOnKeydown(event) {
                if (['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End'].includes(event.key)) {
                    this.showCategoryScrollbar();
                }
            },

            showCategoryScrollbar() {
                this.categoryListIsScrolling = true;
                window.clearTimeout(this.categoryScrollTimer);
                this.categoryScrollTimer = window.setTimeout(() => {
                    this.categoryListIsScrolling = false;
                }, 900);
            },

            attributeLabel(group, value) {
                const values = this.labels.attributeValues[group] || {};

                return values[value] || value;
            },

            updateQueryParams(changes) {
                const query = { ...(this.$route.query || {}) };

                Object.entries(changes).forEach(([key, value]) => {
                    if (value === '' || value === null || typeof value === 'undefined') {
                        delete query[key];
                    } else {
                        query[key] = value;
                    }
                });

                delete query.page;
                this.checkNoFollowQuery(query);
                this.$router.push({ query }).catch(() => {});
            },

            selectAttribute(model, value) {
                this[model] = value;
                this.updateQueryParams({ [model]: value });
            },

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

            scheduleCollectionSearch(type, value) {
                const timerKey = type === 'author' ? 'authorSearchTimer' : 'publisherSearchTimer';
                const loader = type === 'author' ? this.getAuthors : this.getPublishers;

                window.clearTimeout(this[timerKey]);

                if (value.length > 0 && value.length < 3) return;

                this[timerKey] = window.setTimeout(() => loader.call(this), 250);
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
                if (this.parentUrl) {
                    window.location.href = this.parentUrl;
                    return;
                }

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
                const requestId = ++this.authorRequestId;

                axios.post('filter/getAuthors', { params }).then(response => {
                    if (requestId !== this.authorRequestId) return;
                    this.authors_loaded = true;
                    this.authors = response.data;
                }).catch(() => {
                    if (requestId === this.authorRequestId) this.authors_loaded = true;
                });
            },

            /**
             *
             **/
            getPublishers() {
                this.publishers_loaded = false;
                let params = this.setParams();
                const requestId = ++this.publisherRequestId;

                axios.post('filter/getPublishers', { params }).then(response => {
                    if (requestId !== this.publisherRequestId) return;
                    this.publishers_loaded = true;
                    this.publishers = response.data;
                }).catch(() => {
                    if (requestId === this.publisherRequestId) this.publishers_loaded = true;
                });
            },

            /**
             *
             **/
            setQueryParam(type, value) {
                const normalizedValue = String(value || '').replace(/\D/g, '').slice(0, 4);

                if (normalizedValue !== value) {
                    this[type] = normalizedValue;
                    return;
                }

                if (normalizedValue.length === 4 || normalizedValue === '') {
                    this.updateQueryParams({ [type]: normalizedValue });
                }
            },

            /**
             *
             **/
            setQueryParamOther(type, value) {
                this.updateQueryParams({ [type]: value });
            },

            /**
             *
             **/
            resolveQuery() {
                let params = {
                    start: this.start,
                    end: this.end,
                    pismo: this.pismo,
                    stanje: this.stanje,
                    uvez: this.uvez,
                    autor: this.autor,
                    nakladnik: this.nakladnik,
                    sort: this.$route.query.sort || '',
                    page: '',
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
                const hasFilters = Boolean(param.nakladnik || param.autor || param.start || param.end || param.pismo || param.stanje || param.uvez);
                let robotsMeta = document.querySelector('meta[name="robots"]');

                if (hasFilters) {
                    if (!robotsMeta) {
                        robotsMeta = document.createElement('meta');
                        robotsMeta.setAttribute('name', 'robots');
                        robotsMeta.dataset.catalogFilter = 'true';
                        document.head.appendChild(robotsMeta);
                    }

                    robotsMeta.setAttribute('content', 'noindex,follow');
                    return;
                }

                if (robotsMeta && this.originalRobotsContent !== null) {
                    robotsMeta.setAttribute('content', this.originalRobotsContent);
                } else if (robotsMeta && robotsMeta.dataset.catalogFilter === 'true') {
                    robotsMeta.remove();
                }
            },

            /**
             *
             **/
            checkQuery(params) {
                this.syncingQuery = true;
                this.start = params.query.start ? params.query.start : '';
                this.end = params.query.end ? params.query.end : '';
                this.pismo = params.query.pismo ? params.query.pismo : '';
                this.stanje = params.query.stanje ? params.query.stanje : '';
                this.uvez = params.query.uvez ? params.query.uvez : '';
                this.autor = params.query.autor ? params.query.autor : '';
                this.nakladnik = params.query.nakladnik ? params.query.nakladnik : '';
                this.search_query = params.query.pojam ? params.query.pojam : '';
                this.selectedAuthors = this.autor ? this.autor.split('+').filter(Boolean) : [];
                this.selectedPublishers = this.nakladnik ? this.nakladnik.split('+').filter(Boolean) : [];
                this.checkNoFollowQuery(params.query || {});
                this.$nextTick(() => {
                    this.syncingQuery = false;
                });
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
                    pismo: this.pismo,
                    stanje: this.stanje,
                    uvez: this.uvez,
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
                const preservedQuery = {
                    sort: this.$route.query.sort || '',
                    pojam: this.search_query
                };

                this.searchAuthor = '';
                this.searchPublisher = '';
                this.$router.push({
                    query: Object.entries(preservedQuery).reduce((query, [key, value]) => {
                        if (value) query[key] = value;
                        return query;
                    }, {})
                }).catch(()=>{});
            },

            applyFilters() {
                this.closeWindow();
            },

            /**
             *
             */
            closeWindow() {
                const sidebar = document.getElementById('shop-sidebar');
                const trigger = document.querySelector('.catalog-filter-trigger[href="#shop-sidebar"]');

                if (!sidebar) return;

                sidebar.classList.remove('show', 'collapse', 'collapsing');
                sidebar.style.height = '';
                sidebar.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('catalog-filter-open');

                if (trigger) {
                    trigger.classList.add('collapsed');
                    trigger.setAttribute('aria-expanded', 'false');

                    this.$nextTick(() => trigger.focus());
                }
            }
        }
    };
</script>


<style>

</style>
