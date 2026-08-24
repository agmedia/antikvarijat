<template>
    <div>
        <div class=" rounded-3  p-4" v-if="route == 'kosarica'" style="border: 1px dashed #e3e9ef;background-color: #fff !important;">
            <div class="py-2 px-xl-2" v-cloak>
                <div class="text-center mb-2 pb-2">
                    <h2 class="h6 mb-3 pb-1">{{ labels.total }}</h2>
                    <h3 class="fw-bold text-primary">{{ $store.state.service.formatMainPrice($store.state.cart.total) }}</h3>
                    <h4 class="fs-sm" v-if="$store.state.cart.secondary_price">{{ $store.state.service.formatSecondaryPrice($store.state.cart.total) }}</h4>
                </div>
                <a class="btn checkout-cta btn-shadow d-block w-100 mt-4" :href="checkouturl">{{ labels.continueToCheckout }} <i class="fa-solid fa-arrow-right fs-sm" aria-hidden="true"></i></a>
            </div>
        </div>

        <div class="rounded-3 p-4 ms-lg-auto" v-if="route == 'naplata'" style="border: 1px dashed #e3e9ef;background-color: #fff !important;">
            <div class="py-2 px-xl-2">
                <div class="widget mb-3">
                    <h2 class="widget-title text-center mb-2">{{ labels.orderSummary }}</h2>

                    <div class="d-flex align-items-center pb-2 border-bottom" :class="{'cart-aside-gift-voucher': isGiftVoucher(item)}" v-for="item in $store.state.cart.items" :key="item.id">
                        <a class="d-block flex-shrink-0" :href="base_path + item.attributes.path"><img :src="itemImage(item)" :alt="item.name" width="64"></a>
                        <div class="ps-2">
                            <h6 class="widget-product-title"><a :href="base_path + item.attributes.path">{{ item.name }}</a></h6>
                            <div class="widget-product-meta"><span class="text-primary me-2">{{ Object.keys(item.conditions).length ? item.associatedModel.main_special_text : item.associatedModel.main_price_text }}</span><span class="text-muted" v-if="!isGiftVoucher(item)">x {{ item.quantity }}</span></div>
                            <div class="widget-product-meta" v-if="item.associatedModel.secondary_price_text || !isGiftVoucher(item)"><span class="text-muted me-2" v-if="item.associatedModel.secondary_price_text">{{ Object.keys(item.conditions).length ? item.associatedModel.secondary_special_text : item.associatedModel.secondary_price_text }}</span><span class="text-muted" v-if="!isGiftVoucher(item)">x {{ item.quantity }}</span></div>
                            <div class="cart-aside-gift-voucher-details" v-if="isGiftVoucher(item)">
                                <div><span>{{ labels.giftRecipient }}</span><strong>{{ giftVoucherData(item).recipient_name }}</strong></div>
                                <small>{{ giftVoucherData(item).recipient_email }}</small>
                                <div><span>{{ labels.giftSender }}</span><strong>{{ giftVoucherData(item).sender_name }}</strong></div>
                                <p v-if="giftVoucherData(item).message">“{{ giftVoucherData(item).message }}”</p>
                            </div>
                        </div>
                    </div>
                </div>
                <ul class="list-unstyled fs-sm pb-2 border-bottom">
                    <li class="d-flex justify-content-between align-items-center"><span class="me-2">{{ labels.total }}:</span><span class="text-end">{{ $store.state.service.formatMainPrice($store.state.cart.subtotal) }}</span></li>
                    <li v-if="$store.state.cart.secondary_price" class="d-flex justify-content-between align-items-center">
                        <span class="me-2"></span><span class="text-end">{{ $store.state.service.formatSecondaryPrice($store.state.cart.subtotal) }}</span>
                    </li>
                    <div v-for="condition in $store.state.cart.detail_con">
                        <li class="d-flex justify-content-between align-items-center"><span class="me-2">{{ condition.name }}</span><span class="text-end">{{ $store.state.service.formatMainPrice(condition.value) }}</span></li>
                        <li v-if="$store.state.cart.secondary_price" class="d-flex justify-content-between align-items-center"><span class="me-2"></span><span class="text-end">{{ $store.state.service.formatSecondaryPrice(condition.value) }}</span></li>
                    </div>
                </ul>
                <h3 class="fw-bold text-primary text-center my-2">{{ $store.state.service.formatMainPrice($store.state.cart.total) }}</h3>
                <h4 v-if="$store.state.cart.secondary_price" class="fs-sm text-center my-2">{{ $store.state.service.formatSecondaryPrice($store.state.cart.total) }}</h4>
                <p class="small text-center mt-0 mb-0">{{ labels.taxIncluded }}</p>
            </div>
        </div>

        <div class="rounded-3 p-4 ms-lg-auto" v-if="route == 'pregled'" style="border: 1px dashed #e3e9ef;background-color: #fff !important;">
            <div class="py-2 px-xl-2">
                <div class="widget mb-3">
                    <h2 class="widget-title text-center">{{ labels.orderSummary }}</h2>
                </div>
                <ul class="list-unstyled fs-sm pb-2 border-bottom">
                    <li class="d-flex justify-content-between align-items-center"><span class="me-2">{{ labels.total }}:</span><span class="text-end">{{ $store.state.service.formatMainPrice($store.state.cart.subtotal) }}</span></li>
                    <li v-if="$store.state.cart.secondary_price" class="d-flex justify-content-between align-items-center">
                        <span class="me-2"></span><span class="text-end">{{ $store.state.service.formatSecondaryPrice($store.state.cart.subtotal) }}</span>
                    </li>
                    <div v-for="condition in $store.state.cart.detail_con">
                        <li class="d-flex justify-content-between align-items-center"><span class="me-2">{{ condition.name }}</span><span class="text-end">{{ $store.state.service.formatMainPrice(condition.value) }}</span></li>
                        <li v-if="$store.state.cart.secondary_price" class="d-flex justify-content-between align-items-center"><span class="me-2"></span><span class="text-end">{{ $store.state.service.formatSecondaryPrice(condition.value) }}</span></li>
                    </div>
                </ul>
                <h3 class="fw-bold text-primary text-center my-2">{{ $store.state.service.formatMainPrice($store.state.cart.total) }}</h3>
                <h4 v-if="$store.state.cart.secondary_price" class="fs-sm text-center my-2">{{ $store.state.service.formatSecondaryPrice($store.state.cart.total) }}</h4>
                <p class="small text-center mt-0 mb-0">{{ labels.taxIncluded }}</p>
            </div>
        </div>

        <div class="rounded-3 p-4 mt-3" v-if="(route == 'kosarica' || route == 'naplata') && !hasGiftVoucherPurchase" style="border: 1px dashed #e3e9ef;background-color: #fff !important;">
            <div class="py-2 px-xl-2" v-cloak>
                <div class="form-group">

                    <label class="form-label">{{ labels.couponQuestion }}</label>
                    <div class="input-group">
                        <input type="text" class="form-control" v-model="coupon" :placeholder="labels.couponPlaceholder">
                        <div class="input-group-append">
                            <button type="button" v-on:click="setCoupon" class="btn btn-outline-primary btn-shadow">{{ labels.add }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>



    </div>

</template>

<script>
import { resolveCartItemImage } from '../../cart-image';

export default {
    props: {
        continueurl: String,
        checkouturl: String,
        buttons: {type: Boolean, default: true},
        route: String
    },
    data() {
        return {
            base_path: window.location.origin + '/',
            mobile: false,
            show_delete_btn: true,
            coupon: '',
            tax: 0,
        }
    },
    computed: {
        hasGiftVoucherPurchase() {
            if (this.$store.state.cart.has_gift_voucher || this.$store.state.cart.gift_voucher_only) {
                return true;
            }

            return Object.values(this.$store.state.cart.items || {}).some((item) => this.isGiftVoucher(item));
        },
        labels() {
            const t = (window.FrontTranslations && window.FrontTranslations.js && window.FrontTranslations.js.cart)
                ? window.FrontTranslations.js.cart
                : {};
            const gift = (window.FrontTranslations && window.FrontTranslations.gift_voucher)
                ? window.FrontTranslations.gift_voucher
                : {};
            return (document.documentElement.lang || 'hr') === 'en' ? {
                total: t.total || 'Total',
                continueToCheckout: t.continue_to_checkout || 'Continue to checkout',
                orderSummary: t.order_summary || 'Order summary',
                taxIncluded: t.tax_included || 'VAT included in the price',
                couponQuestion: t.coupon_question || 'Do you have a discount code?',
                couponPlaceholder: t.coupon_placeholder || 'Enter code here...',
                add: t.add || 'Add',
                giftRecipient: gift.cart_recipient || 'Recipient',
                giftSender: gift.cart_sender || 'From'
            } : {
                total: t.total || 'Total',
                continueToCheckout: t.continue_to_checkout || 'Nastavi na naplatu',
                orderSummary: t.order_summary || 'Order summary',
                taxIncluded: t.tax_included || 'VAT included in the price',
                couponQuestion: t.coupon_question || 'Do you have a discount code?',
                couponPlaceholder: t.coupon_placeholder || 'Enter code here...',
                add: t.add || 'Add',
                giftRecipient: gift.cart_recipient || 'Primatelj',
                giftSender: gift.cart_sender || 'Šalje'
            };
        }
    },
    mounted() {
        if (window.innerWidth < 800) {
            this.mobile = true;
        }


        this.checkIfEmpty();
            if (window.location.pathname == '/kosarica/naplata') {
                this.show_delete_btn = false;
            }
    },

    methods: {
        itemImage(item) {
            return resolveCartItemImage(item);
        },

        isGiftVoucher(item) {
            const attributes = item && item.attributes ? item.attributes : {};

            return attributes.item_type === 'gift_voucher'
                || attributes.type === 'gift_voucher'
                || item.id === 'gift-voucher';
        },

        giftVoucherData(item) {
            return item && item.attributes && item.attributes.gift_voucher
                ? item.attributes.gift_voucher
                : {};
        },

        /**
         *
         * @param item
         */
        updateCart(item) {
            this.$store.dispatch('updateCart', item);
        },

        /**
         *
         * @param item
         */
        removeFromCart(item) {
            this.$store.dispatch('removeFromCart', item);
        },

        /**
         *
         * @param qty
         * @returns {number|*}
         * @constructor
         */
        CheckQuantity(qty) {
            if (qty < 1) {
                return 1;
            }

            return qty;
        },

        /**
         *
         */
        checkIfEmpty() {
            let cart = this.$store.state.storage.getCart();

            // Check coupon
            if (cart && cart.coupon != '' && cart.coupon != 'null') {
                this.coupon = cart.coupon;
            }

            if (cart && ! cart.count && window.location.pathname != '/kosarica') {
                window.location.href = '/kosarica';
            }
        },

        /**
         *
         */
        setCoupon() {
            let cart = this.$store.state.storage.getCart();
            if (cart) {
                cart.coupon = this.coupon;
                this.checkCoupon();
            }
        },


        /**
         *
         */
        /**
         *
         */
        checkCoupon() {
            this.$store.dispatch('checkCoupon', this.coupon);
        },




        /**
         *
         */

    }
};
</script>


<style>
.table th, .table td {
    padding: 0.75rem 0.45rem !important;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
}
.empty th, .empty td {
    padding: 1rem !important;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
}
.cart-aside-gift-voucher {
    align-items: flex-start !important;
    gap: .25rem;
    margin-bottom: .65rem;
    padding: .7rem;
    border: 1px solid #dbe8df !important;
    border-radius: .35rem;
    background: linear-gradient(135deg, #fbfdfb 0%, #f7f2e5 100%);
}
.cart-aside-gift-voucher-details {
    margin-top: .45rem;
    color: #4d5852;
    font-size: .75rem;
}
.cart-aside-gift-voucher-details div {
    display: flex;
    flex-wrap: wrap;
    gap: .3rem;
}
.cart-aside-gift-voucher-details div span {
    color: #68726c;
}
.cart-aside-gift-voucher-details small {
    display: block;
    overflow-wrap: anywhere;
}
.cart-aside-gift-voucher-details p {
    margin: .35rem 0 0;
    line-height: 1.4;
}
</style>
