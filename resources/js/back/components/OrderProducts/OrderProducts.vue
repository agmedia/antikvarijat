<template>
    <div class="OrderProducts">
        <div class="admin-order-product-search mb-4">
            <label for="order-product-search">Dodaj artikl</label>
            <input id="order-product-search" type="search" v-model="query" @keyup="autoComplete" class="form-control" placeholder="Upišite naziv ili šifru artikla" autocomplete="off">
                <div class="admin-order-autocomplete" v-if="results.length">
                    <ul class="list-group">
                        <li class="list-group-item" v-for="result in results" @click="select(result)">
                            {{ result.name }} -  {{ result.sku }}
                        </li>
                    </ul>
                </div>
        </div>

        <div class="admin-order-products-table-wrap table-responsive" v-if="items.length">
                <table class="table table-hover table-vcenter admin-order-products-table">
                    <thead>
                    <tr>
                        <th class="text-center px-0" style="width: 3%;"></th>
                        <th class="text-center" style="width: 5%;">#</th>
                        <th>Artikl</th>
                        <th class="text-center" style="width: 7%;">Kol.</th>
                        <th class="text-center" style="width: 12%;">Jed. cijena</th>
                        <th class="text-center" style="width: 12%;">Iznos</th>
                        <th class="text-center" style="width: 12%;">Rabat</th>
                        <th class="text-center" style="width: 12%;">Ukupno</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(product, index) in items" class="admin-order-product-row">
                        <td class="text-center px-0 admin-order-product-remove">
                            <button type="button" class="btn btn-sm btn-link text-danger" title="Ukloni artikl" aria-label="Ukloni artikl" @click="removeRow(index)">
                                <i class="fa-duotone fa-trash-can"></i>
                            </button>
                        </td>
                        <td class="text-center admin-order-product-index">{{ index + 1 }}</td>
                        <td class="admin-order-product-name" data-label="Artikl">{{ product.name }}</td>
                        <td class="text-center" data-label="Količina">
                            <input type="text" class="form-control form-control-sm text-center" :value="product.quantity" @keyup="ChangeQty(product.id, $event)" @blur="Recalculate()" aria-label="Količina">
                        </td>
                        <td class="text-right" data-label="Jed. cijena">
                            <input v-if="product.edit" type="text" class="form-control form-control-sm text-right" :value="product.org_price" @keyup.enter="product.edit=false; $emit('update')" @blur="product.edit=false; ChangePrice(product.id, $event); $emit('update')" aria-label="Jedinična cijena">
                            <span v-else @click="product.edit=true;">{{ Number(product.org_price).toLocaleString(localization, currency_style) }}</span>
                        </td>
                        <td class="text-right" data-label="Iznos">{{ Number(product.org_price * product.quantity).toLocaleString(localization, currency_style) }}</td>
                        <td class="text-right" data-label="Rabat">
                            <input v-if="product.edit" type="text" class="form-control form-control-sm text-right" :value="product.rabat" @keyup.enter="product.edit=false; $emit('update')" @blur="product.edit=false; ChangeRabat(product.id, $event); $emit('update')" aria-label="Rabat">
                            <span v-else @click="product.edit=true;">-{{ Number((product.rabat) * product.quantity).toLocaleString(localization, currency_style) }}</span>
                        </td>
                        <td class="text-right font-w600" data-label="Ukupno">{{ Number(product.total).toLocaleString(localization, currency_style) }}</td>
                    </tr>

                    <!-- Totals -->
                    <tr v-if="sums.length" v-for="(total, index) in sums" class="admin-order-total-row">
                        <td colspan="6" class="text-right">{{ total.name }}:</td>
                        <td colspan="2" class="text-right font-w600">{{ Number(total.value).toLocaleString(localization, currency_style) }}</td>
                    </tr>

                    <input type="hidden" :value="JSON.stringify(items)" name="items">
                    <input type="hidden" :value="JSON.stringify(sums)" name="sums">

                    </tbody>
                </table>

        </div>
    </div>
</template>

