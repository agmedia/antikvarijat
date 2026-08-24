/* */
let storage_cart = {
    name: 'sl_cart',
    cart: {
        items: [],
        count: 0,
        subtotal: 0,
        total: 0,
        conditions: [],
        detail_con: [],
        coupon: null,
        secondary_price: false
    }
};
const currentLocale = (document.documentElement.lang || 'hr').toLowerCase();
const frontTranslations = window.FrontTranslations || {};
const translatedMessages = frontTranslations.js && frontTranslations.js.messages ? frontTranslations.js.messages : {};
const messagesByLocale = {
    hr: {
        error: 'Whoops!... Server connection error!',
        cartAdd: 'Product added to cart.',
        cartUpdate: 'Product quantity has been updated.',
        cartRemove: 'Product removed from cart.',
        couponSuccess: 'Coupon has been added to your cart.',
        couponError: 'Unfortunately, no coupon was found for that code.',
    },
    en: {
        error: 'Whoops!... Server connection error!',
        cartAdd: 'Product added to cart.',
        cartUpdate: 'Product quantity has been updated.',
        cartRemove: 'Product removed from cart.',
        couponSuccess: 'Coupon has been added to your cart.',
        couponError: 'Unfortunately, no coupon was found for that code.',
    }
};
let fallbackMessages = messagesByLocale[currentLocale] || messagesByLocale.hr;
let messages = {
    error: translatedMessages.server_error || fallbackMessages.error,
    cartAdd: translatedMessages.cart_add || fallbackMessages.cartAdd,
    cartUpdate: translatedMessages.cart_update || fallbackMessages.cartUpdate,
    cartRemove: translatedMessages.cart_remove || fallbackMessages.cartRemove,
    couponSuccess: translatedMessages.coupon_success || fallbackMessages.couponSuccess,
    couponError: translatedMessages.coupon_error || fallbackMessages.couponError,
};


class AgService {

    /**
     *
     * @returns {*}
     */
    getCart() {
        return axios.get('cart/get')
        .then(response => { return response.data })
        .catch(error => { return this.returnError(messages.error) })
    }

    /**
     *
     * @param item
     * @returns {*}
     */
    checkCart(ids) {
        return axios.post('cart/check', {ids: ids})
        .then(response => { return response.data })
        .catch(error => { return this.returnError(messages.error) })
    }


    /**
     *
     * @param item
     * @returns {*}
     */
    addToCart(item) {
        return axios.post('cart/add', {item: item})
        .then(response => {
            if (response.data.error) {
                this.returnError(response.data.error);
                return false;
            }

            const productItem = this.findCartItem(response.data, item.id);
            const product = productItem ? productItem.associatedModel : null;

            if (product && product.dataLayer) {
                const quantity = Number(item.quantity) || 1;
                const ecommerceItem = { ...product.dataLayer, quantity };
                const price = Number(ecommerceItem.price) || 0;

                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({ ecommerce: null });
                window.dataLayer.push({
                    event: 'add_to_cart',
                    ecommerce: {
                        currency: ecommerceItem.currency || 'EUR',
                        value: Number((price * quantity).toFixed(2)),
                        items: [ecommerceItem]
                    }
                });
            }

            this.showCartAddSuccess(response.data, item);
            return response.data
        })
        .catch(error => { return this.returnError(messages.error) })
    }

    /**
     *
     * @param item
     * @returns {*}
     */
    updateCart(item) {
        return axios.post('cart/update/' + item.id, {item: item})
        .then(response => {
            if (response.data.error) {
                this.returnError(response.data.error);
                return false;
            }

            this.returnSuccess(messages.cartUpdate);
            return response.data
        })
        .catch(error => { return this.returnError(messages.error) })
    }

    /**
     *
     * @param item
     * @returns {*}
     */
    removeItem(item) {
        return axios.get('cart/remove/' + item.id)
        .then(response => {
            this.returnSuccess(messages.cartRemove);
            return response.data
        })
        .catch(error => { return this.returnError(messages.error) })
    }

