<template>
    <div class="navbar-tool ms-1 cart-drawer-root">
        <a
            ref="trigger"
            class="navbar-tool-icon-box cart-drawer-trigger"
            :href="carturl"
            :aria-label="labels.openCart"
            :aria-haspopup="mobile ? null : 'dialog'"
            :aria-expanded="mobile ? null : (isOpen ? 'true' : 'false')"
            :aria-controls="mobile ? null : 'header-cart-drawer'"
            @click="handleTriggerClick"
        >
            <span v-if="count > 0" class="navbar-tool-label">{{ count }}</span>
            <i class="navbar-tool-icon fa-regular fa-bag-shopping front-header-cart-icon" aria-hidden="true"></i>
        </a>

        <transition name="cart-drawer-fade">
            <button
                v-if="isOpen"
                class="cart-drawer-backdrop"
                type="button"
                tabindex="-1"
                :aria-label="labels.close"
                @click="closeDrawer"
            ></button>
        </transition>

        <transition name="cart-drawer-slide">
            <aside
                v-if="isOpen"
                id="header-cart-drawer"
                ref="drawer"
                class="cart-drawer"
                role="dialog"
                aria-modal="true"
                :aria-label="labels.title"
            >
                <header class="cart-drawer-header">
                    <h2 class="cart-drawer-heading">
                        {{ labels.title }}
                        <span v-if="count > 0" class="cart-drawer-count">{{ count }}</span>
                    </h2>
                    <button
                        ref="close"
                        class="cart-drawer-close"
                        type="button"
                        :aria-label="labels.close"
                        @click="closeDrawer"
                    >
                        <span aria-hidden="true">&times;</span>
                    </button>
                </header>

                <template v-if="count > 0">
                    <div class="cart-drawer-items">
                        <article class="cart-drawer-item" v-for="item in cartItems" :key="itemKey(item)">
                            <a class="cart-drawer-item-image" :href="productUrl(item)">
                                <img
                                    v-if="itemImage(item)"
                                    :src="itemImage(item)"
                                    :alt="item.name"
                                    :title="item.name"
                                >
                                <span v-else class="cart-drawer-image-placeholder" aria-hidden="true">
                                    <i class="fa-regular fa-book"></i>
                                </span>
                            </a>

                            <div class="cart-drawer-item-content">
                                <h3 class="cart-drawer-item-title" :title="item.name">
                                    <a :href="productUrl(item)">{{ item.name }}</a>
                                </h3>

                                <div class="cart-drawer-item-meta">
                                    <span class="cart-drawer-item-price">
                                        {{ hasConditions(item)
                                            ? (item.associatedModel && item.associatedModel.main_special_text ? item.associatedModel.main_special_text : '')
                                            : (item.associatedModel && item.associatedModel.main_price_text ? item.associatedModel.main_price_text : '')
                                        }}
                                    </span>
                                    <span class="cart-drawer-item-quantity">&times; {{ item.quantity }}</span>
                                </div>

                                <div class="cart-drawer-item-meta cart-drawer-item-meta-secondary" v-if="item.associatedModel && item.associatedModel.secondary_price">
                                    <span>
                                        {{ hasConditions(item)
                                            ? (item.associatedModel.secondary_special_text || '')
                                            : (item.associatedModel.secondary_price_text || '')
                                        }}
                                    </span>
                                    <span>&times; {{ item.quantity }}</span>
                                </div>
                            </div>

                            <button
                                class="cart-drawer-remove"
                                type="button"
                                :aria-label="labels.remove + ': ' + item.name"
                                :title="labels.remove"
                                @click="removeFromCart(item)"
                            >
                                <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                            </button>
                        </article>
                    </div>

                    <footer class="cart-drawer-footer">
                        <div class="cart-drawer-total-row">
                            <span class="cart-drawer-total-label">{{ labels.total }}</span>
                            <span class="cart-drawer-total-value">{{ formattedTotal }}</span>
                        </div>
                        <div v-if="hasSecondary" class="cart-drawer-total-secondary">{{ formattedTotalSecondary }}</div>

                        <a class="btn d-block w-100 cart-drawer-checkout" :href="carturl">
                            <i class="fa-duotone fa-credit-card me-2 fs-base align-middle" aria-hidden="true"></i>{{ labels.checkout }}
                        </a>
                    </footer>
                </template>

                <div class="cart-empty-state" v-else>
                    <span class="cart-empty-state__icon">
                        <i class="fa-regular fa-bag-shopping" aria-hidden="true"></i>
                    </span>
                    <p class="cart-empty-state__text">{{ labels.emptyCart }}</p>
                </div>
            </aside>
        </transition>
    </div>
