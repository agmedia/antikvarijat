<template>
    <div>
        <div v-if="field == 'price'">
            <a href="javascript:void(0)" v-if="product.special && ! view_input" v-on:click="viewField">
                <s>{{ formatPrice(product.price) }}</s><br>
                <strong>{{ formatPrice(product.special) }}</strong>
            </a>
            <strong v-if="! product.special && ! view_input" style="cursor: pointer;"><span v-on:click="viewField">{{ formatPrice(product.price) }}</span></strong>
            <div class="input-group" v-if="view_input">
                <input type="text" class="form-control" v-model="field_value" v-on:keyup.enter="updateField">
                <div class="input-group-append">
                    <button type="button" class="btn btn-alt-success" v-on:click="updateField"><i class="fa fa-save"></i></button>
                </div>
            </div>
        </div>

        <div v-if="field == 'text'">
            <span v-if="!view_input" style="cursor: pointer;" v-on:click="viewField">{{ field_value }}</span>
            <div class="input-group" v-if="view_input">
                <input type="text" class="form-control" v-model="field_value" v-on:keyup.enter="updateField">
                <div class="input-group-append">
                    <button type="button" class="btn btn-alt-success" v-on:click="updateField"><i class="fa fa-save"></i></button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: {
            item: {
                type: String,
                required: true,
                default: ''
            },
            target: {
                type: String,
                required: true,
                default: ''
            },
            field: {
                type: String,
                required: false,
                default: 'text'
            },
        },
        //
        data() {
            return {
                product: JSON.parse(this.item),
                view_input: false,
                field_value: '...',
            }
        },
        //
        mounted() {
            this.setFieldValue();
        },
        //
        methods: {
            /**
             *
             */
            setFieldValue() {
                if (this.field == 'price') {
                    this.field_value = Number(this.product.price).toFixed(2);
                    this.checkSpecials();
                }
                if (this.field == 'text' && this.product[this.target]) {
                    this.field_value = this.product[this.target];
                }

                if (this.target == 'quantity' && this.product[this.target] === 0) {
                    this.field_value = '0';
                }
            },
            /**
             *
             * @param price
             * @return {string}
             */
            formatPrice(price) {
                return Number(price).toLocaleString('hr-HR', {
                    style: 'currency',
                    //currencyDisplay: 'narrowSymbol',
                    currencyDisplay: 'symbol',
                    currency: 'EUR'
                });
            },
            /**
             *
             */
            viewField() {
                this.view_input = true;
            },
            /**
             *
             */
            checkSpecials() {
                let now = new Date();
                let from = new Date(this.product.special_from);
                let to = new Date(this.product.special_to);

                if (now > from && now < to) {
                } else {
                    this.product.special = null;
                }
            },
            /**
             *
             */
            updateField() {
                let product = {
                    item: this.product,
                    target: this.target,
                    new_value: this.field_value
                };
                let context = this;

                axios.post('products/update-item/single', { product }).then(response => {
                    if (response.data.success) {
                        successToast.fire();
                        this.view_input = false;

                        if (this.target == 'price') {
                            context.product.price = response.data.value_1;
                            context.product.special = response.data.value_2;
                        }
                        if (this.field == 'text') {
                            context.product[this.target] = response.data.value_1;
                        }
                    }
                    //
                    if (response.data.error) {
                        this.view_input = false;
                    }
                });
            }
        }
    };
</script>
<style>

</style>
