<template>
    <div>
            <div role="alert" class="alert alert-secondary d-flex fs-sm" v-if="!hasGiftVoucherPurchase && $store.state.cart.total < freeship && $store.state.cart.count"><div class="alert-icon"><i class="fa-duotone fa-gift" aria-hidden="true"></i></div> <div>{{ labels.freeShippingRemainingStart }} {{ $store.state.service.formatMainPrice(freeship - $store.state.cart.total) }} <span v-if="$store.state.cart.secondary_price">({{ $store.state.service.formatSecondaryPrice(freeship - $store.state.cart.total) }})</span> {{ labels.freeShippingRemainingEnd }}</div></div>

            <div role="alert" class="alert alert-secondary d-flex fs-sm" v-if="!hasGiftVoucherPurchase && $store.state.cart.total > freeship && $store.state.cart.count"><div class="alert-icon"><i class="fa-duotone fa-gift" aria-hidden="true"></i></div> <div>{{ labels.freeShippingUnlocked }}</div></div>

            <div class="cart-page-section-heading">
                <h2 class="h6 text-dark mb-0">{{ labels.items }}</h2>
            </div>
            <div class="cart-page-section-heading" v-if="!$store.state.cart.count">
                <p class="text-dark mb-0">{{ labels.emptyCart }}</p>
            </div>



        <!-- Item-->
        <div class="cart-page-item" :class="{'cart-page-item--gift-voucher': isGiftVoucher(item)}" v-for="item in $store.state.cart.items" :key="item.id">
            <div class="cart-page-product">
                <a class="cart-page-thumb" :href="base_path + item.attributes.path">
                    <img class="cart-page-thumb-image" :src="itemImage(item)" :alt="item.name" :title="item.name">
                </a>
                <div class="cart-page-product-info">
                    <h3 class="product-title cart-page-title"><a :href="base_path + item.attributes.path">{{ item.name }}</a></h3>

                    <div class="text-accent cart-page-price">{{ Object.keys(item.conditions).length ? item.associatedModel.main_special_text : item.associatedModel.main_price_text }}</div>
                    <div class="text-accent cart-page-price cart-page-price-secondary" v-if="item.associatedModel.secondary_price">{{ Object.keys(item.conditions).length ? item.associatedModel.secondary_special_text : item.associatedModel.secondary_price_text }}</div>

                    <div class="cart-page-gift-voucher" v-if="isGiftVoucher(item)">
                        <div class="cart-page-gift-voucher-detail">
                            <span>{{ labels.giftRecipient }}</span>
                            <strong>{{ giftVoucherData(item).recipient_name }}</strong>
                            <small>{{ giftVoucherData(item).recipient_email }}</small>
                        </div>
                        <div class="cart-page-gift-voucher-detail">
                            <span>{{ labels.giftSender }}</span>
                            <strong>{{ giftVoucherData(item).sender_name }}</strong>
                        </div>
                        <div class="cart-page-gift-voucher-message" v-if="giftVoucherData(item).message">
                            <span>{{ labels.giftMessage }}</span>
                            <p>“{{ giftVoucherData(item).message }}”</p>
                        </div>
                        <div class="cart-page-gift-voucher-notice"><i class="fa-solid fa-envelope" aria-hidden="true"></i>{{ labels.giftNotice }}</div>
                    </div>
                </div>
            </div>
            <div class="cart-page-controls">
                <template v-if="!isGiftVoucher(item)">
                    <label class="form-label cart-page-quantity-label">{{ labels.quantity }}</label>
                    <input class="form-control cart-page-quantity" type="number" v-model="item.quantity" min="1" :max="item.associatedModel.quantity" @click.prevent="updateCart(item)">
                </template>
                <button class="btn btn-link text-danger cart-page-remove" type="button" @click.prevent="removeFromCart(item)"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i><span>{{ labels.remove }}</span></button>
            </div>
        </div>

        <div class="d-flex pt-3 pb-2 mt-1" v-if="show_buttons">
            <a class="btn btn-outline-primary btn-sm btn-shadow mt-3" :href="continueurl"><i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i>{{ labels.backToShop }}</a>
        </div>

    </div>
</template>

