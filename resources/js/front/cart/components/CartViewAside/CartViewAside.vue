<template>
    <div>
        <div class="bg-white rounded-3 shadow-lg p-4" v-if="route == 'kosarica'">
            <div class="py-2 px-xl-2">
                <div class="text-center mb-4 pb-3 border-bottom">
                    <h2 class="h6 mb-3 pb-1">Ukupno</h2>
                    <h3 class="fw-normal">{{ $store.state.service.formatPrice($store.state.cart.total) }}</h3>
                    <h4 class="fw-normal" v-if="$store.state.cart.eur">{{ ($store.state.cart.total * $store.state.cart.eur).toFixed(2) }} €</h4>
                </div>
                <a class="btn btn-green btn-shadow d-block w-100 mt-4" :href="checkouturl">NASTAVI NA NAPLATU</a>
                <p class="small fw-light text-center mt-2">* Cijena dostave će biti izračunata na koraku 3: Dostava</p>
            </div>
        </div>


        <div class="bg-white rounded-3 shadow-lg p-4 ms-lg-auto" v-if="route == 'naplata'">
            <div class="py-2 px-xl-2">
                <div class="widget mb-3">
                    <h2 class="widget-title text-center">Sažetak narudžbe</h2>
                    <hr class="mb-2">
                    <div class="d-flex align-items-center pb-2 border-bottom" v-for="item in $store.state.cart.items">
                        <a class="d-block flex-shrink-0" :href="base_path + item.attributes.path"><img :src="item.associatedModel.image" :alt="item.name" width="64"></a>
                        <div class="ps-2">
                            <h6 class="widget-product-title"><a :href="base_path + item.attributes.path">{{ item.name }}</a></h6>
                            <div class="widget-product-meta"><span class="text-accent me-2">{{ Object.keys(item.conditions).length ? $store.state.service.formatPrice(item.price - item.conditions.parsedRawValue) : $store.state.service.formatPrice(item.price) }}</span><span class="text-muted">x {{ item.quantity }}</span></div>
                            <div class="widget-product-meta"><span class="text-accent me-2" v-if="$store.state.cart.eur">{{ Object.keys(item.conditions).length ? item.associatedModel.eur_special : item.associatedModel.eur_price }} €</span><span class="text-muted">x {{ item.quantity }}</span></div>
                        </div>
                    </div>
                </div>
                <ul class="list-unstyled fs-sm pb-2 border-bottom">
                    <li class="d-flex justify-content-between align-items-center"><span class="me-2">Ukupno:</span><span class="text-end">{{ $store.state.service.formatPrice($store.state.cart.subtotal) }}</span></li>
                    <li v-if="$store.state.cart.eur" class="d-flex justify-content-between align-items-center">
                        <span class="me-2"></span><span class="text-end">{{ ($store.state.cart.subtotal * $store.state.cart.eur).toFixed(2) }} €</span>
                    </li>
                    <div v-for="condition in $store.state.cart.detail_con">
                        <li class="d-flex justify-content-between align-items-center"><span class="me-2">{{ condition.name }}</span><span class="text-end">{{ $store.state.service.formatPrice(condition.value) }}</span></li>
                        <li v-if="$store.state.cart.eur" class="d-flex justify-content-between align-items-center"><span class="me-2"></span><span class="text-end">{{ (condition.value * $store.state.cart.eur).toFixed(2) }} €</span></li>
                    </div>
                </ul>
                <h3 class="fw-normal text-center my-2">{{ $store.state.service.formatPrice($store.state.cart.total) }}</h3>
                <h4 v-if="$store.state.cart.eur" class="fw-normal text-center my-2">{{ ($store.state.cart.total * $store.state.cart.eur).toFixed(2) }} €</h4>
                <p class="small fw-light text-center mt-4 mb-0">
                    <span class="fw-normal">{{ ($store.state.service.calculateItemsTax($store.state.cart.items)).toFixed(2) }} kn</span> PDV knjige i
                    <span class="fw-normal">{{ ($store.state.service.calculateItemsTax($store.state.cart.total - $store.state.cart.subtotal)).toFixed(2) }} kn</span> PDV dostava
                </p>
                <p class="small fw-light text-center mt-2 mb-0" v-if="$store.state.cart.eur">
                    <span class="fw-normal">{{ ($store.state.service.calculateItemsTax($store.state.cart.items) * $store.state.cart.eur).toFixed(2) }} €</span> PDV knjige i
                    <span class="fw-normal">{{ ($store.state.service.calculateItemsTax($store.state.cart.total - $store.state.cart.subtotal) * $store.state.cart.eur).toFixed(2) }} €</span> PDV dostava
                </p>
                <p class="small text-center mt-0 mb-0">*Uračunato u cijenu</p>
            </div>
        </div>


        <div class="bg-white rounded-3 shadow-lg p-4 ms-lg-auto" v-if="route == 'pregled'">
            <div class="py-2 px-xl-2">
                <div class="widget mb-3">
                    <h2 class="widget-title text-center">Sažetak narudžbe</h2>
                </div>
                <ul class="list-unstyled fs-sm pb-2 border-bottom">
                    <li class="d-flex justify-content-between align-items-center"><span class="me-2">Ukupno:</span><span class="text-end">{{ $store.state.service.formatPrice($store.state.cart.subtotal) }}</span></li>
                    <li v-if="$store.state.cart.eur" class="d-flex justify-content-between align-items-center">
                        <span class="me-2"></span><span class="text-end">{{ ($store.state.cart.subtotal * $store.state.cart.eur).toFixed(2) }} €</span>
                    </li>
                    <div v-for="condition in $store.state.cart.detail_con">
                        <li class="d-flex justify-content-between align-items-center"><span class="me-2">{{ condition.name }}</span><span class="text-end">{{ $store.state.service.formatPrice(condition.value) }}</span></li>
                        <li v-if="$store.state.cart.eur" class="d-flex justify-content-between align-items-center"><span class="me-2"></span><span class="text-end">{{ (condition.value * $store.state.cart.eur).toFixed(2) }} €</span></li>
                    </div>
                </ul>
                <h3 class="fw-normal text-center my-2">{{ $store.state.service.formatPrice($store.state.cart.total) }}</h3>
                <h4 v-if="$store.state.cart.eur" class="fw-normal text-center my-2">{{ ($store.state.cart.total * $store.state.cart.eur).toFixed(2) }} €</h4>
                <p class="small fw-light text-center mt-4 mb-0">
                    <span class="fw-normal">{{ ($store.state.service.calculateItemsTax($store.state.cart.items)).toFixed(2) }} kn</span> PDV knjige i
                    <span class="fw-normal">{{ ($store.state.service.calculateItemsTax($store.state.cart.total - $store.state.cart.subtotal)).toFixed(2) }} kn</span> PDV dostava
                </p>
                <p class="small fw-light text-center mt-2 mb-0" v-if="$store.state.cart.eur">
                    <span class="fw-normal">{{ ($store.state.service.calculateItemsTax($store.state.cart.items) * $store.state.cart.eur).toFixed(2) }} €</span> PDV knjige i
                    <span class="fw-normal">{{ ($store.state.service.calculateItemsTax($store.state.cart.total - $store.state.cart.subtotal) * $store.state.cart.eur).toFixed(2) }} €</span> PDV dostava
                </p>
                <p class="small text-center mt-0 mb-0">*Uračunato u cijenu</p>
            </div>
        </div>
    </div>

</template>

<script>
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
        mounted() {
            if (window.innerWidth < 800) {
                this.mobile = true;
            }

            this.checkIfEmpty();
            this.setCoupon();

            if (window.location.pathname == '/kosarica/naplata') {
                this.show_delete_btn = false;
            }
        },

        methods: {
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
</style>
