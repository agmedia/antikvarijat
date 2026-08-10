@extends('front.layouts.app')

@push('css_after')
    @livewireStyles
    @include('front.checkout.partials.progress-styles')
    <style>
        .checkout-flow .checkout-panel {
            color: #536058;
            font-size: 1rem;
        }

        .checkout-flow {
            --checkout-field-background: #f8faf8;
            --checkout-field-border: #d5ded8;
            --checkout-field-color: #3f4943;
        }

        .checkout-flow .checkout-section-heading {
            padding-bottom: 1.1rem !important;
            margin-bottom: 1.35rem !important;
        }

        .checkout-flow .checkout-section-heading h2 {
            font-size: 1.35rem;
            font-weight: 600;
            line-height: 1.25;
        }

        .checkout-flow .checkout-section-heading p {
            font-size: .95rem !important;
        }

        .checkout-flow .checkout-section-heading > .text-nowrap {
            padding-top: .25rem;
            font-size: .84rem !important;
        }

        .checkout-flow .checkout-heading-icon,
        .checkout-flow .checkout-method-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            width: 2.35rem;
            height: 2.35rem;
            border-radius: 50%;
            color: var(--bs-primary);
            background: rgba(42, 98, 72, .1);
            font-size: .94rem;
        }

        .checkout-flow .checkout-method-icon {
            width: 2.15rem;
            height: 2.15rem;
            font-size: .78rem;
        }

        .checkout-flow .checkout-subsection + .checkout-subsection,
        .checkout-flow .checkout-subsection.checkout-separated {
            margin-top: 1.55rem !important;
            padding-top: 1.55rem !important;
        }

        .checkout-flow .checkout-subsection h3 {
            margin-bottom: .85rem !important;
            font-size: 1.12rem;
            font-weight: 600;
        }

        .checkout-flow .checkout-subsection h3 i {
            width: 1rem;
            font-size: .94rem;
            text-align: center;
        }

        .checkout-flow .form-label {
            margin-bottom: .38rem;
            color: #3f4943;
            font-size: .98rem;
            font-weight: 600;
        }

        .checkout-flow .form-control,
        .checkout-flow .form-select {
            min-height: 3rem;
            padding: .68rem .85rem;
            border-color: var(--checkout-field-border);
            border-radius: .42rem;
            background-color: var(--checkout-field-background);
            color: var(--checkout-field-color);
            font-size: 1rem;
            transition: border-color .15s ease-in-out, background-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }

        .checkout-flow .form-control::placeholder {
            color: #849087;
            opacity: 1;
        }

        .checkout-flow .form-control:hover,
        .checkout-flow .form-select:hover {
            border-color: #c4d0c8;
        }

        .checkout-flow textarea.form-control {
            min-height: 6rem;
        }

        .checkout-flow .form-control:focus,
        .checkout-flow .form-select:focus {
            border-color: rgba(191, 167, 106, .75);
            background-color: var(--checkout-field-background);
            color: var(--checkout-field-color);
            box-shadow: 0 0 0 .18rem rgba(191, 167, 106, .12);
        }

        .checkout-flow .form-control.is-invalid,
        .checkout-flow .form-select.is-invalid {
            border-color: var(--bs-danger);
        }

        .checkout-flow .form-control:-webkit-autofill,
        .checkout-flow .form-control:-webkit-autofill:hover,
        .checkout-flow .form-control:-webkit-autofill:focus {
            -webkit-text-fill-color: var(--checkout-field-color);
            box-shadow: 0 0 0 1000px var(--checkout-field-background) inset;
        }

        .checkout-flow .checkout-option-card {
            border: 1px solid rgba(42, 98, 72, .14);
            background: #f8faf8;
        }

        .checkout-flow .checkout-toggle-card {
            overflow: hidden;
            padding: .85rem 1rem !important;
            border-radius: .55rem !important;
        }

        .checkout-flow .checkout-toggle-content {
            margin: .85rem -1rem -.85rem !important;
            padding: .55rem .5rem .75rem !important;
            border-top: 1px solid #e3e9ef;
            background: #fff;
        }

        .checkout-flow .checkout-toggle-card .form-check-label {
            color: #37443b;
            font-size: .98rem;
        }

        .checkout-flow .checkout-toggle-card .form-text {
            margin-top: .18rem;
            font-size: .87rem;
        }

        .checkout-flow .checkout-login-prompt {
            min-height: auto;
            padding: .72rem .9rem;
            border-color: rgba(191, 167, 106, .3);
            background: #fcfbf7;
            font-size: .98rem;
        }

        .checkout-flow .checkout-login-prompt .alert-icon {
            width: 1.75rem;
            height: 1.75rem;
            margin-right: .7rem;
            transform: translateX(.3rem);
            background: transparent;
            font-size: .78rem;
        }

        .checkout-flow .checkout-login-link {
            padding: 0;
            border: 0;
            color: inherit;
            background: transparent;
            font: inherit;
        }

        .checkout-flow .checkout-date-parts {
            display: flex;
            align-items: center;
            min-height: 3rem;
            padding: .68rem .85rem;
            border: 1px solid var(--checkout-field-border);
            border-radius: .42rem;
            background: var(--checkout-field-background);
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }

        .checkout-flow .checkout-date-parts:hover {
            border-color: #c4d0c8;
        }

        .checkout-flow .checkout-date-parts:focus-within {
            border-color: rgba(191, 167, 106, .75);
            box-shadow: 0 0 0 .18rem rgba(191, 167, 106, .12);
        }

        .checkout-flow .checkout-date-parts.is-invalid {
            border-color: var(--bs-danger);
        }

        .checkout-flow .checkout-date-part {
            min-width: 0;
            padding: 0;
            border: 0;
            outline: 0;
            color: #536058;
            background: transparent;
            font: inherit;
            text-align: center;
        }

        .checkout-flow .checkout-date-part-day,
        .checkout-flow .checkout-date-part-month {
            flex: 0 0 2.5rem;
        }

        .checkout-flow .checkout-date-part-year {
            flex: 0 0 4.25rem;
        }

        .checkout-flow .checkout-date-part::placeholder {
            color: #7d879c;
            opacity: 1;
        }

        .checkout-flow .checkout-date-separator {
            color: #7d879c;
            user-select: none;
        }

        .checkout-flow .checkout-options-table {
            border-collapse: separate;
            border-spacing: 0 .65rem;
            font-size: .95rem !important;
        }

        .checkout-flow .checkout-options-table thead th {
            border: 0;
            padding-top: 0;
            padding-bottom: 0;
            color: #6c757d;
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .035em;
        }

        .checkout-flow .checkout-option-row td {
            padding-top: .75rem;
            padding-bottom: .75rem;
            border-top: 1px solid #e8ece9;
            border-bottom: 1px solid #e8ece9;
            background: #fff;
            transition: border-color .18s ease, background-color .18s ease, box-shadow .18s ease;
        }

        .checkout-flow .checkout-option-row td:first-child {
            vertical-align: middle;
            border-left: 1px solid #e8ece9;
            border-radius: .6rem 0 0 .6rem;
        }

        .checkout-flow .checkout-option-icon-cell {
            position: relative;
            width: 4.25rem;
            text-align: center;
        }

        .checkout-flow .checkout-option-radio {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            opacity: 0;
            pointer-events: none;
        }

        .checkout-flow .checkout-option-icon-cell .checkout-method-icon {
            margin: 0 auto;
        }

        .checkout-flow .checkout-option-row td:last-child {
            border-right: 1px solid #e8ece9;
            border-radius: 0 .6rem .6rem 0;
        }

        .checkout-flow .checkout-option-row:hover td {
            border-color: rgba(42, 98, 72, .3);
            background: #f8faf8;
        }

        .checkout-flow .checkout-option-row.is-selected td {
            border-color: rgba(42, 98, 72, .45);
            background: #eef5f0;
        }

        .checkout-flow .checkout-option-row.is-selected td:first-child,
        .checkout-flow .checkout-option-row:focus-within td:first-child {
            box-shadow: inset 3px 0 0 var(--bs-primary);
        }

        .checkout-flow .checkout-option-row.is-selected .checkout-method-icon {
            background: rgba(42, 98, 72, .12);
        }

        .checkout-flow .checkout-option-mobile-time {
            display: none;
        }

        .checkout-flow .checkout-option-row:focus-within td {
            border-color: rgba(191, 167, 106, .75);
        }

        .checkout-flow .checkout-actions .btn {
            min-height: 2.7rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .95rem;
            font-weight: 600;
        }

        .checkout-sidebar {
            position: sticky;
            top: 6rem;
        }

        .checkout-save-toast {
            position: fixed;
            top: 1rem;
            left: 50%;
            z-index: 1085;
            display: flex;
            align-items: center;
            gap: .55rem;
            max-width: calc(100vw - 2rem);
            padding: .7rem 1rem;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: .65rem;
            background: #2a6248;
            box-shadow: 0 .45rem 1.35rem rgba(21, 55, 40, .24);
            color: #fff;
            font-size: .9rem;
            font-weight: 600;
            opacity: 0;
            pointer-events: none;
            transform: translate(-50%, -.75rem);
            transition: opacity .18s ease, transform .18s ease;
        }

        .checkout-save-toast.is-visible {
            opacity: 1;
            transform: translate(-50%, 0);
        }

        @media (max-width: 575.98px) {
            .checkout-flow .checkout-section-heading {
                align-items: flex-start !important;
            }

            .checkout-flow .checkout-section-heading > .text-nowrap {
                display: none;
            }

            .checkout-flow .checkout-actions {
                gap: .65rem !important;
            }

            .checkout-flow .checkout-options-wrap {
                overflow: visible;
            }

            .checkout-flow .checkout-options-table {
                display: block;
                width: 100%;
                min-width: 0;
                border-spacing: 0;
            }

            .checkout-flow .checkout-options-table thead {
                display: none;
            }

            .checkout-flow .checkout-options-table tbody {
                display: block;
            }

            .checkout-flow .checkout-option-row {
                display: grid;
                width: 100%;
                min-height: 4rem;
                margin-bottom: .5rem;
                overflow: hidden;
                border: 1px solid #e2e8e4;
                border-radius: .65rem;
                background: #fff;
                touch-action: manipulation;
            }

            .checkout-flow .checkout-option-row:last-child {
                margin-bottom: 0;
            }

            .checkout-flow .checkout-option-row td {
                display: block;
                width: auto !important;
                padding: 0;
                border: 0 !important;
                border-radius: 0 !important;
                background: transparent !important;
                box-shadow: none !important;
            }

            .checkout-flow .checkout-option-row:hover,
            .checkout-flow .checkout-option-row:focus-within {
                border-color: rgba(42, 98, 72, .38);
                background: #f8faf8;
            }

            .checkout-flow .checkout-option-row.is-selected {
                border-color: rgba(42, 98, 72, .55);
                background: #eef5f0;
                box-shadow: inset 3px 0 0 var(--bs-primary);
            }

            .checkout-flow .checkout-shipping-table .checkout-option-row {
                grid-template-columns: 2.85rem minmax(0, 1fr) auto;
                grid-template-rows: max-content;
                align-content: center;
                align-items: center;
            }

            .checkout-flow .checkout-shipping-table .checkout-option-row td:nth-child(1) {
                grid-column: 1;
                grid-row: 1;
                padding: .4rem .1rem .4rem .5rem;
            }

            .checkout-flow .checkout-shipping-table .checkout-option-row td:nth-child(2) {
                grid-column: 2;
                grid-row: 1;
                align-self: center;
                padding: .4rem .3rem;
                line-height: 1.25;
            }

            .checkout-flow .checkout-shipping-table .checkout-option-row td:nth-child(2) label {
                font-size: .9rem;
                line-height: 1.2;
            }

            .checkout-flow .checkout-shipping-table .checkout-option-row td:nth-child(2) .text-muted {
                display: block;
                overflow: hidden;
                font-size: .82rem;
                line-height: 1.25;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .checkout-flow .checkout-shipping-table .checkout-option-row td:nth-child(3) {
                display: none;
            }

            .checkout-flow .checkout-shipping-table .checkout-option-row td:nth-child(4) {
                grid-column: 3;
                grid-row: 1;
                padding: .4rem .6rem .4rem .2rem;
                font-size: .84rem;
                text-align: right;
            }

            .checkout-flow .checkout-option-mobile-time {
                display: block;
                margin-top: .08rem;
                color: #536078;
                font-size: .72rem;
                line-height: 1.15;
            }

            .checkout-flow .checkout-payment-table .checkout-option-row {
                grid-template-columns: 3.25rem minmax(0, 1fr);
                align-items: center;
            }

            .checkout-flow .checkout-payment-table .checkout-option-row td:nth-child(1) {
                padding: .75rem .25rem .75rem .65rem;
            }

            .checkout-flow .checkout-payment-table .checkout-option-row td:nth-child(2) {
                padding: .75rem .8rem .75rem .45rem !important;
            }

            .checkout-flow .checkout-option-icon-cell .checkout-method-icon {
                width: 1.8rem;
                height: 1.8rem;
                font-size: .66rem;
            }

            .checkout-save-toast {
                top: auto;
                bottom: calc(4.75rem + env(safe-area-inset-bottom));
                width: max-content;
            }
        }
    </style>
@endpush

@section('content')

    <div class="page-title-overlap bg-accent pt-4" style="background-image: url({{ asset('media/img/farmer.png')  }});background-repeat: repeat">
        <div class="container d-lg-flex justify-content-between py-2 py-lg-3">
            <div class="order-lg-2 mb-3 mb-lg-0 pt-lg-2">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-dark flex-lg-nowrap justify-content-center justify-content-lg-start">
                        <li class="breadcrumb-item"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}"><i class="fa-solid fa-house"></i>{{ __('front.nav.home') }}</a></li>
                        <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ __('front.checkout.checkout') }}</li>
                    </ol>
                </nav>
            </div>
            <div class="order-lg-1 pe-lg-4 text-center text-lg-start">
                <h1 class="h3 checkout-page-heading text-dark mb-0">{{ __('front.checkout.checkout') }}</h1>
            </div>
        </div>
    </div>

    <div class="container checkout-page pb-5 mb-2 mb-md-4">
        <div class="row">
            <section class="col-lg-8">
                @livewire('front.checkout', ['step' => $step, 'is_free_shipping' => $is_free_shipping])
            </section>
            <!-- Sidebar-->
            <aside class="col-lg-4 pt-4 pt-lg-0 ps-xl-5 d-none d-lg-block">
                <div class="checkout-sidebar">
                <cart-view-aside route="naplata" continueurl="{{ \Illuminate\Support\Facades\URL::previous() }}" checkouturl="{{ \App\Helpers\LocaleHelper::route('naplata') }}"></cart-view-aside>
                </div>
            </aside>
        </div>
    </div>

    <div class="checkout-save-toast" id="checkout-save-toast" role="status" aria-live="polite" aria-atomic="true">
        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
        <span data-checkout-toast-message></span>
    </div>

@endsection

@push('js_after')
    @livewireScripts
    @include('front.checkout.partials.progress-script')
    <script>
        (function () {
            var hideToastTimer;

            function initCheckoutSaveToast() {
                var toast = document.getElementById('checkout-save-toast');
                var message = toast ? toast.querySelector('[data-checkout-toast-message]') : null;

                if (!toast || !message) {
                    return;
                }

                window.addEventListener('checkout-option-saved', function (event) {
                    message.textContent = event.detail && event.detail.message ? event.detail.message : '';
                    toast.classList.add('is-visible');

                    window.clearTimeout(hideToastTimer);
                    hideToastTimer = window.setTimeout(function () {
                        toast.classList.remove('is-visible');
                    }, 1800);
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initCheckoutSaveToast, { once: true });
            } else {
                initCheckoutSaveToast();
            }
        }());
    </script>
@endpush
