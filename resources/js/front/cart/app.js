/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

import Vue from "vue";
window.Vue = Vue;
import Vuex from 'vuex';
window.Vuex = Vuex;
Vue.use(Vuex);

import VueRouter from 'vue-router'
Vue.use(VueRouter)

const router = new VueRouter({
    mode: 'history',
});

import VueSweetalert2 from "vue-sweetalert2";
import 'sweetalert2/dist/sweetalert2.min.css';
Vue.use(VueSweetalert2)

import store from './store.js';

//import Storage from './services/Storage'

Vue.component('cart-nav-icon', require('./components/CartNavIcon/CartNavIcon').default);
Vue.component('cart-footer-icon', require('./components/CartFooterIcon/CartFooterIcon').default);
Vue.component('add-to-cart-btn', require('./components/AddToCartBtn/AddToCartBtn').default);
Vue.component('add-to-cart-btn-simple', require('./components/AddToCartBtnSimple/AddToCartBtnSimple').default);
Vue.component('cart-view', () => import(/* webpackChunkName: "cart-checkout" */ './components/CartView/CartView.vue'));
Vue.component('cart-view-aside', () => import(/* webpackChunkName: "cart-checkout" */ './components/CartViewAside/CartViewAside.vue'));
Vue.component('filter-view', () => import(/* webpackChunkName: "catalog-filter" */ './../filter/components/Filter/Filter.vue'));
Vue.component('products-view', () => import(/* webpackChunkName: "catalog-filter" */ './../filter/components/ProductsList/ProductsList.vue'));
Vue.component('pagination', () => import(/* webpackChunkName: "catalog-filter" */ './../filter/components/Pagination/LaravelVuePagination.vue'));

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
    el: '#agapp',
    router,
    store: new Vuex.Store(store)
});


window.ToastSuccess = app.$swal.mixin({
    toast: true,
    icon: 'success',
    position: 'top-end',
    showConfirmButton: false,
    timer: 2500,
})

window.ToastWarning = app.$swal.mixin({
    toast: true,
    icon: 'warning',
    position: 'top-end',
    showConfirmButton: false,
    timer: 2500,
})

window.ToastWarningLong = app.$swal.mixin({
    toast: true,
    icon: 'warning',
    position: 'top-end',
    showConfirmButton: false,
    timer: 5000,
})