</template>

<script>
import { resolveCartItemImage } from '../../cart-image';

export default {
    props: {
        carturl: String,
        checkouturl: String
    },

    data() {
        return {
            base_path: window.location.origin + '/',
            success_path: window.location.origin + '/kosarica/success',
            mobile: false,
            isOpen: false,
            previousBodyOverflow: '',
            lastActiveElement: null
        };
    },

    computed: {
        cart() {
            const s = (this.$store && this.$store.state) ? this.$store.state : {};
            const c = s.cart || {};
            const count = Number(c.count || 0);
            const rawItems = c.items;
            const items = Array.isArray(rawItems) ? rawItems : (rawItems ? Object.values(rawItems) : []);
            return Object.assign({}, c, {count, items});
        },

        count() {
            return Number(this.cart.count || 0);
        },

        cartItems() {
            return this.cart.items || [];
        },

        formattedTotal() {
            const svc = (this.$store && this.$store.state) ? this.$store.state.service : null;
            const total = (this.$store && this.$store.state && this.$store.state.cart) ? (this.$store.state.cart.total || 0) : 0;
            return (svc && typeof svc.formatMainPrice === 'function')
                ? svc.formatMainPrice(total)
                : Number(total).toFixed(2);
        },

        hasSecondary() {
            return !!(this.$store && this.$store.state && this.$store.state.cart && this.$store.state.cart.secondary_price);
        },

        formattedTotalSecondary() {
            const svc = (this.$store && this.$store.state) ? this.$store.state.service : null;
            const total = (this.$store && this.$store.state && this.$store.state.cart) ? (this.$store.state.cart.total || 0) : 0;
            return (svc && typeof svc.formatSecondaryPrice === 'function')
                ? svc.formatSecondaryPrice(total)
                : '';
        },

        labels() {
            const t = (window.FrontTranslations && window.FrontTranslations.js && window.FrontTranslations.js.cart)
                ? window.FrontTranslations.js.cart
                : {};
            return (document.documentElement.lang || 'hr') === 'en' ? {
                title: t.title || 'Cart',
                total: t.total || 'Total',
                checkout: t.complete_purchase || 'Complete purchase',
                emptyCart: t.empty_cart || 'Your cart is empty!',
                remove: t.remove || 'Remove',
                close: (t.add_modal && t.add_modal.close) || 'Close',
                openCart: t.open_cart || 'Open cart'
            } : {
                title: t.title || 'Košarica',
                total: t.total || 'Ukupno',
                checkout: t.complete_purchase || 'Dovrši kupnju',
                emptyCart: t.empty_cart || 'Vaša košarica je prazna!',
                remove: t.remove || 'Ukloni',
                close: (t.add_modal && t.add_modal.close) || 'Zatvori',
                openCart: t.open_cart || 'Otvori košaricu'
            };
        }
    },

    mounted() {
        this.checkCart();

        if (window.location.pathname === '/kosarica/success') {
            this.$store.dispatch('flushCart');
        }

        this.updateMobileState();
        window.addEventListener('resize', this.updateMobileState);

        if (window.location.pathname === '/pregled') {
            window.setInterval(this.checkCart, 15000);
        }

        document.addEventListener('keydown', this.handleKeydown);
    },

    beforeDestroy() {
        document.removeEventListener('keydown', this.handleKeydown);
        window.removeEventListener('resize', this.updateMobileState);
        if (this.isOpen) {
            this.restoreBodyScroll();
        }
    },

    methods: {
        handleTriggerClick(event) {
            if (window.innerWidth < 800) {
                return;
            }

            event.preventDefault();
            this.toggleDrawer();
        },

        updateMobileState() {
            const mobile = window.innerWidth < 800;

            if (mobile && this.isOpen) {
                this.closeDrawer();
            }

            this.mobile = mobile;
        },

        toggleDrawer() {
            if (this.isOpen) {
                this.closeDrawer();
                return;
            }

            this.openDrawer();
        },

        openDrawer() {
            this.lastActiveElement = document.activeElement;
            this.previousBodyOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            this.isOpen = true;

            this.$nextTick(() => {
                if (this.$refs.close) {
                    this.$refs.close.focus();
                }
            });
        },

        closeDrawer() {
            if (!this.isOpen) {
                return;
            }

            this.isOpen = false;
            this.restoreBodyScroll();

            this.$nextTick(() => {
                const target = this.lastActiveElement || this.$refs.trigger;
                if (target && typeof target.focus === 'function') {
                    target.focus();
                }
            });
        },

        restoreBodyScroll() {
            document.body.style.overflow = this.previousBodyOverflow || '';
        },

        handleKeydown(event) {
            if (!this.isOpen) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                this.closeDrawer();
                return;
            }

            if (event.key !== 'Tab' || !this.$refs.drawer) {
                return;
            }

            const focusable = Array.from(this.$refs.drawer.querySelectorAll(
                'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'
            ));

            if (!focusable.length) {
                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        },

        itemKey(item) {
            const optId = (item && item.options && item.options.option_id) ? item.options.option_id : '';
            return (item && item.id ? item.id : 'x') + '-' + optId;
        },

        hasConditions(item) {
            const c = (item && item.conditions) ? item.conditions : null;
            return c ? Object.keys(c).length > 0 : false;
        },

        productUrl(item) {
            const path = (item && item.attributes && item.attributes.path)
                ? item.attributes.path
                : ((item && item.associatedModel && item.associatedModel.url) ? item.associatedModel.url : '');
            return this.base_path + path;
        },

        itemImage(item) {
            return resolveCartItemImage(item);
        },

        checkCart() {
            try {
                const storage = (this.$store && this.$store.state && this.$store.state.storage && this.$store.state.storage.getCart)
                    ? this.$store.state.storage.getCart()
                    : null;

                if (this.$store && this.$store.dispatch) {
                    this.$store.dispatch('getSettings');
                }

                if (!storage) {
                    if (this.$store && this.$store.dispatch) {
                        this.$store.dispatch('getCart');
                    }
                    return;
                }

                const raw = storage.items;
                const items = Array.isArray(raw) ? raw : (raw ? Object.values(raw) : []);
                const kos = items.map(i => i.id).filter(Boolean);

                if (this.$store && this.$store.dispatch) {
                    this.$store.dispatch('checkCart', kos);
                }
            } catch (e) {
                if (this.$store && this.$store.dispatch) {
                    this.$store.dispatch('getCart');
                }
            }
        },

        removeFromCart(item) {
            if (this.$store && this.$store.dispatch) {
                this.$store.dispatch('removeFromCart', item);
            }
        }
    }
};
</script>

