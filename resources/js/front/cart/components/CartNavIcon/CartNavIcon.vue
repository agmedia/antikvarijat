<template>
    <div class="navbar-tool dropdown ms-1">
        <a class="navbar-tool-icon-box dropdown-toggle" :href="carturl">
            <span class="navbar-tool-label">{{ count }}</span>
            <i class="navbar-tool-icon fa-regular fa-bag-shopping front-header-cart-icon" aria-hidden="true"></i>
        </a>

        <!-- Cart dropdown -->
        <div class="dropdown-menu dropdown-menu-end cart-dropdown-menu">
            <div class="widget widget-cart cart-dropdown-content" v-if="count > 0">
                <div data-simplebar-auto-hide="false" v-for="item in cartItems" :key="itemKey(item)">
                    <div class="widget-cart-item pb-2 border-bottom">
                        <button class="btn-close text-danger" type="button" @click.prevent="removeFromCart(item)" aria-label="Remove">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <div class="d-flex align-items-center">
                            <a class="d-block flex-shrink-0 pt-2" :href="productUrl(item)">
                                <img
                                    :src="(item.associatedModel && item.associatedModel.image) ? item.associatedModel.image : ''"
                                    :alt="item.name"
                                    :title="item.name"
                                    style="width: 5rem;"
                                >
                            </a>
                            <div class="ps-2">
                                <h6 class="widget-product-title">
                                    <a :href="productUrl(item)">{{ item.name }}</a>
                                </h6>

                                <div class="widget-product-meta">
                  <span class="text-primary me-2">
                    {{ hasConditions(item)
                      ? (item.associatedModel && item.associatedModel.main_special_text ? item.associatedModel.main_special_text : '')
                      : (item.associatedModel && item.associatedModel.main_price_text ? item.associatedModel.main_price_text : '')
                      }}
                  </span>
                                    <span class="text-muted">x {{ item.quantity }}</span>
                                </div>

                                <div class="widget-product-meta" v-if="item.associatedModel && item.associatedModel.secondary_price">
                  <span class="text-dark fs-sm me-2">
                    {{ hasConditions(item)
                      ? (item.associatedModel.secondary_special_text || '')
                      : (item.associatedModel.secondary_price_text || '')
                      }}
                  </span>
                                    <span class="text-muted">x {{ item.quantity }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center py-3">
                    <div class="fs-sm me-2 py-2">
                        <span class="text-muted">{{ labels.total }}:</span>
                        <span class="text-primary fs-base ms-1">{{ formattedTotal }}</span>
                        <span v-if="hasSecondary" class="text-muted">{{ formattedTotalSecondary }}</span>
                    </div>
                </div>

                <a class="btn btn-primary btn-sm d-block w-100" :href="carturl">
                    <i class="fa-duotone fa-credit-card me-2 fs-base align-middle" aria-hidden="true"></i>{{ labels.checkout }}
                </a>
            </div>

            <div class="widget widget-cart cart-empty-state" v-else>
                <span class="cart-empty-state__icon">
                    <i class="fa-regular fa-bag-shopping" aria-hidden="true"></i>
                </span>
                <p class="cart-empty-state__text">{{ labels.emptyCart }}</p>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        carturl: String,
        checkouturl: String
    },

    data() {
        return {
            base_path: window.location.origin + '/',
            success_path: window.location.origin + '/kosarica/success',
            mobile: false
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
                total: t.total || 'Total',
                checkout: t.complete_purchase || 'Complete purchase',
                emptyCart: t.empty_cart || 'Your cart is empty!'
            } : {
                total: t.total || 'Total',
                checkout: t.complete_purchase || 'Complete purchase',
                emptyCart: t.empty_cart || 'Your cart is empty!'
            };
        }
    },

    mounted() {
        this.checkCart();

        if (window.location.pathname === '/kosarica/success') {
            this.$store.dispatch('flushCart');
        }

        if (window.innerWidth < 800) {
            this.mobile = true;
        }

        if (window.location.pathname === '/pregled') {
            window.setInterval(this.checkCart, 15000);
        }
    },

    methods: {
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
            window.location.reload();
        }
    }
};
</script>

<style scoped>
.cart-dropdown-menu {
    overflow: hidden;
    padding: 0;
    border: 1px solid #e3e8e4;
    border-radius: .6rem;
    box-shadow: 0 .75rem 2rem rgba(49, 72, 55, .14);
}

.cart-dropdown-content {
    width: 24rem;
    max-width: calc(100vw - 2rem);
    padding: .8rem 1rem 1rem;
}

.cart-empty-state {
    display: flex;
    width: 19rem;
    max-width: calc(100vw - 2rem);
    min-height: 10rem;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: .8rem;
    padding: 1.4rem;
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
</style>
