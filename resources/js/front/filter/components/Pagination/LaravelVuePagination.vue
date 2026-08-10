<template>
    <renderless-laravel-vue-pagination
        :data="data"
        :limit="limit"
        :show-disabled="showDisabled"
        :size="size"
        :align="align"
        v-on:pagination-change-page="onPaginationChangePage">

        <nav
            class="catalog-pagination"
            :aria-label="labels.navigation"
            v-if="computed.total > computed.perPage"
            slot-scope="{ data, limit, showDisabled, size, align, computed, prevButtonEvents, nextButtonEvents, pageButtonEvents }">
        <ul class="pagination mb-0"
            :class="{
                'pagination-sm': size == 'small',
                'pagination-lg': size == 'large',
                'justify-content-center': align == 'center',
                'justify-content-end': align == 'right'
            }">

            <li class="page-item pagination-prev-nav" :class="{'disabled': !computed.prevPageUrl}" v-if="computed.prevPageUrl || showDisabled">
                <a class="page-link" href="#" :aria-label="labels.previous" :tabindex="!computed.prevPageUrl && -1" v-on="prevButtonEvents">
                    <slot name="prev-nav">
                        <i class="fa-regular fa-arrow-left" aria-hidden="true"></i>
                        <span class="d-none d-sm-inline">{{ labels.previous }}</span>
                    </slot>
                </a>
            </li>

            <li class="page-item pagination-mobile-summary disabled d-sm-none" aria-current="page">
                <span class="page-link">{{ computed.currentPage }} / {{ computed.lastPage }}</span>
            </li>

            <li class="page-item pagination-page-nav d-none d-sm-block" v-for="(page, key) in computed.pageRange" :key="key" :class="{ 'active': page == computed.currentPage }">
                <a class="page-link" href="#" v-on="pageButtonEvents(page)">
                    {{ page == '...' ? page : Number(page).toLocaleString(numberLocale) }}
                    <span class="sr-only" v-if="page == computed.currentPage"></span>
                </a>
            </li>

            <li class="page-item pagination-next-nav" :class="{'disabled': !computed.nextPageUrl}" v-if="computed.nextPageUrl || showDisabled">
                <a class="page-link" href="#" :aria-label="labels.next" :tabindex="!computed.nextPageUrl && -1" v-on="nextButtonEvents">
                    <slot name="next-nav">
                        <span class="d-none d-sm-inline">{{ labels.next }}</span>
                        <i class="fa-regular fa-arrow-right" aria-hidden="true"></i>
                    </slot>
                </a>
            </li>

        </ul>
        </nav>

    </renderless-laravel-vue-pagination>
</template>

<script>
import RenderlessLaravelVuePagination from './RenderlessLaravelVuePagination.vue';

export default {
    props: {
        data: {
            type: Object,
            default: () => {}
        },
        limit: {
            type: Number,
            default: 0
        },
        showDisabled: {
            type: Boolean,
            default: false
        },
        size: {
            type: String,
            default: 'default',
            validator: value => {
                return ['small', 'default', 'large'].indexOf(value) !== -1;
            }
        },
        align: {
            type: String,
            default: 'left',
            validator: value => {
                return ['left', 'center', 'right'].indexOf(value) !== -1;
            }
        }
    },

    computed: {
        labels() {
            const t = (window.FrontTranslations && window.FrontTranslations.js && window.FrontTranslations.js.pagination)
                ? window.FrontTranslations.js.pagination
                : {};

            return {
                navigation: t.navigation || ((document.documentElement.lang || 'hr') === 'en' ? 'Pagination' : 'Navigacija po stranicama'),
                previous: t.previous || ((document.documentElement.lang || 'hr') === 'en' ? 'Previous' : 'Prethodna'),
                next: t.next || ((document.documentElement.lang || 'hr') === 'en' ? 'Next' : 'Sljedeća')
            };
        },

        numberLocale() {
            return (document.documentElement.lang || 'hr') === 'en' ? 'en-US' : 'hr-HR';
        }
    },

    methods: {
        onPaginationChangePage (page) {
            this.$emit('pagination-change-page', page);
        }
    },

    components: {
        RenderlessLaravelVuePagination
    }
}
</script>
