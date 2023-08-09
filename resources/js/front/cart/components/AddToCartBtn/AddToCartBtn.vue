<template>
    <div class="cart mb-5 d-flex align-items-center" v-cloak>
        <button class="btn btn-primary btn-shadow d-block w-100" @click="addToCart()" v-if="show_buy">Dodaj u Košaricu</button>
        <a class="btn btn-primary btn-shadow d-block w-100" href="#wishlist-modal" data-bs-toggle="modal" v-else>Obavjesti me o dostupnosti</a>
    </div>
</template>

<script>
export default {
    props: {
        id: String,
        wishlist: String
    },

    data() {
        return {
            quantity: 1,
            show_buy: true
        }
    },

    mounted() {
        if (this.wishlist == '0') {
            this.show_buy = false;
        }
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