    /**
     *
     * @param coupon
     * @returns {*}
     */
    checkCoupon(coupon) {
        if ( ! coupon) {
            coupon = null;
        }
        return axios.post('cart/coupon', {coupon: coupon})
        .then(response => {
            //this.returnSuccess(messages.couponSuccess);
            return response.data
        })
        .catch(error => { return 0/*this.returnError(messages.error)*/ })
    }

    /**
     *
     * @returns {*}
     */
    getSettings() {
        return axios.get('settings/get')
        .then(response => { return response.data })
        .catch(error => { return this.returnError(messages.error) })
    }

    /**
     *
     * @param msg
     * @returns {*}
     */
    returnSettings(settings) {
        window.AGSettings = settings;
    }

    /**
     *
     * @param msg
     * @returns {*}
     */
    returnError(msg) {
        window.ToastWarning.fire(msg);
    }

    /**
     *
     * @param msg
     * @returns {*}
     */
    returnSuccess(msg) {
        window.ToastSuccess.fire(msg);
    }

    showCartAddSuccess(cart, item) {
        try {
            if (typeof window.CartAddSuccess === 'function') {
                const result = window.CartAddSuccess({ cart, item });

                if (result !== null) {
                    return;
                }
            }
        } catch (error) {
            console.error(error);
        }

        this.returnSuccess(messages.cartAdd);
    }

    findCartItem(cart, itemId) {
        const items = cart && cart.items ? cart.items : {};

        if (items[itemId]) {
            return items[itemId];
        }

        return Object.values(items).find((cartItem) => String(cartItem && cartItem.id) === String(itemId)) || null;
    }

    /**
     * Returns HR formated price string.
     *
     * @param price
     * @returns {string}
     */
    formatPrice(price) {
        return Number(price).toLocaleString('hr-HR', {
            style: 'currency',
            //currencyDisplay: 'narrowSymbol',
            currencyDisplay: 'symbol',
            currency: 'HRK'
        });
    }

    /**
     * Returns HR formated price string.
     *
     * @param price
     * @returns {string}
     */
    formatMainPrice(price) {

        if(store.state.settings) {
            let list = store.state.settings['currency.list'] || [];
            let main_currency = {};

            list.forEach((item) => {
                if (item.main) {
                    main_currency = item;
                }
            });

            let left = main_currency.symbol_left ? main_currency.symbol_left + ' ' : '';
            let right = main_currency.symbol_right ? ' ' + main_currency.symbol_right : '';
            let currency_value = Number(main_currency.value);
            let decimal_places = Number(main_currency.decimal_places);
            let numeric_price = Number(price);

            currency_value = Number.isFinite(currency_value) ? currency_value : 1;
            decimal_places = Number.isFinite(decimal_places) ? decimal_places : 2;
            numeric_price = Number.isFinite(numeric_price) ? numeric_price : 0;

            return left + Number(numeric_price * currency_value).toFixed(decimal_places) + right;
        }
    }

    /**
     * Returns HR formated price string.
     *
     * @param price
     * @returns {string}
     */
    formatSecondaryPrice(price) {
        if(store.state.settings) {
        let list = store.state.settings['currency.list'] || [];
        let main_currency = {};

        list.forEach((item) => {
            if ( ! item.main) {
                main_currency = item;
                return;
            }
        });

        let left = main_currency.symbol_left ? main_currency.symbol_left + ' ' : '';
        let right = main_currency.symbol_right ? ' ' + main_currency.symbol_right : '';
        let currency_value = Number(main_currency.value);
        let decimal_places = Number(main_currency.decimal_places);
        let numeric_price = Number(price);

        currency_value = Number.isFinite(currency_value) ? currency_value : 1;
        decimal_places = Number.isFinite(decimal_places) ? decimal_places : 2;
        numeric_price = Number.isFinite(numeric_price) ? numeric_price : 0;

        return left + Number(numeric_price * currency_value).toFixed(decimal_places) + right;
        }
    }

