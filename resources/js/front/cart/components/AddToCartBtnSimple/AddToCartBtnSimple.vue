<template>


        <button class="btn btn-primary btn-sm" type="button" :aria-label="buttonLabel" @click="addToCart()">
            <span v-if="label" class="add-to-cart-btn-simple__label">{{ label }}</span><span aria-hidden="true">+</span><i class="fa-regular fa-bag-shopping fs-base ms-1" aria-hidden="true"></i>
        </button>


</template>

<script>
    export default {
        props: {
            id: String,
            label: {type: String, default: ''}
        },
        data() {
            return {
                quantity: 1
            }
        },
        computed: {
            buttonLabel() {
                if (this.label) {
                    return this.label;
                }

                const cart = window.FrontTranslations && window.FrontTranslations.js
                    ? window.FrontTranslations.js.cart
                    : {};

                return cart.add_to_cart || 'Dodaj u košaricu';
            }
        },
        methods: {
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
