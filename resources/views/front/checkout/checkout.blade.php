@extends('front.layouts.app')

@push('css_after')
    @livewireStyles
    @include('front.checkout.partials.progress-styles')
    <style>
        .checkout-flow .checkout-panel {
            color: #536058;
            font-size: 1rem;
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
            border-color: #dfe5e1;
            border-radius: .42rem;
            font-size: 1rem;
        }

        .checkout-flow textarea.form-control {
            min-height: 6rem;
        }

        .checkout-flow .form-control:focus,
        .checkout-flow .form-select:focus {
            border-color: rgba(191, 167, 106, .75);
            box-shadow: 0 0 0 .18rem rgba(191, 167, 106, .12);
        }

        .checkout-flow .checkout-option-card {
            border: 1px solid rgba(42, 98, 72, .14);
            background: #f8faf8;
        }

        .checkout-flow .checkout-toggle-card {
            padding: .85rem 1rem !important;
            border-radius: .55rem !important;
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
            border: 1px solid #dfe5e1;
            border-radius: .42rem;
            background: #fff;
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
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

        .checkout-flow .checkout-option-row td:first-child .form-check-input {
            display: block;
            margin: 0 auto !important;
        }

        .checkout-flow .checkout-option-row td:last-child {
            border-right: 1px solid #e8ece9;
            border-radius: 0 .6rem .6rem 0;
        }

        .checkout-flow .checkout-option-row:hover td,
        .checkout-flow .checkout-option-row.is-selected td {
            border-color: rgba(42, 98, 72, .45);
            background: rgba(42, 98, 72, .045);
        }

        .checkout-flow .checkout-option-row.is-selected td:first-child {
            box-shadow: inset 3px 0 0 var(--bs-primary);
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

            .checkout-flow .checkout-options-table {
                min-width: 620px;
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

@endsection

@push('js_after')
    @livewireScripts
@endpush
