@extends('back.layouts.backend')
@push('css_before')

    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('js/plugins/select2/css/select2.min.css') }}">


@endpush

@section('content')

    <div class="admin-page-hero">
        <div class="content content-full">
            <div class="admin-page-heading">
                <div>
                    <div class="admin-page-kicker"><i class="fa-duotone fa-cart-shopping"></i> Prodaja</div>
                    <h1 class="admin-page-title">Narudžbe</h1>
                    <p class="admin-page-description">Pronađite narudžbu, provjerite kupca i plaćanje te upravljajte obradom i dostavom.</p>
                </div>
            </div>
        </div>
    </div>


    <!-- Page Content -->
    <div class="content">
    @include('back.layouts.partials.session')
    <!-- All Orders -->
        <div class="block block-rounded">
            <div class="block-header block-header-default admin-orders-header">
                <div class="d-flex align-items-center min-width-0">
                    <span class="admin-section-icon mr-3"><i class="fa-duotone fa-receipt"></i></span>
                    <div>
                        <h2 class="block-title mb-1">Lista narudžbi</h2>
                        <span class="admin-count">{{ number_format($orders->total(), 0, ',', '.') }} narudžbi</span>
                    </div>
                </div>
                <div class="block-options d-none d-xl-block">
                    <div class="form-group mb-0 mr-2 admin-bulk-status">
                        <label class="sr-only" for="status-select">Promijeni status označenih narudžbi</label>
                        <select class="js-select2 form-control" id="status-select" name="status" style="width: 100%;" data-placeholder="Promijeni status narudžbe">
                            <option></option><!-- Required for data-placeholder attribute to work with Select2 plugin -->
                            @foreach ($statuses as $status)
                                <option value="{{ $status->id }}">{{ $status->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="block-options">
                    @php
                        $activeStatus = request()->filled('status')
                            ? $statuses->firstWhere('id', (int) request('status'))
                            : null;
                    @endphp
                    <div class="dropdown">
                        <button type="button" class="btn btn-light admin-status-trigger" id="dropdown-ecom-filters" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span>
                                <i class="fa-duotone fa-filter-list" aria-hidden="true"></i>
                                <span class="admin-status-trigger-copy">
                                    <small>Status</small>
                                    <strong>{{ $activeStatus ? $activeStatus->title : 'Svi statusi' }}</strong>
                                </span>
                            </span>
                            <i class="fa fa-angle-down admin-status-chevron" aria-hidden="true"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right admin-status-menu" aria-labelledby="dropdown-ecom-filters">
                            <div class="admin-status-menu-heading">
                                <span>Prikaži narudžbe</span>
                                @if(request()->filled('status'))
                                    <a href="javascript:setURL('status', 0)">Očisti</a>
                                @endif
                            </div>
                            <div class="admin-status-grid">
                                <a class="dropdown-item admin-status-menu-item admin-status-menu-all {{ ! request()->filled('status') ? 'is-active' : '' }}" href="javascript:setURL('status', 0)">
                                    <span><i class="fa-duotone fa-list" aria-hidden="true"></i>Sve narudžbe</span>
                                    @if(! request()->filled('status'))<i class="fa-duotone fa-check admin-status-check" aria-hidden="true"></i>@endif
                                </a>
                                @foreach ($statuses as $status)
                                    <a class="dropdown-item admin-status-menu-item {{ (string) request('status') === (string) $status->id ? 'is-active' : '' }}" href="javascript:setURL('status', {{ $status->id }})">
                                        <span class="badge badge-pill badge-{{ $status->color }}">{{ $status->title }}</span>
                                        @if((string) request('status') === (string) $status->id)<i class="fa-duotone fa-check admin-status-check" aria-hidden="true"></i>@endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="block-content bg-body-dark admin-order-filters">
                <!-- Search Form -->
                <form action="{{ route('orders') }}" method="GET" class="row">
                    @if(request()->filled('dashboard_group'))
                        <input type="hidden" name="dashboard_group" value="{{ request('dashboard_group') }}">
                    @endif
                    <div class="col-md-6 mb-2">
                        <label class="admin-filter-label" for="date-from-input">Od datuma</label>
                        <div class="input-group date" id="dateFromPicker">
                            <input type="text" class="form-control datepicker"
                                   id="date-from-input"
                                   name="date_from"
                                   value="{{ request('date_from') }}"
                                   placeholder="Datum od">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fa-duotone fa-calendar-days" aria-hidden="true"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="admin-filter-label" for="date-to-input">Do datuma</label>
                        <div class="input-group date" id="dateToPicker">
                            <input type="text" class="form-control datepicker"
                                   id="date-to-input"
                                   name="date_to"
                                   value="{{ request('date_to') }}"
                                   placeholder="Datum do">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fa-duotone fa-calendar-days" aria-hidden="true"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8 mb-2 admin-order-search-field">
                        <div class="input-group">
                            <input type="search" class="form-control"
                                   name="search"
                                   id="search-input"
                                   value="{{ request('search') }}"
                                   placeholder="Broj, kupac, e-mail ili artikl">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary fs-base">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-2 d-flex">
                        <button type="submit" class="btn btn-primary flex-fill mr-2">
                            <i class="fa-duotone fa-filter mr-1"></i> Primijeni
                        </button>
                        <a href="{{ route('orders') }}" class="btn btn-secondary flex-fill mr-2">
                            <i class="fa-regular fa-xmark mr-1"></i> Očisti
                        </a>


                            <a href="{{ route('orders.export', request()->query()) }}"
                               class="btn btn-outline-success">
                                <i class="fa-duotone fa-file-excel mr-1"></i> Izvezi
                            </a>



                    </div>
                </form>
            </div>


            <div class="block-content">
                <!-- All Orders Table -->
                <div class="table-responsive">
                    <table class="table table-borderless table-striped table-vcenter admin-orders-table">
                        <thead>
                        <tr>
                            <th class="text-center">
                                <div class="form-group">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="" id="checkAll" name="status">
                                    </div>
                                </div>
                            </th>
                            <th>Narudžba</th>
                            <th>Status i plaćanje</th>
                            <th>Kupac</th>
                            <th>Sažetak</th>
                            <th class="text-right">Radnje</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($orders as $order)
                            @php
                                $abandonedCartState = $order->abandoned_cart_state ?? [];
                                $isUnfinishedOrder = (int) $order->order_status_id === (int) config('settings.order.status.unfinished', 8);
                            @endphp
                            <tr>
                                <td class="text-center" data-label="Odaberi">
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="{{ $order->id }}" id="status[{{ $order->id }}]" name="status">
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Narudžba" class="admin-order-number">
                                    <a class="font-w600" href="{{ route('orders.show', ['order' => $order]) }}">
                                        <strong>#{{ $order->id }}</strong>
                                    </a>
                                    <small><i class="fa-duotone fa-calendar-days mr-1" aria-hidden="true"></i>{{ \Illuminate\Support\Carbon::make($order->created_at)->format('d.m.Y.') }}</small>
                                </td>
                                <td data-label="Status i plaćanje" class="admin-order-status-payment">
                                    <div class="admin-order-status-stack">
                                        <span class="badge badge-pill badge-{{ $order->status->color }}">{{ $order->status->title }}</span>
                                        <small>{{ $order->payment_method }}</small>
                                        @if(! empty($abandonedCartState['first_sent_at']))
                                            <small class="admin-reminder-sent">
                                                <i class="fa-duotone fa-envelope-circle-check mr-1" aria-hidden="true"></i>
                                                1. podsjetnik: {{ $abandonedCartState['first_sent_at']->format('d.m.Y. H:i') }}
                                            </small>
                                        @endif
                                        @if(! empty($abandonedCartState['second_sent_at']))
                                            <small class="admin-reminder-sent">
                                                <i class="fa-duotone fa-envelope-circle-check mr-1" aria-hidden="true"></i>
                                                2. podsjetnik: {{ $abandonedCartState['second_sent_at']->format('d.m.Y. H:i') }}
                                            </small>
                                        @endif
                                        @if($isUnfinishedOrder && ! empty($abandonedCartState['available']) && ! empty($abandonedCartState['next_scheduled_at']))
                                            <small class="admin-reminder-scheduled">
                                                <i class="fa-duotone fa-clock mr-1" aria-hidden="true"></i>
                                                {{ $abandonedCartState['next_sequence'] }}. automatski: {{ $abandonedCartState['next_scheduled_at']->format('d.m.Y. H:i') }}
                                            </small>
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Kupac" class="admin-order-customer">
                                    <a class="font-w600" href="{{ route('orders.show', ['order' => $order]) }}">{{ $order->shipping_fname }} {{ $order->shipping_lname }}</a>
                                </td>
                                <td data-label="Sažetak" class="admin-order-summary">
                                    <div class="admin-order-summary-stack">
                                        <span><i class="fa-duotone fa-books mr-1" aria-hidden="true"></i>{{ $order->order_products_count }} {{ $order->order_products_count == 1 ? 'artikl' : 'artikala' }}</span>
                                        <strong>
                                            @if ($order->id > 4627)
                                                € {{ number_format($order->total, 2, ',', '.') }}
                                            @else
                                                {{ number_format($order->total, 2, ',', '.') }} kn
                                            @endif
                                        </strong>
                                        @if($order->shipping_tracking_status)
                                            <small class="text-muted">
                                                <i class="fa-duotone fa-truck-fast mr-1" aria-hidden="true"></i>
                                                {{ $order->shipping_tracking_status }}
                                            </small>
                                        @elseif($order->tracking_code)
                                            <small class="text-muted">
                                                {{ app(\App\Services\Shipping\OrderTrackingService::class)->carrierLabel($order->shipping_carrier) }} #{{ $order->tracking_code }}
                                            </small>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-right" data-label="Radnje">
                                    <span class="admin-row-actions">
                                    <a class="btn btn-sm btn-alt-secondary" href="{{ route('orders.show', ['order' => $order]) }}" title="Pregledaj" aria-label="Pregledaj narudžbu {{ $order->id }}">
                                        <i class="fa-duotone fa-eye"></i>
                                    </a>
                                    <a class="btn btn-sm btn-alt-info" href="{{ route('orders.edit', ['order' => $order]) }}" title="Uredi" aria-label="Uredi narudžbu {{ $order->id }}">
                                        <i class="fa-duotone fa-pen-to-square"></i>
                                    </a>
                                    @if($isUnfinishedOrder && ! empty($abandonedCartState['available']))
                                        <form class="admin-reminder-form" method="POST" action="{{ route('orders.abandoned-cart-reminder.send', ['order' => $order]) }}" onsubmit="return confirm('Poslati {{ $abandonedCartState['next_sequence'] }}. podsjetnik kupcu sada?');">
                                            @csrf
                                            <button type="submit"
                                                    class="btn btn-sm btn-alt-primary"
                                                    title="Pošalji {{ $abandonedCartState['next_sequence'] }}. podsjetnik sada"
                                                    aria-label="Pošalji {{ $abandonedCartState['next_sequence'] }}. podsjetnik za narudžbu {{ $order->id }} sada">
                                                <i class="fa-duotone fa-envelope-open-text"></i>
                                            </button>
                                        </form>
                                    @elseif($isUnfinishedOrder && ! empty($abandonedCartState['complete']))
                                        <button type="button" class="btn btn-sm btn-light disabled" disabled title="Poslana su oba podsjetnika" aria-label="Poslana su oba podsjetnika za narudžbu {{ $order->id }}">
                                            <i class="fa-duotone fa-envelope-circle-check text-success"></i>
                                        </button>
                                    @endif
                                    @php($shipmentCarrierHint = \Illuminate\Support\Str::lower($order->shipping_carrier . ' ' . $order->shipping_method . ' ' . $order->shipping_code))
                                    @php($hasShipment = $order->printed || filled($order->shipping_parcel_id) || filled($order->tracking_code))
                                    @php($isWoltShipment = \Illuminate\Support\Str::contains($shipmentCarrierHint, ['wolt_drive', 'wolt drive', 'wolt']))
                                    @php($hasWoltDelivery = filled($order->shipping_parcel_id) || filled($order->tracking_code))
                                    @php($woltTerminal = in_array(\Illuminate\Support\Str::lower((string) $order->shipping_tracking_status_code), ['delivered', 'order.delivered', 'rejected', 'order.rejected', 'cancelled', 'canceled'], true))
                                    @if($isWoltShipment && $hasWoltDelivery)
                                        <button type="button" class="btn btn-light btn-sm disabled" disabled title="Pošiljka je kreirana"><i class="fa-duotone fa-check text-success"></i></button>
                                        @unless($woltTerminal)
                                            <button type="button" class="btn btn-alt-danger btn-sm" onclick="cancelWolt({{ $order->id }})" title="Otkaži Wolt Drive dostavu" aria-label="Otkaži Wolt Drive dostavu za narudžbu {{ $order->id }}"><i class="fa-duotone fa-ban"></i></button>
                                        @endunless
                                    @elseif($isWoltShipment)
                                        <button type="button" class="btn btn-alt-primary btn-sm" onclick="sendWolt({{ $order->id }})" title="Pošalji u Wolt Drive" aria-label="Pošalji narudžbu {{ $order->id }} u Wolt Drive"><i class="fa-duotone fa-motorcycle"></i></button>
                                    @elseif($hasShipment)
                                        <button type="button" class="btn btn-light btn-sm disabled" disabled title="Pošiljka je kreirana"><i class="fa-duotone fa-check text-success"></i></button>
                                    @elseif(\Illuminate\Support\Str::contains($shipmentCarrierHint, ['boxnow', 'box now']))
                                        <button type="button" class="btn btn-alt-warning btn-sm" onclick="sendBoxNow({{ $order->id }})" title="Pošalji u Box Now" aria-label="Pošalji narudžbu {{ $order->id }} u Box Now"><i class="fa-duotone fa-box"></i></button>
                                    @elseif(\Illuminate\Support\Str::contains($shipmentCarrierHint, 'gls'))
                                        <button type="button" class="btn btn-alt-warning btn-sm" onclick="sendGLS({{ $order->id }})" title="Pošalji u GLS" aria-label="Pošalji narudžbu {{ $order->id }} u GLS"><i class="fa-duotone fa-truck-fast"></i></button>
                                    @endif
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center font-size-sm" colspan="6">
                                    <label>Nema narudžbi...</label>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->

                        {{ $orders->links() }}

            </div>
        </div>
        <!-- END All Orders -->
    </div>

@endsection

@push('css_after')
    <style>
        .admin-bulk-status { min-width: 15rem; }
        .admin-orders-header { position: relative; z-index: 30; overflow: visible; }
        .admin-order-filters { position: relative; z-index: 1; }
        .admin-status-trigger { display: flex; min-width: 12.5rem; height: 2.25rem !important; min-height: 2.25rem !important; align-items: center; justify-content: space-between; gap: .7rem; padding: .25rem .65rem; text-align: left; }
        .admin-status-trigger > span { display: flex; min-width: 0; align-items: center; gap: .65rem; }
        .admin-status-trigger > span > i { color: #63776b; font-size: 1rem; }
        .admin-status-trigger-copy { display: flex; min-width: 0; align-items: baseline; gap: .38rem; line-height: 1; white-space: nowrap; }
        .admin-status-trigger-copy small { margin: 0; color: #6b776f; font-size: .68rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
        .admin-status-trigger-copy strong { overflow: hidden; color: var(--admin-ink); font-size: .88rem; text-overflow: ellipsis; white-space: nowrap; }
        .admin-status-chevron { color: #657168; transition: transform .16s ease; }
        .admin-status-trigger[aria-expanded="true"] .admin-status-chevron { transform: rotate(180deg); }
        .admin-status-menu { z-index: 1085; width: 23rem; max-width: calc(100vw - 2rem); padding: .65rem; border-color: #cbc5b9; box-shadow: 0 .45rem 1rem rgba(39, 49, 43, .1); }
        .admin-status-menu-heading { display: flex; align-items: center; justify-content: space-between; padding: .15rem .2rem .55rem; color: #68756d; font-size: .72rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
        .admin-status-menu-heading a { color: var(--admin-forest); font-size: .72rem; text-transform: none; }
        .admin-status-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .4rem; }
        .admin-status-menu-item { display: flex; min-width: 0; min-height: 2.75rem; align-items: center; justify-content: space-between; gap: .4rem; padding: .48rem .55rem; border: 1px solid #e0dbd1; border-radius: .25rem; background: #fff; }
        .admin-status-menu-item:hover, .admin-status-menu-item:focus { color: var(--admin-ink); border-color: #aab9b0; background: #f5f7f5; }
        .admin-status-menu-item.is-active { border-color: #81998b; background: #f0f4f1; }
        .admin-status-menu-item .badge { max-width: 100%; overflow: hidden; text-overflow: ellipsis; }
        .admin-status-menu-all { grid-column: 1 / -1; }
        .admin-status-menu-all > span { display: flex; align-items: center; gap: .55rem; color: var(--admin-ink); font-weight: 700; }
        .admin-status-menu-all > span i { color: #6c7a72; }
        .admin-status-check { flex: 0 0 auto; color: var(--admin-forest); }
        .admin-order-filters .input-group-text { border-color: #d4dad5; color: var(--admin-forest); background: #fff; }
        .admin-order-filters .form-control { padding-right: .85rem; padding-left: .85rem; }
        .admin-orders-table { width: 100%; min-width: 0; table-layout: fixed; }
        .admin-orders-table th:nth-child(1) { width: 5%; }
        .admin-orders-table th:nth-child(2) { width: 14%; }
        .admin-orders-table th:nth-child(3) { width: 24%; }
        .admin-orders-table th:nth-child(4) { width: 22%; }
        .admin-orders-table th:nth-child(5) { width: 16%; }
        .admin-orders-table th:nth-child(6) { width: 19%; }
        .admin-orders-table th, .admin-orders-table td { padding: .75rem .65rem; }
        .admin-order-number small,
        .admin-order-status-payment small { display: block; margin-top: .32rem; color: #657168; font-size: var(--admin-type-xs); line-height: 1.3; }
        .admin-order-status-payment small { overflow: hidden; text-overflow: ellipsis; }
        .admin-order-status-payment .admin-reminder-sent { color: #39714e; font-weight: 700; }
        .admin-order-status-payment .admin-reminder-scheduled { color: #8b621f; }
        .admin-order-status-stack { min-width: 0; }
        .admin-order-customer a { display: -webkit-box; overflow: hidden; line-height: 1.35; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
        .admin-order-summary { font-variant-numeric: tabular-nums; }
        .admin-order-summary-stack { min-width: 0; }
        .admin-order-summary span,
        .admin-order-summary strong { display: block; }
        .admin-order-summary span { margin-bottom: .22rem; color: #657168; font-size: var(--admin-type-xs); }
        .admin-order-summary strong { color: #26342d; font-size: var(--admin-type-body); }
        .admin-orders-table .admin-row-actions { display: inline-flex; gap: .35rem; }
        .admin-reminder-form { display: inline-flex; margin: 0; }
        .admin-orders-table .admin-row-actions .btn { width: 2.25rem; padding: 0; }
        @media (max-width: 767.98px) {
            .admin-orders-header { align-items: center !important; flex-direction: row !important; gap: .5rem !important; }
            .admin-orders-header .dropdown { position: static; }
            .admin-status-trigger { min-width: 0; width: 3.25rem; padding: 0; justify-content: center; }
            .admin-status-trigger-copy, .admin-status-trigger .admin-status-chevron { display: none; }
            .admin-status-menu.show { top: calc(100% - .25rem) !important; right: .75rem !important; left: .75rem !important; width: auto !important; min-width: 0; max-height: min(19rem, calc(100vh - 8rem)); overflow-y: auto; transform: none !important; overscroll-behavior: contain; }
            .admin-order-filters .row > [class*="col-"] { margin-bottom: .75rem !important; }
            .admin-order-filters .admin-order-search-field { margin-top: .15rem; }
            .admin-order-filters .col-md-4.d-flex { flex-wrap: wrap; gap: .5rem; }
            .admin-order-filters .col-md-4.d-flex > * { min-width: calc(50% - .25rem); margin: 0 !important; }
            .admin-orders-table { min-width: 0; }
            .admin-orders-table, .admin-orders-table tbody, .admin-orders-table tr, .admin-orders-table td { display: block; width: 100%; }
            .admin-orders-table thead { display: none; }
            .admin-orders-table tr { display: grid; grid-template-columns: auto minmax(0, 1fr); gap: .48rem .7rem; padding: .8rem; border-bottom: 1px solid var(--admin-line); }
            .admin-orders-table td { display: grid; min-width: 0 !important; padding: 0 !important; border: 0 !important; grid-template-columns: 7.5rem minmax(0, 1fr); gap: .55rem; align-items: start; background: transparent !important; text-align: left !important; }
            .admin-orders-table td::before { color: #657269; content: attr(data-label); font-size: var(--admin-type-xs); font-weight: 800; letter-spacing: .04em; line-height: 1.35; text-transform: uppercase; }
            .admin-orders-table td:first-child { grid-column: 1; }
            .admin-orders-table td:first-child::before { display: none; }
            .admin-orders-table td:nth-child(2) { display: block; grid-column: 2; font-size: .92rem; }
            .admin-orders-table td:nth-child(2)::before { display: none; }
            .admin-orders-table td:nth-child(3), .admin-orders-table td:nth-child(4) { grid-column: 1 / -1; }
            .admin-orders-table td:nth-child(5) { grid-column: 1 / -1; }
            .admin-orders-table td:last-child { display: block; grid-column: 1 / -1; padding-top: .45rem !important; border-top: 1px solid #e6e2da !important; }
            .admin-orders-table td:last-child::before { display: none; }
            .admin-orders-table .admin-order-status-stack,
            .admin-orders-table .admin-order-summary-stack { min-width: 0; }
            .admin-orders-table .admin-row-actions { width: 100%; max-width: 100%; flex-wrap: wrap; justify-content: flex-end; }
        }
    </style>
@endpush

@push('js_after')
    <script src="{{ \App\Helpers\Asset::url('js/plugins/select2/js/select2.full.min.js') }}"></script>

    <script src="{{ \App\Helpers\Asset::url('js/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js') }}"></script>
    <script src="{{ \App\Helpers\Asset::url('js/plugins/bootstrap-datepicker/locales/bootstrap-datepicker.hr.min.js') }}"></script>
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('js/plugins/bootstrap-datepicker/css/bootstrap-datepicker.css') }}">
    <script>
        $('.datepicker').datepicker({
            format: 'dd.mm.yyyy',
            autoclose: true,
            todayHighlight: true,
            language: 'hr',
            weekStart: 1,
            orientation: "bottom"
        });
    </script>
    <script>
        $(() => {
            $('#status-select').select2({
                placeholder: 'Promijeni status'
            });

            $('#status-select').on('change', (e) => {
                let selected = e.currentTarget.selectedOptions[0].value;
                let orders = '[';
                var checkedBoxes = document.querySelectorAll('input[name=status]:checked');

                for (let i = 0; i < checkedBoxes.length; i++) {
                    if (checkedBoxes.length - 1 == i) {
                        orders += checkedBoxes[i].value + ']';
                    } else {
                        orders += checkedBoxes[i].value + ','
                    }
                }

                console.log('Selected ID: ' + selected);
                console.log('Orders ID: ' + orders);

                axios.post('{{ route('api.order.status.change') }}', {
                    selected: selected,
                    orders: orders
                })
                .then((r) => {
                    location.reload();
                })
                .catch((e) => {
                    console.log(e)
                })
            });
        });

        /**
         *
         * @param type
         * @param search
         */
        function setURL(type, search) {
            let url = new URL(location.href);
            let params = new URLSearchParams(url.search);
            let keys = [];

            for(var key of params.keys()) {
                if (key === type) {
                    keys.push(key);
                }
            }

            keys.forEach((value) => {
                if (params.has(value) || search == 0) {
                    params.delete(value);
                }
            })

            if (search) {
                params.append(type, search);
            }

            url.search = params;
            location.href = url;
        }

        /**
         *
         * @param order_id
         */
        function sendShipment(order_id, endpoint) {
            axios.post(endpoint, {order_id: order_id})
            .then(response => {
                if (response.data.message) {
                    successToast.fire({
                        timer: 1500,
                        text: response.data.message,
                    }).then(() => {
                        location.reload();
                    })

                } else {
                    return errorToast.fire(response.data.error);
                }
            }).catch(error => {
                return errorToast.fire(error.response && error.response.data ? error.response.data.error : 'Slanje pošiljke nije uspjelo.');
            });
        }

        function sendGLS(order_id) {
            sendShipment(order_id, "{{ route('api.order.send.gls') }}");
        }

        function sendBoxNow(order_id) {
            sendShipment(order_id, "{{ route('api.order.send.boxnow') }}");
        }

        function sendWolt(order_id) {
            sendShipment(order_id, "{{ route('api.order.send.wolt') }}");
        }

        function cancelWolt(order_id) {
            const reason = window.prompt('Razlog otkazivanja Wolt Drive dostave:', 'Otkazano iz Biblos administracije.');

            if (!reason || reason.trim().length < 3) {
                return;
            }

            axios.post("{{ route('api.order.cancel.wolt') }}", {
                order_id: order_id,
                reason: reason.trim(),
            }).then(response => {
                successToast.fire({ timer: 1500, text: response.data.message })
                    .then(() => location.reload());
            }).catch(error => {
                return errorToast.fire(error.response && error.response.data
                    ? (error.response.data.error || error.response.data.message)
                    : 'Otkazivanje Wolt Drive dostave nije uspjelo.');
            });
        }
    </script>
    <script>
        $("#checkAll").click(function () {
            $('input:checkbox').not(this).prop('checked', this.checked);
        });
    </script>

@endpush