<script>
export default {
    props: {
        products: {
            type: String,
            required: false,
            default: []
        },
        totals: {
            type: String,
            required: false,
            default: []
        },
        products_autocomplete_url: {
            type: String,
            required: true
        }
    },
    //
    data() {
        return {
            products_local: [],
            totals_local: [],
            query: '',
            results: [],
            items: [],
            sums: [],
            selected_product: {},
            is_shipping: true,
            shipping_value: 30,
            is_action: false,
            action_value: 0,
            currency_style: {
                style: 'currency',
                currency: 'EUR'
            },
            localization: 'de-DE'
        }
    },
    //
    mounted() {
        if (this.products.length && this.totals.length) {
            this.products_local = JSON.parse(this.products)
            this.totals_local = JSON.parse(this.totals)
            this.Sort()
        }
    },
    //
    methods: {

        /**
         *
         * @constructor
         */
        Sort() {
            this.products_local.forEach((item) => {
                this.items.push({
                    id: item.product_id,
                    name: item.name,
                    quantity: item.quantity,
                    price: item.price,
                    org_price: item.org_price,
                    rabat: item.org_price - item.price,
                    total: item.total,
                    edit: false
                })
            })

            this.Recalculate()
        },

        /**
         *
         * @param selected
         */
        select(selected) {
            this.results = [];
            this.query = '';
            let price = selected.price;

            if (selected.actions) {
                if (selected.actions.price) {
                    price = selected.actions.price;
                }
                if (selected.actions.discount) {
                    price = selected.price - (selected.price * (selected.actions.discount / 100));
                }
            }

            this.items.push({
                id: selected.id,
                name: selected.name,
                quantity: 1,
                price: price,
                org_price: selected.price,
                rabat: selected.price - price,
                total: price,
                edit: false
            })

            this.Recalculate();
        },

        /**
         *
         * @param row
         * @param product
         */
        removeRow(row, product) {
            this.items.splice(row, 1);

            if (!this.items.length) {
                this.sums = [];
            }

            this.Recalculate();
        },

        /**
         *
         * @param id
         * @param event
         * @constructor
         */
        ChangeQty(id, event) {
            for (let i = 0; i < this.items.length; i++) {
                if (this.items[i].id == id) {
                    this.items[i].quantity = Number(event.target.value);
                    this.items[i].total = this.items[i].price * Number(event.target.value);
                }
            }
            this.Recalculate();
        },

        /**
         *
         * @param id
         * @param event
         * @constructor
         */
        ChangePrice(id, event) {
            for (let i = 0; i < this.items.length; i++) {
                if (this.items[i].id == id) {
                    let inserted_price = Number(event.target.value);

                    if (inserted_price > this.items[i].rabat) {
                        this.items[i].org_price = inserted_price;
                        this.items[i].price = Number(this.items[i].org_price) - this.items[i].rabat;
                        this.items[i].total = Number(this.items[i].price) * this.items[i].quantity;
                    }
                }
            }
            this.Recalculate();
        },

        /**
         *
         * @param id
         * @param event
         * @constructor
         */
        ChangeRabat(id, event) {
            for (let i = 0; i < this.items.length; i++) {
                if (this.items[i].id == id) {
                    let inserted_rabat = Number(event.target.value);

                    if (inserted_rabat < this.items[i].org_price) {
                        this.items[i].rabat = inserted_rabat;
                        this.items[i].price = Number(this.items[i].org_price) - inserted_rabat;
                        this.items[i].total = Number(this.items[i].price) * this.items[i].quantity;
                    }
                }
            }
            this.Recalculate();
        },

        /**
         *
         */
        Recalculate() {
            this.sums = [];
            let subtotal = 0;
            let total = 0;

            this.items.forEach((item) => {
                subtotal = subtotal + Number(item.total);
            });

            total = subtotal;

            this.totals_local.forEach((item) => {
                if (item.code == 'shipping' || item.code == 'payment') {
                    total += Number(item.value);
                }
            });

            this.totals_local.forEach((item) => {
                let value = Number(item.value);

                if (item.code == 'subtotal') {
                    value = subtotal;
                }

                if (item.code == 'total') {
                    value = total;
                }

                this.sums.push({
                    name: item.title,
                    title: item.title,
                    value: value,
                    code: item.code
                });
            });
        },

        /**
         *
         */
        autoComplete() {
            this.results = []

            if (this.query.length > 2) {
                axios.get(this.products_autocomplete_url, {params: {query: this.query}}).then(response => {
                    this.results = response.data;
                })
            }
        }
    }
};
</script>

<style>
.admin-order-product-search {
    position: relative;
}

.admin-order-autocomplete {
    position: absolute;
    z-index: 1075;
    top: calc(100% + .25rem);
    right: 0;
    left: 0;
    max-height: 18rem;
    overflow-y: auto;
    border: 1px solid #c9c5bc;
    border-radius: .24rem;
    background: #fff;
}

.admin-order-autocomplete .list-group-item {
    padding: .7rem .8rem;
    border-width: 0 0 1px;
    border-color: #e3dfd6;
    color: #202a24;
    cursor: pointer;
}

.admin-order-autocomplete .list-group-item:last-child {
    border-bottom: 0;
}

.admin-order-autocomplete .list-group-item:hover {
    color: #fff;
    background: #315344;
}

.admin-order-products-table {
    min-width: 780px;
    margin-bottom: 0;
}

.admin-order-products-table thead th {
    background: #f5f3ee;
}

@media (max-width: 767.98px) {
    .admin-order-products-table {
        min-width: 0;
    }

    .admin-order-products-table thead {
        display: none;
    }

    .admin-order-products-table tbody,
    .admin-order-products-table .admin-order-product-row,
    .admin-order-products-table .admin-order-product-row td {
        display: block;
        width: 100%;
    }

    .admin-order-products-table .admin-order-product-row {
        position: relative;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .65rem 1rem;
        padding: .85rem;
        border-bottom: 1px solid #c9c5bc;
    }

    .admin-order-products-table .admin-order-product-row td {
        padding: 0 !important;
        border: 0;
        text-align: left !important;
    }

    .admin-order-products-table .admin-order-product-row td[data-label]::before {
        display: block;
        margin-bottom: .2rem;
        color: #59665e;
        content: attr(data-label);
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .admin-order-products-table .admin-order-product-name {
        grid-column: 1 / -1;
        padding-right: 2.5rem !important;
        font-weight: 700;
    }

    .admin-order-products-table .admin-order-product-index {
        display: none !important;
    }

    .admin-order-products-table .admin-order-product-remove {
        position: absolute;
        top: .55rem;
        right: .55rem;
        width: auto !important;
    }

    .admin-order-products-table .admin-order-total-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: .75rem;
        padding: .55rem .85rem;
        border-bottom: 1px solid #e3dfd6;
    }

    .admin-order-products-table .admin-order-total-row td {
        display: block;
        width: auto;
        padding: 0 !important;
        border: 0;
    }

    .admin-order-products-table .admin-order-total-row td:first-child {
        text-align: left !important;
    }
}
</style>