<style scoped>
.cart-drawer-root {
    position: relative;
}

.cart-drawer-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    border: 0;
    background: transparent;
    color: inherit;
    text-decoration: none;
}

.cart-drawer-backdrop {
    position: fixed;
    inset: 0;
    width: 100vw;
    height: 100vh;
    height: 100dvh;
    padding: 0;
    border: 0;
    background: rgba(21, 44, 24, .42);
    cursor: default;
    z-index: 11035;
}

.cart-drawer {
    display: flex;
    position: fixed;
    top: 0;
    right: 0;
    width: min(30rem, 100vw);
    height: 100vh;
    height: 100dvh;
    flex-direction: column;
    overflow: hidden;
    border-left: 1px solid #e3e8e4;
    background: #fff;
    box-shadow: -.75rem 0 2.25rem rgba(49, 72, 55, .18);
    white-space: normal;
    z-index: 11040;
}

.cart-drawer-header {
    display: flex;
    min-height: 5rem;
    flex: 0 0 auto;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.15rem 1.5rem;
    border-bottom: 1px solid #e3e9ef;
}

.cart-drawer-heading {
    display: flex;
    align-items: center;
    gap: .65rem;
    margin: 0;
    color: #373f50;
    font-size: 1.3rem;
    font-weight: 600;
}