<script>
    import { resolveCartItemImage } from '../../cart-image';

    export default {
        props: {
            continueurl: String,
            checkouturl: String,
            freeship: String,
            buttons: {type: String, default: 'true'},
        },
        data() {
            return {
                base_path: window.location.origin + '/',
                mobile: false,
                show_delete_btn: true,
                coupon: '',
                show_buttons: true,
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
                    freeShippingRemainingStart: t.free_shipping_remaining_start || 'Only',
                    freeShippingRemainingEnd: t.free_shipping_remaining_end || 'left for free delivery!',
                    freeShippingUnlocked: t.free_shipping_unlocked || 'You qualify for free delivery!',
                    items: t.items || 'Items',
                    emptyCart: t.empty_cart || 'Your cart is empty!',
                    quantity: t.quantity || 'Quantity',
                    remove: t.remove || 'Remove',
                    backToShop: t.back_to_shop || 'Back to shop',
                    giftRecipient: gift.cart_recipient || 'Recipient',
                    giftSender: gift.cart_sender || 'From',
                    giftMessage: gift.message || 'Personal message',
                    giftNotice: gift.cart_notice || 'The voucher will be emailed after confirmed card payment.'
                } : {
                    freeShippingRemainingStart: t.free_shipping_remaining_start || 'Još',
                    freeShippingRemainingEnd: t.free_shipping_remaining_end || 'do besplatne dostave!',
                    freeShippingUnlocked: t.free_shipping_unlocked || 'Ostvarili ste pravo na besplatnu dostavu!',
                    items: t.items || 'Artikli',
                    emptyCart: t.empty_cart || 'Vaša košarica je prazna!',
                    quantity: t.quantity || 'Količina',
                    remove: t.remove || 'Ukloni',
                    backToShop: t.back_to_shop || 'Natrag na trgovinu',
                    giftRecipient: gift.cart_recipient || 'Primatelj',
                    giftSender: gift.cart_sender || 'Šalje',
                    giftMessage: gift.message || 'Osobna poruka',
                    giftNotice: gift.cart_notice || 'Bon se šalje e-mailom nakon potvrđenog kartičnog plaćanja.'
                };
            }
        },
        mounted() {
            if (window.innerWidth < 800) {
                this.mobile = true;
            }

            if (this.buttons == 'false') {
                this.show_buttons = false;
            } else {
                this.show_buttons = true;
            }

            this.checkIfEmpty();
            this.setCoupon();

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
                console.log(item);

                let _item = {
                    id: item.id,
                    quantity: 1,
                    relative: true
                };

                console.log(_item);

                this.$store.dispatch('updateCart', _item);
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

                if (cart && ! cart.count && window.location.pathname != '/kosarica') {
                    window.location.href = '/kosarica';
                }
            },

            /**
             *
             */
            setCoupon() {
                let cart = this.$store.state.storage.getCart();

                this.coupon = cart.coupon;
            },

            /**
             *
             */
            checkCoupon() {
                this.$store.dispatch('checkCoupon', this.coupon);
            }
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
.mobile-prices {
    font-size: .66rem;
    color: #999999;
}
.cart-page-section-heading {
    display: flex;
    padding: .8rem 0 .35rem;
}
.cart-page-item {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    gap: 1.25rem;
    min-width: 0;
    padding: .65rem 0;
    border-bottom: 1px solid #eee4c9;
}
.cart-page-item--gift-voucher {
    align-items: flex-start;
    padding: 1rem;
    border: 1px solid #dbe8df;
    border-radius: .45rem;
    background: linear-gradient(135deg, #fbfdfb 0%, #f7f2e5 100%);
}
.cart-page-product {
    display: grid;
    grid-template-columns: 3.75rem minmax(0, 1fr);
    align-items: center;
    gap: .85rem;
    min-width: 0;
}
.cart-page-thumb {
    display: flex;
    width: 3.75rem;
    height: 4.25rem;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border-radius: .2rem;
    background: #f6f4ee;
}
.cart-page-thumb-image {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain;
}
.cart-page-product-info {
    min-width: 0;
}
.cart-page-title {
    margin: 0 0 .3rem;
    overflow: hidden;
    font-size: .98rem;
    line-height: 1.3;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.cart-page-price {
    padding: 0;
    font-size: .98rem;
    line-height: 1.25;
}
.cart-page-price-secondary {
    margin-top: .15rem;
    color: #7d879c !important;
    font-size: .75rem;
}
.cart-page-gift-voucher {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .55rem 1rem;
    margin-top: .75rem;
}
.cart-page-gift-voucher-detail,
.cart-page-gift-voucher-message {
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.cart-page-gift-voucher-detail span,
.cart-page-gift-voucher-message span {
    color: #68726c;
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
}
.cart-page-gift-voucher-detail strong,
.cart-page-gift-voucher-detail small {
    overflow-wrap: anywhere;
}
.cart-page-gift-voucher-message {
    grid-column: 1 / -1;
}
.cart-page-gift-voucher-message p {
    margin: .15rem 0 0;
    color: #4d5852;
    font-size: .82rem;
    line-height: 1.45;
}
.cart-page-gift-voucher-notice {
    display: flex;
    grid-column: 1 / -1;
    align-items: center;
    gap: .4rem;
    color: #2a6248;
    font-size: .76rem;
    font-weight: 600;
}
.cart-page-controls {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: .55rem;
}
.cart-page-quantity-label {
    margin: 0;
    font-size: .78rem;
    font-weight: 600;
    white-space: nowrap;
}
.cart-page-quantity {
    width: 4.25rem;
    height: 2.35rem;
    padding: .35rem .55rem;
}
.cart-page-remove {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    margin: 0;
    padding: .35rem .2rem;
    font-size: .82rem;
    line-height: 1.2;
    white-space: nowrap;
}
@media (max-width: 575.98px) {
    .cart-page-section-heading {
        padding-top: .65rem;
    }
    .cart-page-item {
        grid-template-columns: minmax(0, 1fr);
        gap: .5rem;
        padding: .7rem 0;
    }
    .cart-page-product {
        grid-template-columns: 3.5rem minmax(0, 1fr);
        gap: .75rem;
    }
    .cart-page-thumb {
        width: 3.5rem;
        height: 4rem;
    }
    .cart-page-title {
        display: -webkit-box;
        white-space: normal;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }
    .cart-page-controls {
        justify-content: flex-start;
        padding-left: 4.25rem;
    }
    .cart-page-item--gift-voucher .cart-page-controls {
        padding-left: 0;
    }
    .cart-page-gift-voucher {
        grid-template-columns: minmax(0, 1fr);
    }
    .cart-page-gift-voucher-message,
    .cart-page-gift-voucher-notice {
        grid-column: 1;
    }
}
</style>
