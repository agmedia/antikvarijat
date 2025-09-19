<template>
    <div class="cart mb-3 d-flex align-items-center" v-cloak v-if="show_buy">
        <div class="d-flex flex-wrap align-items-center pt-4 pb-2 mb-3">
            <input class="form-control me-3 "  type="number" inputmode="numeric" pattern="[0-9]*" v-model="quantity" min="1" :max="is_available" v-if="show_quantity" style="width: 5rem;">
            <button class="btn btn-primary btn-shadow  w-auto" @click="addToCart()"><i class="ci-cart fs-base me-1"></i> Dodaj u Košaricu</button>
        </div>
    </div>
    <div class="cart mb-3 d-flex align-items-center" v-cloak v-else>
        <a class="btn btn-primary btn-shadow d-block w-100" href="#wishlist-modal" data-bs-toggle="modal">Obavijesti me o dostupnosti</a>
    </div>
</template>

<script>
export default {
    props: {
        id: [String, Number],
        product: { type: Object, required: true },
        wishlist: [String, Number]
    },

    data() {
        return {
            quantity: 1,
            show_buy: true,
            show_quantity: false,
            context_product: {},
            is_available: 0,
            has_in_cart: 0,
        }
    },
    //


    beforeMount() {
        this.context_product = this.product;      // ✅ već je object
        this.is_available = this.context_product.quantity;

        if (this.wishlist == '0') {
            this.show_buy = false;
        }

        if (this.context_product.quantity > 1) {
            this.show_quantity = true;
        }
    },

    mounted() {

    },

    methods: {
        /**
         *
         */
        add() {
            this.checkCart();

            if (this.has_in_cart) {
                this.updateCart();
            } else {
                this.addToCart();
            }
        },

        /**
         *
         */
        addToCart() {
            let item = {
                id: this.id,
                quantity: this.quantity
            }

            this.$store.dispatch('addToCart', item);
        },

        /**
         *
         */
        updateCart() {
            /*if (parseFloat(this.quantity) > parseFloat(this.is_available)) {
                this.quantity = this.is_available;
            }*/

            let item = {
                id: this.id,
                quantity: this.quantity,
                relative: true
            }

            this.$store.dispatch('updateCart', item);
        },


        checkCart() {
            let cart = this.$store.state.storage.getCart();

            if (cart) {
                for (const key in cart.items) {
                    if (this.id == cart.items[key].id) {
                        this.has_in_cart = cart.items[key].quantity;
                    }
                }
            }
        }
    }
};
</script>