    /**
     * Calculate tax on items.
     * Item can be number or object.
     *
     * @param items
     * @return {string}
     */
    getDiscountAmount(price, special) {
        let discount = ((price - special) / price) * 100;

        return Math.round(discount).toFixed(0);
    }

    /**
     * Calculate tax on items.
     * Item can be number or object.
     *
     * @param items
     * @return {string}
     */
    calculateItemsTax(items) {
        let tax = 0;

        if (isNaN(items)) {
            for (const key in items) {
                tax += items[key].price - (items[key].price / (Number(items[key].attributes.tax.rate) / 100 + 1));
            }
        } else {
            tax = items - (items / 1.25);
        }

        return tax;
    }
}


class AgStorage {

    /**
     *
     * @returns {JSON}
     */
    getCart() {
        let item = localStorage.getItem(storage_cart.name);

        return (item && item != 'undefined') ? JSON.parse(item) : null;
    }

    /**
     *
     * @param value
     * @returns localStorage item
     */
    setCart(value) {
        return localStorage.setItem(storage_cart.name, JSON.stringify(value));
    }
}

/**/
let store = {
    state: {
        storage: new AgStorage(),
        service: new AgService(),
        cart: storage_cart.cart,
        messages: messages,
        settings: null
    },

    actions: {
        /**
         *
         * @param context
         * @returns {*}
         */
        getCart(context) {
            context.commit('setCart');
        },

        /**
         *
         * @param context
         * @param item
         */
        addToCart(context, item) {
            let state = context.state;

            state.service.addToCart(item).then(cart => {
                if (cart) {
                    state.storage.setCart(cart);
                    state.cart = cart;
                }
            });
        },

        /**
         *
         * @param context
         * @param item
         */
        updateCart(context, item) {
            let state = context.state;

            state.service.updateCart(item).then(cart => {
                if (cart) {
                    state.storage.setCart(cart);
                    state.cart = cart;
                }
            });
        },

        /**
         *
         * @param context
         * @param item
         */
        removeFromCart(context, item) {
            let state = context.state;

            state.service.removeItem(item).then(cart => {
                state.storage.setCart(cart);
                state.cart = cart;
            });
        },

        /**
         *
         * @param context
         * @param ids
         */
        checkCart(context, ids) {
            let state = context.state;

            state.service.checkCart(ids).then(response => {
                if (!response || !response.cart) {
                    return;
                }

                state.storage.setCart(response.cart);
                state.cart = response.cart;

                if (response.message && window.location.pathname != '/uspjeh') {
                    //window.ToastWarningLong.fire(response.message)

                   /* if (window.location.pathname != '/kosarica') {
                        window.setTimeout(() => {
                            window.location.href = '/kosarica';
                        }, 5000);
                    }*/
                }

            })
        },

        /**
         *
         * @param context
         * @param coupon
         */
        checkCoupon(context, coupon) {
            let state = context.state;

            state.cart.coupon = coupon;
            state.storage.setCart(state.cart);

            state.service.checkCoupon(coupon).then(response => {
                if (response) {
                    state.service.returnSuccess(messages.couponSuccess);
                } else {
                    state.service.returnError(messages.couponError);
                }

                context.commit('setCart');
            });
        },

        /**
         *
         * @param context
         */
        flushCart(context) {
            const emptyCart = { ...storage_cart.cart };

            context.state.storage.setCart(emptyCart);
            context.state.cart = emptyCart;
        },

        /**
         *
         * @param context
         * @param item
         */
        getSettings(context, item) {
            let state = context.state;

            state.service.getSettings(item).then(settings => {
                if (settings) {
                    state.settings = settings;
                }
            });
        },
    },

    mutations: {

        /**
         *
         * @param state
         * @returns {*}
         */
        setCart(state) {
            return state.service.getCart().then(cart => {
                if (!cart) {
                    return;
                }

                state.cart = cart;

                return state.storage.setCart(cart);
            });
        }
    },
};

export default store;