.cart-drawer-count {
    display: inline-flex;
    min-width: 1.7rem;
    height: 1.7rem;
    align-items: center;
    justify-content: center;
    padding: 0 .4rem;
    border-radius: 999px;
    background: #f4f1e7;
    color: #8f7333;
    font-size: .78rem;
    font-weight: 600;
}

.cart-drawer-close {
    display: inline-flex;
    width: 2.5rem;
    height: 2.5rem;
    flex: 0 0 auto;
    align-items: center;
    justify-content: center;
    padding: 0;
    border: 0;
    border-radius: 50%;
    background: #f3f5f9;
    color: #4b566b;
    font-size: 1.65rem;
    font-weight: 300;
    line-height: 1;
    transition: background-color .2s ease, color .2s ease;
}

.cart-drawer-close:hover,
.cart-drawer-close:focus-visible {
    background: #ebe6d8;
    color: #314837;
}

.cart-drawer-items {
    min-height: 0;
    flex: 1 1 auto;
    overflow-x: hidden;
    overflow-y: auto;
    overscroll-behavior: contain;
    padding: .25rem 1.5rem;
    scrollbar-color: #b8beaf transparent;
    scrollbar-width: thin;
}

.cart-drawer-items::-webkit-scrollbar {
    width: .35rem;
}

.cart-drawer-items::-webkit-scrollbar-thumb {
    border-radius: 99px;
    background: #b8beaf;
}

.cart-drawer-item {
    display: grid;
    grid-template-columns: 4.5rem minmax(0, 1fr) 1.85rem;
    align-items: center;
    gap: .8rem;
    min-width: 0;
    padding: .75rem 0;
    border-bottom: 1px solid #eee4c9;
}

.cart-drawer-item-image {
    display: flex;
    width: 4.5rem;
    height: 5.25rem;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border-radius: .25rem;
    background: #f6f4ee;
}

.cart-drawer-item-image img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.cart-drawer-image-placeholder {
    color: #bfa76a;
    font-size: 1.75rem;
}

.cart-drawer-item-content {
    min-width: 0;
}

