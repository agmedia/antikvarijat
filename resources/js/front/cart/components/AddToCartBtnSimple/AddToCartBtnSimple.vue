<template>


        <button class="btn btn-primary  btn-sm" type="button" @click="addToCart()">+<i class="ci-cart fs-base ms-1"></i></button>


</template>

<script>
    export default {
        props: {
            id: String
        },
        data() {
            return {
                quantity: 1
            }
        },
        mounted() {
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
