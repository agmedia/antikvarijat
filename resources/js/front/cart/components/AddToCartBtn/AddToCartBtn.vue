<template>
    <div class="cart mb-5 d-flex align-items-center" v-cloak v-if="show_buy">
        <input class="form-control me-3 mb-1" type="number" inputmode="numeric" pattern="[0-9]*" v-model="quantity" min="1" :max="is_available" v-if="show_quantity" style="width: 5rem;">
        <button class="btn btn-primary btn-shadow d-block w-100" @click="addToCart()">Dodaj u Košaricu</button>
    </div>
    <div class="cart mb-5 d-flex align-items-center" v-cloak v-else>
        <a class="btn btn-primary btn-shadow d-block w-100" href="#wishlist-modal" data-bs-toggle="modal">Obavjesti me o dostupnosti</a>
    </div>
</template>

<script>
export default {
    props: {
        id: String,
        product: String,
        wishlist: String
    },

    data() {
        return {
            quantity: 1,
            show_buy: true,
            show_quantity: false,
            context_product: {},
            is_available: 0,
        }
    },
    //
    beforeMount() {
        this.context_product = JSON.parse(this.product);
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
        addToCart() {
            let item = {
                id: this.id,
                quantity: this.quantity
            }

            this.$store.dispatch('addToCart', item);
        }
    }
};
</script>