.cart-drawer-item-title {
    display: -webkit-box;
    max-width: 100%;
    margin: 0 0 .55rem;
    overflow: hidden;
    color: #373f50;
    font-size: 1rem;
    font-weight: 600;
    line-height: 1.35;
    overflow-wrap: anywhere;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.cart-drawer-item-title a {
    color: inherit;
}

.cart-drawer-item-title a:hover {
    color: #9f8339;
}

.cart-drawer-item-meta {
    display: flex;
    min-width: 0;
    flex-wrap: wrap;
    align-items: baseline;
    gap: .3rem .7rem;
    font-size: .9rem;
}

.cart-drawer-item-price {
    color: #b08f3d;
    font-weight: 500;
}

.cart-drawer-item-quantity,
.cart-drawer-item-meta-secondary {
    color: #7d879c;
}

.cart-drawer-item-meta-secondary {
    margin-top: .2rem;
    font-size: .78rem;
}

.cart-drawer-remove {
    display: inline-flex;
    width: 2rem;
    height: 2rem;
    align-items: center;
    justify-content: center;
    align-self: center;
    padding: 0;
    border: 0;
    border-radius: 50%;
    background: transparent;
    color: #9aa1ae;
    transition: background-color .2s ease, color .2s ease;
}

.cart-drawer-remove:hover,
.cart-drawer-remove:focus-visible {
    background: #fff0f3;
    color: #d83c60;
}

.cart-drawer-footer {
    flex: 0 0 auto;
    padding: 1.15rem 1.5rem max(1.5rem, env(safe-area-inset-bottom));
    border-top: 1px solid #e3e9ef;
    background: #fff;
    box-shadow: 0 -.5rem 1.25rem rgba(49, 72, 55, .06);
}

.cart-drawer-total-row {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: .2rem;
}

.cart-drawer-total-label {
    color: #7d879c;
    font-size: 1rem;
}

.cart-drawer-total-value {
    color: #b08f3d;
    font-size: 1.35rem;
    font-weight: 600;
    text-align: right;
}

.cart-drawer-total-secondary {
    margin-bottom: .8rem;
    color: #7d879c;
    font-size: .8rem;
    text-align: right;
}

.cart-drawer-checkout {
    margin-top: .9rem;
    padding-top: .85rem;
    padding-bottom: .85rem;
    border-color: #2f7d52;
    background: #2f7d52;
    box-shadow: 0 .35rem .9rem rgba(47, 125, 82, .18);
    color: #fff;
    font-size: 1rem;
    font-weight: 600;
    text-transform: none;
    transition: background-color .16s ease, border-color .16s ease, box-shadow .16s ease, transform .16s ease;
}

.cart-drawer-checkout:hover,
.cart-drawer-checkout:focus {
    border-color: #286b46;
    background: #286b46;
    box-shadow: 0 .45rem 1rem rgba(40, 107, 70, .24);
    color: #fff;
    transform: translateY(-1px);
}

.cart-drawer-checkout:focus-visible {
    box-shadow: 0 0 0 .2rem rgba(47, 125, 82, .25), 0 .45rem 1rem rgba(40, 107, 70, .22);
    outline: 0;
}

.cart-drawer-checkout:active {
    border-color: #245f3e;
    background: #245f3e;
    box-shadow: none;
    transform: translateY(0);
}

.cart-drawer-fade-enter-active,
.cart-drawer-fade-leave-active {
    transition: opacity .25s ease;
}

.cart-drawer-fade-enter,
.cart-drawer-fade-leave-to {
    opacity: 0;
}

.cart-drawer-slide-enter-active,
.cart-drawer-slide-leave-active {
    transition: transform .32s cubic-bezier(.22, 1, .36, 1);
}

.cart-drawer-slide-enter,
.cart-drawer-slide-leave-to {
    transform: translateX(100%);
}

.cart-empty-state {
    display: flex;
    min-height: 0;
    flex: 1 1 auto;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: .8rem;
    padding: 2rem 1.5rem;
    background: #fff;
    text-align: center;
}

.cart-empty-state__icon {
    display: inline-flex;
    width: 3.5rem;
    height: 3.5rem;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #f4f1e7;
    color: #8f7333;
    font-size: 1.45rem;
}

.cart-empty-state__text {
    margin: 0;
    color: #4b566b;
    font-size: .9rem;
    font-weight: 500;
    line-height: 1.45;
}

@media (max-width: 499.98px) {
    .cart-drawer-header {
        min-height: 4.5rem;
        padding: .9rem 1rem;
    }

    .cart-drawer-items {
        padding-right: 1rem;
        padding-left: 1rem;
    }

    .cart-drawer-item {
        grid-template-columns: 4rem minmax(0, 1fr) 1.75rem;
        gap: .65rem;
        padding: .7rem 0;
    }

    .cart-drawer-item-image {
        width: 4rem;
        height: 4.75rem;
    }

    .cart-drawer-footer {
        padding-right: 1rem;
        padding-left: 1rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .cart-drawer-fade-enter-active,
    .cart-drawer-fade-leave-active,
    .cart-drawer-slide-enter-active,
    .cart-drawer-slide-leave-active {
        transition-duration: .01ms;
    }
}
</style>
