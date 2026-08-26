@extends('back.layouts.backend')

@section('content')
    <div class="bg-body-light dashboard-hero">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <div>
                    <span class="dashboard-title-kicker"><i class="fa-duotone fa-books" aria-hidden="true"></i> Antikvarijat Biblos</span>
                    <h1 class="font-size-h2 font-w600 mb-1">Nadzorna ploča</h1>
                    <p class="text-muted mb-0">Pregled prodaje, narudžbi i zadnjih aktivnosti</p>
                </div>
                @if($canViewSales)
                    <a class="btn btn-primary dashboard-statistics-button mt-3 mt-sm-0" href="{{ url('/admin/statistike') }}">
                        <i class="fa-duotone fa-chart-mixed" aria-hidden="true"></i>
                        <span>Detaljne statistike</span>
                        <i class="fa-duotone fa-arrow-right" aria-hidden="true"></i>
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="content dashboard-content">
        @include('back.layouts.partials.session')

        @if($canViewSales)
        <div class="dashboard-section-heading">
            <div>
                <span class="dashboard-section-eyebrow">Brzi pregled</span>
                <h2>Prodaja po razdobljima</h2>
            </div>
            <span class="dashboard-section-note">Klik na karticu otvara pripadajuće narudžbe</span>
        </div>

        <div class="dashboard-kpi-grid">
            <div class="col-12 col-md-4 dashboard-kpi-col">
                <a class="block block-rounded block-link-shadow dashboard-kpi-card dashboard-kpi-card-info"
                   href="{{ route('orders', [
                       'dashboard_group' => 'sales',
                       'date_from' => now()->format('d.m.Y'),
                       'date_to' => now()->format('d.m.Y'),
                   ]) }}"
                   aria-label="Otvori današnje narudžbe">
                    <div class="block-content dashboard-kpi-content">
                        <div class="dashboard-kpi-head">
                            <span class="dashboard-kpi-icon" aria-hidden="true"><i class="fa-duotone fa-calendar-day"></i></span>
                            <span class="dashboard-kpi-period">Danas</span>
                            <i class="fa-duotone fa-chevron-right dashboard-kpi-arrow" aria-hidden="true"></i>
                        </div>
                        <span class="dashboard-kpi-label">Promet</span>
                        <div class="dashboard-kpi-value">{{ \App\Helpers\Currency::main($data['today_total'], true) ?: '0,00 €' }}</div>
                        <div class="dashboard-kpi-meta">
                            <div>
                                <span>Narudžbe</span>
                                <strong>{{ number_format($data['today'], 0, ',', '.') }}</strong>
                            </div>
                            <div>
                                <span>Artikala / nar.</span>
                                <strong>{{ number_format($data['today_items_average'], 2, ',', '.') }}</strong>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-md-4 dashboard-kpi-col">
                <a class="block block-rounded block-link-shadow dashboard-kpi-card dashboard-kpi-card-primary"
                   href="{{ route('orders', [
                       'dashboard_group' => 'sales',
                       'date_from' => now()->startOfMonth()->format('d.m.Y'),
                       'date_to' => now()->endOfMonth()->format('d.m.Y'),
                   ]) }}"
                   aria-label="Otvori narudžbe iz tekućeg mjeseca">
                    <div class="block-content dashboard-kpi-content">
                        <div class="dashboard-kpi-head">
                            <span class="dashboard-kpi-icon" aria-hidden="true"><i class="fa-duotone fa-chart-line"></i></span>
                            <span class="dashboard-kpi-period">Ovaj mjesec</span>
                            <i class="fa-duotone fa-chevron-right dashboard-kpi-arrow" aria-hidden="true"></i>
                        </div>
                        <span class="dashboard-kpi-label">Promet</span>
                        <div class="dashboard-kpi-value">{{ \App\Helpers\Currency::main($data['this_month_total'], true) ?: '0,00 €' }}</div>
                        <div class="dashboard-kpi-meta">
                            <div>
                                <span>Narudžbe</span>
                                <strong>{{ number_format($data['this_month'], 0, ',', '.') }}</strong>
                            </div>
                            <div>
                                <span>Artikala / nar.</span>
                                <strong>{{ number_format($data['this_month_items_average'], 2, ',', '.') }}</strong>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-md-4 dashboard-kpi-col">
                <a class="block block-rounded block-link-shadow dashboard-kpi-card dashboard-kpi-card-success"
                   href="{{ route('orders', [
                       'dashboard_group' => 'sales',
                       'date_from' => now()->startOfYear()->format('d.m.Y'),
                       'date_to' => now()->endOfYear()->format('d.m.Y'),
                   ]) }}" aria-label="Otvori završene narudžbe ove godine">
                    <div class="block-content dashboard-kpi-content">
                        <div class="dashboard-kpi-head">
                            <span class="dashboard-kpi-icon" aria-hidden="true"><i class="fa-duotone fa-badge-check"></i></span>
                            <span class="dashboard-kpi-period">Ova godina</span>
                            <i class="fa-duotone fa-chevron-right dashboard-kpi-arrow" aria-hidden="true"></i>
                        </div>
                        <span class="dashboard-kpi-label">Promet</span>
                        <div class="dashboard-kpi-value">{{ \App\Helpers\Currency::main($data['finished_total'], true) ?: '0,00 €' }}</div>
                        <div class="dashboard-kpi-meta">
                            <div>
                                <span>Narudžbe</span>
                                <strong>{{ number_format($data['finished'], 0, ',', '.') }}</strong>
                            </div>
                            <div>
                                <span>Artikala / nar.</span>
                                <strong>{{ number_format($data['finished_items_average'], 2, ',', '.') }}</strong>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="block block-rounded dashboard-sales-block" id="dashboard-sales">
            <div class="block-header block-header-default dashboard-sales-header">
                <div>
                    <h2 class="block-title font-size-h4 mb-1">Statistika prometa</h2>
                    <p class="dashboard-sales-subtitle mb-0">Iznosi i narudžbe prema datumu nastanka</p>
                </div>
                <div class="dashboard-sales-filters">
                    <div class="dashboard-filter">
                        <label for="chart-year">Godina</label>
                        <select id="chart-year" class="form-control" aria-controls="salesTabsContent">
                            @foreach($yearsWithOrders as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="dashboard-filter">
                        <label for="chart-month">Mjesec</label>
                        <select id="chart-month" class="form-control" aria-controls="tab-sales">
                            @foreach([
                                1 => 'Siječanj',
                                2 => 'Veljača',
                                3 => 'Ožujak',
                                4 => 'Travanj',
                                5 => 'Svibanj',
                                6 => 'Lipanj',
                                7 => 'Srpanj',
                                8 => 'Kolovoz',
                                9 => 'Rujan',
                                10 => 'Listopad',
                                11 => 'Studeni',
                                12 => 'Prosinac',
                            ] as $month => $monthName)
                                <option value="{{ $month }}">{{ $monthName }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="block-content dashboard-sales-content">
                <div id="dashboard-sales-error" class="alert alert-danger d-none" role="alert">
                    <i class="fa fa-exclamation-circle mr-2" aria-hidden="true"></i>
                    Statistiku trenutno nije moguće učitati. Pokušajte ponovno.
                </div>

                <ul class="nav nav-tabs dashboard-sales-tabs" id="salesTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="sales-tab" data-toggle="tab" href="#tab-sales" role="tab"
                           aria-controls="tab-sales" aria-selected="true">
                            <i class="fa-duotone fa-calendar-day mr-2" aria-hidden="true"></i>Mjesečni pregled
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="yearly-tab" data-toggle="tab" href="#tab-yearly" role="tab"
                           aria-controls="tab-yearly" aria-selected="false">
                            <i class="fa-duotone fa-calendar-range mr-2" aria-hidden="true"></i>Godišnji pregled
                        </a>
                    </li>
                </ul>

                <div class="tab-content" id="salesTabsContent">
                    <div class="tab-pane fade show active" id="tab-sales" role="tabpanel" aria-labelledby="sales-tab">
                        <div class="dashboard-summary-grid">
                            <div class="dashboard-summary-card">
                                <span class="dashboard-summary-label">Mjesečni promet</span>
                                <strong id="kpi-month-total" aria-live="polite">—</strong>
                                <small>Novo, Plaćeno i Poslano</small>
                            </div>
                            <div class="dashboard-summary-card">
                                <span class="dashboard-summary-label">Narudžbe</span>
                                <strong id="kpi-month-orders" aria-live="polite">—</strong>
                                <small>Završene narudžbe</small>
                            </div>
                            <div class="dashboard-summary-card">
                                <span class="dashboard-summary-label">Prodani artikli</span>
                                <strong id="kpi-month-items" aria-live="polite">—</strong>
                                <small id="kpi-month-items-avg">— po narudžbi</small>
                            </div>
                            <div class="dashboard-summary-card">
                                <span class="dashboard-summary-label">Prosječna narudžba</span>
                                <strong id="kpi-month-aov" aria-live="polite">—</strong>
                                <small>Po završenoj narudžbi</small>
                            </div>
                        </div>

                        <div class="row dashboard-breakdowns">
                            <div class="col-12 col-lg-4">
                                <section class="dashboard-breakdown-card" aria-labelledby="monthly-payment-title">
                                    <h3 id="monthly-payment-title"><i class="fa-duotone fa-credit-card" aria-hidden="true"></i>Način plaćanja <span id="monthly-payment-methods-count" class="dashboard-breakdown-count" aria-live="polite">—</span></h3>
                                    <div id="monthly-payment-methods" class="dashboard-breakdown-list">
                                        <div class="dashboard-breakdown-empty">Učitavanje…</div>
                                    </div>
                                </section>
                            </div>
                            <div class="col-12 col-lg-4">
                                <section class="dashboard-breakdown-card" aria-labelledby="monthly-shipping-title">
                                    <h3 id="monthly-shipping-title"><i class="fa-duotone fa-truck" aria-hidden="true"></i>Način dostave <span id="monthly-shipping-methods-count" class="dashboard-breakdown-count" aria-live="polite">—</span></h3>
                                    <div id="monthly-shipping-methods" class="dashboard-breakdown-list">
                                        <div class="dashboard-breakdown-empty">Učitavanje…</div>
                                    </div>
                                </section>
                            </div>
                            <div class="col-12 col-lg-4">
                                <section class="dashboard-breakdown-card" aria-labelledby="monthly-status-title">
                                    <h3 id="monthly-status-title"><i class="fa-duotone fa-list-check" aria-hidden="true"></i>Statusi narudžbi <span id="monthly-statuses-count" class="dashboard-breakdown-count" aria-live="polite">—</span></h3>
                                    <div id="monthly-statuses" class="dashboard-breakdown-list">
                                        <div class="dashboard-breakdown-empty">Učitavanje…</div>
                                    </div>
                                </section>
                            </div>
                        </div>

                        <div class="dashboard-chart-wrap">
                            <canvas id="salesChart" aria-label="Graf prometa i broja narudžbi po danima" role="img"></canvas>
                            <div id="month-chart-empty" class="dashboard-chart-empty d-none">Nema podataka za odabrani mjesec.</div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-yearly" role="tabpanel" aria-labelledby="yearly-tab">
                        <div class="dashboard-summary-grid">
                            <div class="dashboard-summary-card">
                                <span class="dashboard-summary-label">Godišnji promet</span>
                                <strong id="kpi-year-total" aria-live="polite">—</strong>
                                <small>Novo, Plaćeno i Poslano</small>
                            </div>
                            <div class="dashboard-summary-card">
                                <span class="dashboard-summary-label">Narudžbe</span>
                                <strong id="kpi-year-orders" aria-live="polite">—</strong>
                                <small>Završene narudžbe</small>
                            </div>
                            <div class="dashboard-summary-card">
                                <span class="dashboard-summary-label">Prodani artikli</span>
                                <strong id="kpi-year-items" aria-live="polite">—</strong>
                                <small id="kpi-year-items-avg">— po narudžbi</small>
                            </div>
                            <div class="dashboard-summary-card">
                                <span class="dashboard-summary-label">Prosječna narudžba</span>
                                <strong id="kpi-year-aov" aria-live="polite">—</strong>
                                <small>Po završenoj narudžbi</small>
                            </div>
                        </div>

                        <div class="row dashboard-breakdowns">
                            <div class="col-12 col-lg-4">
                                <section class="dashboard-breakdown-card" aria-labelledby="yearly-payment-title">
                                    <h3 id="yearly-payment-title"><i class="fa-duotone fa-credit-card" aria-hidden="true"></i>Način plaćanja <span id="yearly-payment-methods-count" class="dashboard-breakdown-count" aria-live="polite">—</span></h3>
                                    <div id="yearly-payment-methods" class="dashboard-breakdown-list">
                                        <div class="dashboard-breakdown-empty">Učitavanje…</div>
                                    </div>
                                </section>
                            </div>
                            <div class="col-12 col-lg-4">
                                <section class="dashboard-breakdown-card" aria-labelledby="yearly-shipping-title">
                                    <h3 id="yearly-shipping-title"><i class="fa-duotone fa-truck" aria-hidden="true"></i>Način dostave <span id="yearly-shipping-methods-count" class="dashboard-breakdown-count" aria-live="polite">—</span></h3>
                                    <div id="yearly-shipping-methods" class="dashboard-breakdown-list">
                                        <div class="dashboard-breakdown-empty">Učitavanje…</div>
                                    </div>
                                </section>
                            </div>
                            <div class="col-12 col-lg-4">
                                <section class="dashboard-breakdown-card" aria-labelledby="yearly-status-title">
                                    <h3 id="yearly-status-title"><i class="fa-duotone fa-list-check" aria-hidden="true"></i>Statusi narudžbi <span id="yearly-statuses-count" class="dashboard-breakdown-count" aria-live="polite">—</span></h3>
                                    <div id="yearly-statuses" class="dashboard-breakdown-list">
                                        <div class="dashboard-breakdown-empty">Učitavanje…</div>
                                    </div>
                                </section>
                            </div>
                        </div>

                        <div class="dashboard-chart-wrap">
                            <canvas id="yearChart" aria-label="Usporedni graf godišnjeg prometa po mjesecima" role="img"></canvas>
                            <div id="year-chart-empty" class="dashboard-chart-empty d-none">Nema podataka za odabranu godinu.</div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-loading" aria-hidden="true">
                    <i class="fa fa-circle-notch fa-spin" aria-hidden="true"></i>
                    <span>Učitavanje statistike…</span>
                </div>
            </div>
        </div>
        @endif

        <div class="row dashboard-list-row">
            <div class="col-xl-6">
                <div class="block block-rounded dashboard-list-block">
                    <div class="block-header block-header-default">
                        <h2 class="block-title">Zadnje narudžbe</h2>
                        <a class="btn btn-sm btn-light" href="{{ route('orders') }}">
                            Sve narudžbe <i class="fa fa-arrow-right ml-1" aria-hidden="true"></i>
                        </a>
                    </div>
                    <div class="block-content">
                        @if($orders->isEmpty())
                            <div class="dashboard-list-empty">
                                <i class="fa-duotone fa-receipt" aria-hidden="true"></i>
                                <span>Još nema narudžbi za prikaz.</span>
                            </div>
                        @else
                            <table class="table table-borderless table-vcenter dashboard-list-table dashboard-orders-table">
                                <thead class="sr-only">
                                    <tr>
                                        <th>Broj</th>
                                        <th>Kupac, datum i broj artikala</th>
                                        <th>Iznos i status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($orders as $order)
                                    @php($status = $order->status)
                                    @php($itemCount = (int) $order->order_products_count)
                                    @php($itemCountLastTwoDigits = $itemCount % 100)
                                    @php($itemCountLastDigit = $itemCount % 10)
                                    @php($usesSingularItemLabel = $itemCountLastDigit === 1 && $itemCountLastTwoDigits !== 11)
                                    @php($usesFewItemsLabel = $itemCountLastDigit >= 2 && $itemCountLastDigit <= 4 && ($itemCountLastTwoDigits < 12 || $itemCountLastTwoDigits > 14))
                                    @php($itemLabel = $usesSingularItemLabel ? 'artikl' : ($usesFewItemsLabel ? 'artikla' : 'artikala'))
                                    <tr>
                                        <td class="dashboard-list-id">
                                            <a href="{{ route('orders.show', ['order' => $order]) }}">#{{ $order->id }}</a>
                                        </td>
                                        @php($customerName = trim($order->payment_fname . ' ' . $order->payment_lname) ?: 'Nepoznati kupac')
                                        <td class="dashboard-list-main">
                                            <a href="{{ route('orders.show', ['order' => $order]) }}" title="{{ $customerName }}">
                                                {{ $customerName }}
                                            </a>
                                            <small>
                                                {{ optional($order->created_at)->format('d.m.Y. H:i') }}
                                                <span aria-hidden="true">·</span>
                                                {{ $itemCount }} {{ $itemLabel }}
                                            </small>
                                        </td>
                                        <td class="dashboard-list-price dashboard-order-summary">
                                            <strong>{{ \App\Helpers\Currency::main($order->total, true) }}</strong>
                                            <span class="badge badge-pill badge-{{ $status->color ?? 'secondary' }}">
                                                {{ $status->title ?? ('Status #' . $order->order_status_id) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="block block-rounded dashboard-list-block">
                    <div class="block-header block-header-default">
                        <h2 class="block-title">Zadnje prodani artikli</h2>
                        <a class="btn btn-sm btn-light" href="{{ route('products') }}">
                            Svi artikli <i class="fa fa-arrow-right ml-1" aria-hidden="true"></i>
                        </a>
                    </div>
                    <div class="block-content">
                        @if($products->isEmpty())
                            <div class="dashboard-list-empty">
                                <i class="fa-duotone fa-books" aria-hidden="true"></i>
                                <span>Još nema prodanih artikala za prikaz.</span>
                            </div>
                        @else
                            <table class="table table-borderless table-vcenter dashboard-list-table dashboard-products-table">
                                <thead class="sr-only">
                                    <tr>
                                        <th>ID</th>
                                        <th>Artikl, autor i datum</th>
                                        <th>Cijena</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($products as $product)
                                    @php($authorTitle = trim((string) optional(optional($product->product)->author)->title))
                                    @php($hasAuthor = $authorTitle !== '' && $authorTitle !== '/')
                                    <tr>
                                        <td class="dashboard-list-id">
                                            <a href="{{ route('products.edit', ['product' => $product->product_id]) }}">#{{ $product->product_id }}</a>
                                        </td>
                                        <td class="dashboard-list-main">
                                            <a href="{{ route('products.edit', ['product' => $product->product_id]) }}" title="{{ $product->name }}">{{ $product->name }}</a>
                                            <small title="{{ $hasAuthor ? 'Autor: ' . $authorTitle : 'Autor nije naveden' }}">
                                                Autor:
                                                @if($hasAuthor)
                                                    {{ $authorTitle }}
                                                @else
                                                    <span aria-hidden="true">—</span><span class="sr-only">nije naveden</span>
                                                @endif
                                                <span aria-hidden="true">·</span>
                                                {{ optional($product->created_at)->format('d.m.Y. H:i') }}
                                            </small>
                                        </td>
                                        <td class="dashboard-list-price">
                                            <strong>{{ \App\Helpers\Currency::main($product->price, true) }}</strong>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css_after')
    <style>
        .dashboard-hero {
            border-bottom: 1px solid #ded8cc;
            background: #f7f4ed !important;
        }

        .dashboard-hero .content-full { padding-top: 1.45rem !important; padding-bottom: 1.45rem !important; }
        .dashboard-hero h1 { color: #29332d; font-family: var(--admin-font); font-size: 2rem; letter-spacing: -.025em; }
        .dashboard-title-kicker { display: block; margin-bottom: .28rem; color: #8b6535; font-size: var(--admin-type-xs); font-weight: 800; letter-spacing: .055em; text-transform: uppercase; }
        .dashboard-title-kicker i { margin-right: .3rem; }

        .dashboard-content {
            --dashboard-primary: #315344;
            --dashboard-success: #4d725c;
            --dashboard-warning: #a5793d;
            --dashboard-info: #55706a;
            --dashboard-ink: #29332d;
            --dashboard-muted: #6f786f;
            --dashboard-line: #ded8cc;
            --dashboard-soft: #f7f4ed;
            padding-top: 1rem;
        }

        .dashboard-statistics-button {
            display: inline-flex;
            min-height: 2.6rem;
            align-items: center;
            gap: .6rem;
            padding-right: 1rem;
            padding-left: 1rem;
            border-color: #315344;
            border-radius: .42rem;
            color: #fff;
            background: #315344;
            font-weight: 700;
            box-shadow: none;
        }

        .dashboard-statistics-button:hover,
        .dashboard-statistics-button:focus { border-color: #243f33; color: #fff; background: #243f33; }

        .dashboard-statistics-button .fa-arrow-right {
            margin-left: .2rem;
            font-size: .8rem;
            transition: transform .15s ease;
        }

        .dashboard-statistics-button:hover .fa-arrow-right {
            transform: translateX(2px);
        }

        .dashboard-section-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: .8rem;
        }

        .dashboard-section-eyebrow {
            display: block;
            margin-bottom: .2rem;
            color: #8b6535;
            font-size: var(--admin-type-xs);
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .dashboard-section-heading h2 {
            margin: 0;
            color: var(--dashboard-ink);
            font-size: var(--admin-type-title);
            font-weight: 700;
        }

        .dashboard-section-note {
            color: var(--dashboard-muted);
            font-size: var(--admin-type-sm);
        }

        .dashboard-kpi-grid {
            display: grid;
            margin: 0 0 1.25rem;
            gap: .85rem;
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .dashboard-kpi-col {
            display: block;
            width: auto;
            max-width: none;
            padding: 0;
        }

        .dashboard-kpi-card {
            min-width: 100%;
            height: 100%;
            margin: 0 !important;
            overflow: hidden;
            border: 1px solid var(--dashboard-line) !important;
            border-radius: var(--admin-radius) !important;
            background: #fff;
            box-shadow: none;
            transition: background-color .15s ease;
        }

        .dashboard-kpi-col:last-child .dashboard-kpi-card { border-right: 1px solid var(--dashboard-line); }

        .dashboard-kpi-card:hover,
        .dashboard-kpi-card:focus {
            background: #fbfaf7;
            box-shadow: none;
        }

        .dashboard-kpi-content {
            display: flex;
            min-height: 0;
            flex-direction: column;
            padding: 1rem 1.1rem .95rem !important;
        }

        .dashboard-kpi-head {
            display: flex;
            align-items: center;
            gap: .65rem;
            margin-bottom: 1rem;
        }

        .dashboard-kpi-icon {
            display: inline-flex;
            width: 2.35rem;
            height: 2.35rem;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: var(--dashboard-primary);
            background: #edf1eb;
            font-size: 1.08rem;
        }

        .dashboard-kpi-card-success .dashboard-kpi-icon { color: #7e6744; background: #f4eee4; }
        .dashboard-kpi-card-info .dashboard-kpi-icon { color: #55706a; background: #eef2f0; }

        .dashboard-kpi-period {
            color: var(--dashboard-ink);
            font-size: .96rem;
            font-weight: 700;
        }

        .dashboard-kpi-arrow {
            margin-left: auto;
            color: #a4b0bf;
            font-size: .78rem;
            transition: transform .15s ease, color .15s ease;
        }

        .dashboard-kpi-card:hover .dashboard-kpi-arrow,
        .dashboard-kpi-card:focus .dashboard-kpi-arrow {
            color: var(--dashboard-primary);
            transform: translateX(2px);
        }

        .dashboard-kpi-label {
            display: block;
            color: var(--dashboard-muted);
            font-size: var(--admin-type-xs);
            font-weight: 700;
            line-height: 1.25;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .dashboard-kpi-value {
            margin: .35rem 0 1rem;
            overflow: hidden;
            color: var(--dashboard-ink);
            font-size: 1.78rem;
            font-weight: 700;
            line-height: 1.08;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dashboard-kpi-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .6rem;
            margin-top: .75rem;
            padding-top: .8rem;
            border-top: 1px solid #e9e4da;
        }

        .dashboard-kpi-meta div {
            min-width: 0;
        }

        .dashboard-kpi-meta div + div {
            padding-left: .6rem;
            border-left: 1px solid #e9e4da;
        }

        .dashboard-kpi-meta span,
        .dashboard-kpi-meta strong {
            display: block;
        }

        .dashboard-kpi-meta span {
            overflow: hidden;
            color: var(--dashboard-muted);
            font-size: var(--admin-type-xs);
            font-weight: 700;
            text-overflow: ellipsis;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .dashboard-kpi-meta strong {
            margin-top: .2rem;
            color: var(--dashboard-ink);
            font-size: .9rem;
        }

        .dashboard-sales-block {
            margin-bottom: 1.5rem;
            border: 1px solid var(--dashboard-line);
            box-shadow: none;
        }

        .dashboard-sales-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.15rem;
        }

        .dashboard-sales-subtitle {
            color: var(--dashboard-muted);
            font-size: var(--admin-type-sm);
        }

        .dashboard-sales-header .block-title,
        .dashboard-list-block .block-title {
            color: var(--dashboard-ink);
            font-size: var(--admin-type-title);
            font-weight: 700;
        }

        .dashboard-sales-filters {
            display: flex;
            align-items: flex-end;
            gap: .65rem;
        }

        .dashboard-filter label {
            display: block;
            margin-bottom: .25rem;
            color: var(--dashboard-muted);
            font-size: var(--admin-type-xs);
            font-weight: 700;
            text-transform: uppercase;
        }

        .dashboard-filter .form-control {
            min-width: 145px;
            height: 2.35rem;
            padding-top: .35rem;
            padding-bottom: .35rem;
            border-color: #d9d3c8;
            font-size: .9rem;
            font-weight: 600;
        }

        .dashboard-sales-content {
            position: relative;
            padding-bottom: 1.25rem;
        }

        .dashboard-sales-content .tab-pane.active {
            display: flex;
            flex-direction: column;
        }

        .dashboard-sales-content .tab-pane.active .dashboard-summary-grid { order: 1; }
        .dashboard-sales-content .tab-pane.active .dashboard-chart-wrap { order: 2; }
        .dashboard-sales-content .tab-pane.active .dashboard-breakdowns { order: 3; }

        .dashboard-sales-tabs {
            margin-bottom: 1rem;
            border-bottom-color: var(--dashboard-line);
        }

        .dashboard-sales-tabs .nav-link {
            padding: .7rem .95rem;
            border: 0;
            border-bottom: 2px solid transparent;
            color: var(--dashboard-muted);
            font-size: .9rem;
            font-weight: 700;
        }

        .dashboard-sales-tabs .nav-link:hover {
            color: var(--dashboard-primary);
        }

        .dashboard-sales-tabs .nav-link.active {
            border-bottom-color: var(--dashboard-primary);
            color: var(--dashboard-primary);
            background: transparent;
        }

        .dashboard-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .75rem;
            margin-bottom: 1rem;
            overflow: visible;
            border: 0;
            border-radius: 0;
            background: transparent;
        }

        .dashboard-summary-card {
            min-width: 0;
            padding: .9rem 1rem;
            border: 1px solid var(--dashboard-line);
            border-radius: var(--admin-radius);
            background: #fff;
        }

        .dashboard-summary-card:last-child {
            border-right: 1px solid var(--dashboard-line);
        }

        .dashboard-summary-label {
            display: block;
            color: var(--dashboard-muted);
            font-size: var(--admin-type-xs);
            font-weight: 700;
            text-transform: uppercase;
        }

        .dashboard-summary-card strong {
            display: block;
            margin-top: .35rem;
            overflow: hidden;
            color: var(--dashboard-ink);
            font-size: 1.22rem;
            line-height: 1.2;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dashboard-summary-card small {
            display: block;
            min-height: 1rem;
            margin-top: .2rem;
            color: var(--dashboard-muted);
            font-size: var(--admin-type-xs);
            font-weight: 600;
        }

        .dashboard-breakdowns {
            margin-right: -.4rem;
            margin-left: -.4rem;
            margin-top: 1rem;
            margin-bottom: 0;
        }

        .dashboard-breakdowns > [class*=col-] {
            padding-right: .4rem;
            padding-left: .4rem;
        }

        .dashboard-breakdown-card {
            min-height: 11.25rem;
            margin-bottom: .8rem;
            padding: .85rem .95rem;
            border: 1px solid var(--dashboard-line);
            border-radius: .4rem;
            background: #faf8f3;
        }

        .dashboard-breakdown-card h3 {
            display: flex;
            align-items: center;
            gap: .45rem;
            margin-bottom: .7rem;
            color: var(--dashboard-ink);
            font-size: .8rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .dashboard-breakdown-card h3 i {
            color: var(--dashboard-primary);
            font-size: .8rem;
        }

        .dashboard-breakdown-count {
            display: inline-flex;
            min-height: 1.45rem;
            align-items: center;
            gap: .3rem;
            margin-left: auto;
            padding: .2rem .48rem;
            border: 1px solid #ded8cc;
            border-radius: 999px;
            background: #fff;
            color: var(--dashboard-primary);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: 0;
            line-height: 1;
            text-transform: none;
            white-space: nowrap;
        }

        .dashboard-breakdown-count i {
            font-size: .66rem !important;
        }

        .dashboard-breakdown-list {
            max-height: 8.4rem;
            overflow-y: auto;
            scrollbar-color: #c7d1de transparent;
            scrollbar-width: thin;
        }

        .dashboard-breakdown-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: .8rem;
            padding: .45rem 0;
            border-top: 1px solid #e9e4da;
        }

        .dashboard-breakdown-row:first-child {
            border-top: 0;
        }

        .dashboard-breakdown-label {
            overflow: hidden;
            color: var(--dashboard-ink);
            font-size: .88rem;
            font-weight: 600;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dashboard-breakdown-value {
            color: var(--dashboard-ink);
            font-size: .84rem;
            font-weight: 700;
            text-align: right;
            white-space: nowrap;
        }

        .dashboard-breakdown-value small {
            display: block;
            color: var(--dashboard-muted);
            font-size: .72rem;
            font-weight: 600;
        }

        .dashboard-breakdown-empty {
            padding: .75rem 0;
            color: #8a96a5;
            font-size: .86rem;
        }

        .dashboard-chart-wrap {
            position: relative;
            height: 360px;
            padding: .8rem .35rem .2rem;
            border: 1px solid var(--dashboard-line);
            border-radius: .5rem;
            background: #fff;
        }

        .dashboard-chart-empty {
            position: absolute;
            inset: .5rem 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px dashed #d8d1c5;
            border-radius: .4rem;
            color: var(--dashboard-muted);
            background: rgba(247, 244, 237, .92);
            font-size: .9rem;
            font-weight: 600;
        }

        .dashboard-loading {
            position: absolute;
            z-index: 5;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            gap: .6rem;
            color: var(--dashboard-primary);
            background: rgba(255, 255, 255, .72);
            font-size: .88rem;
            font-weight: 700;
            backdrop-filter: blur(1px);
        }

        .dashboard-sales-content.is-loading .dashboard-loading {
            display: flex;
        }

        .dashboard-list-row {
            margin-top: .25rem;
        }

        .dashboard-list-row > [class*="col-"] {
            display: flex;
        }

        .dashboard-list-block {
            display: flex;
            width: 100%;
            flex-direction: column;
            border: 1px solid var(--dashboard-line);
        }

        .dashboard-list-block .block-header {
            flex: 0 0 auto;
            padding: .78rem .9rem;
        }

        .dashboard-list-block .block-content {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            padding: .28rem .75rem .65rem;
        }

        .dashboard-list-table {
            width: 100%;
            height: 100%;
            flex: 1 1 auto;
            margin-bottom: 0;
            table-layout: fixed;
        }

        .dashboard-list-table td {
            padding: .48rem .35rem;
            border-top: 1px solid #e9e4da;
            vertical-align: middle;
        }

        .dashboard-list-table tbody tr:first-child td {
            border-top: 0;
        }

        .dashboard-list-table tbody tr:hover {
            background: #f8fbff;
        }

        .dashboard-list-id {
            width: 4.15rem;
            white-space: nowrap;
        }

        .dashboard-list-id a {
            color: var(--dashboard-primary);
            font-size: .84rem;
            font-weight: 700;
        }

        .dashboard-list-main {
            min-width: 0;
            overflow: hidden;
        }

        .dashboard-list-main a,
        .dashboard-list-main small {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dashboard-list-main a {
            color: var(--dashboard-ink);
            font-size: .9rem;
            font-weight: 600;
        }

        .dashboard-list-main small {
            margin-top: .15rem;
            color: var(--dashboard-muted);
            font-size: .74rem;
        }

        .dashboard-order-summary {
            display: table-cell;
            vertical-align: middle;
        }

        .dashboard-order-summary strong {
            display: block;
            line-height: 1.2;
        }

        .dashboard-order-summary .badge {
            display: inline-flex;
            min-width: 3.6rem;
            min-height: 1.35rem;
            align-items: center;
            justify-content: center;
            margin-top: .3rem;
            padding: .2rem .52rem;
            overflow: hidden;
            border: 1px solid transparent;
            border-radius: 999px;
            box-shadow: none;
            font-size: .76rem;
            font-weight: 750;
            line-height: 1;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dashboard-list-price {
            width: 5.7rem;
            color: var(--dashboard-ink);
            font-size: .86rem;
            font-weight: 700;
            text-align: right;
            white-space: nowrap;
        }

        .dashboard-list-empty {
            display: flex;
            min-height: 11rem;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .65rem;
            color: var(--dashboard-muted);
            font-size: .8rem;
        }

        .dashboard-list-empty i {
            color: #bdc8d5;
            font-size: 1.5rem;
        }

        @media (max-width: 991.98px) {
            .dashboard-kpi-grid { grid-template-columns: 1fr; }
            .dashboard-kpi-card { border-right: 0; border-bottom: 1px solid var(--dashboard-line); }
            .dashboard-kpi-col:last-child .dashboard-kpi-card { border-bottom: 0; }

            .dashboard-kpi-content {
                min-height: 0;
            }

            .dashboard-breakdown-card {
                min-height: 0;
            }

            .dashboard-breakdown-list {
                max-height: 8.5rem;
            }
        }

        @media (max-width: 767.98px) {
            .dashboard-content {
                padding-top: .9rem;
            }

            .dashboard-kpi-content {
                min-height: 0;
                padding: .9rem !important;
            }

            .dashboard-kpi-head {
                gap: .45rem;
            }

            .dashboard-kpi-icon {
                width: 2rem;
                height: 2rem;
                font-size: .92rem;
            }

            .dashboard-kpi-label {
                font-size: var(--admin-type-xs);
            }

            .dashboard-kpi-value {
                margin: .3rem 0 .7rem;
                font-size: 1.42rem;
            }

            .dashboard-section-heading {
                align-items: flex-start;
                flex-direction: column;
                gap: .25rem;
            }

            .dashboard-sales-header {
                align-items: stretch;
                flex-direction: column;
            }

            .dashboard-sales-filters {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-filter .form-control {
                width: 100%;
                min-width: 0;
            }

            .dashboard-sales-tabs {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: .45rem;
                border-bottom: 0;
            }

            .dashboard-sales-tabs .nav-link {
                display: flex;
                min-height: 42px;
                align-items: center;
                justify-content: center;
                padding: .55rem;
                border: 1px solid var(--dashboard-line);
                border-radius: .35rem;
                text-align: center;
            }

            .dashboard-sales-tabs .nav-link.active {
                border-color: var(--dashboard-primary);
            }

            .dashboard-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-summary-card:nth-child(2) {
                border-right: 0;
            }

            .dashboard-summary-card:nth-child(n+3) {
                border-top: 1px solid var(--dashboard-line);
            }

            .dashboard-chart-wrap {
                height: 285px;
            }
        }

        @media (max-width: 575.98px) {
            .dashboard-list-table,
            .dashboard-list-table tbody,
            .dashboard-list-table tr,
            .dashboard-list-table td {
                display: block;
                width: 100%;
            }

            .dashboard-list-table tr {
                display: grid;
                margin-bottom: .55rem;
                padding: .65rem .7rem;
                border: 1px solid var(--dashboard-line);
                border-radius: .35rem;
                grid-template-columns: minmax(0, 1fr) auto;
                grid-template-areas:
                    "id price"
                    "main main";
                column-gap: .75rem;
                row-gap: .35rem;
            }

            .dashboard-list-table tr:last-child {
                margin-bottom: 0;
            }

            .dashboard-list-table td {
                width: auto;
                padding: .08rem 0;
                border: 0;
                text-align: left;
            }

            .dashboard-list-id {
                grid-area: id;
                align-self: start;
            }

            .dashboard-list-main {
                grid-area: main;
                min-width: 0;
                padding-right: 0 !important;
            }

            .dashboard-list-price {
                position: static;
                grid-area: price;
                align-self: start;
                width: auto;
                text-align: right;
            }

            .dashboard-list-status {
                margin-top: .35rem;
                text-align: left;
            }

            .dashboard-order-summary .badge {
                display: flex;
                margin-left: auto;
            }
        }
    </style>
@endpush

@push('js_after')
    @if($canViewSales)
    <script src="{{ \App\Helpers\Asset::url('js/plugins/chart.js/Chart.bundle.min.js') }}"></script>
    <script>
        (() => {
            const currentYear = {{ now()->year }};
            const currentMonth = {{ now()->month }};
            const salesContent = document.querySelector('.dashboard-sales-content');
            const errorAlert = document.getElementById('dashboard-sales-error');
            const yearSelect = document.getElementById('chart-year');
            const monthSelect = document.getElementById('chart-month');
            const monthChartContext = document.getElementById('salesChart').getContext('2d');
            const yearChartContext = document.getElementById('yearChart').getContext('2d');
            const monthNames = ['Sij', 'Velj', 'Ožu', 'Tra', 'Svi', 'Lip', 'Srp', 'Kol', 'Ruj', 'Lis', 'Stu', 'Pro'];
            const fmtCurrency = new Intl.NumberFormat('hr-HR', {
                style: 'currency',
                currency: 'EUR',
                maximumFractionDigits: 2
            });
            const fmtInteger = new Intl.NumberFormat('hr-HR', { maximumFractionDigits: 0 });
            const fmtDecimal = new Intl.NumberFormat('hr-HR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            let monthChart = null;
            let yearChart = null;
            let pendingRequests = 0;
            let monthRequestId = 0;
            let yearRequestId = 0;

            function beginLoading() {
                pendingRequests += 1;
                salesContent.classList.add('is-loading');
                salesContent.setAttribute('aria-busy', 'true');
            }

            function endLoading() {
                pendingRequests = Math.max(0, pendingRequests - 1);

                if (pendingRequests === 0) {
                    salesContent.classList.remove('is-loading');
                    salesContent.setAttribute('aria-busy', 'false');
                }
            }

            function setError(visible) {
                errorAlert.classList.toggle('d-none', ! visible);
            }

            function setChartEmpty(elementId, empty) {
                document.getElementById(elementId).classList.toggle('d-none', ! empty);
            }

            function prepareMonthSeries(year, month, rows) {
                const daysInMonth = new Date(year, month, 0).getDate();
                const byDay = {};

                (rows || []).forEach(row => {
                    byDay[Number(row.day)] = {
                        total: Number(row.total) || 0,
                        orders: Number(row.orders) || 0
                    };
                });

                const labels = [];
                const totals = [];
                const orders = [];

                for (let day = 1; day <= daysInMonth; day += 1) {
                    const row = byDay[day] || { total: 0, orders: 0 };
                    labels.push(day + '.');
                    totals.push(row.total);
                    orders.push(row.orders);
                }

                return { labels, totals, orders };
            }

            function prepareYearSeries(rows) {
                const byMonth = {};

                (rows || []).forEach(row => {
                    byMonth[Number(row.month)] = Number(row.total) || 0;
                });

                return monthNames.map((label, index) => byMonth[index + 1] || 0);
            }

            function updateSummary(prefix, summary) {
                const data = summary || {};
                const total = Number(data.total) || 0;
                const orders = Number(data.orders) || 0;
                const items = Number(data.item_quantity) || 0;
                const averageItems = Number(data.avg_items) || 0;
                const averageOrder = orders > 0 ? total / orders : 0;

                document.getElementById('kpi-' + prefix + '-total').textContent = fmtCurrency.format(total);
                document.getElementById('kpi-' + prefix + '-orders').textContent = fmtInteger.format(orders);
                document.getElementById('kpi-' + prefix + '-items').textContent = fmtInteger.format(items);
                document.getElementById('kpi-' + prefix + '-items-avg').textContent =
                    fmtDecimal.format(averageItems) + ' po narudžbi';
                document.getElementById('kpi-' + prefix + '-aov').textContent = fmtCurrency.format(averageOrder);
            }

            function renderBreakdown(elementId, rows) {
                const container = document.getElementById(elementId);
                const count = document.getElementById(elementId + '-count');
                container.replaceChildren();

                if (count) {
                    const rowCount = Array.isArray(rows) ? rows.length : 0;
                    count.replaceChildren(document.createTextNode(fmtInteger.format(rowCount) + ' ukupno'));

                    if (rowCount > 3) {
                        const icon = document.createElement('i');
                        icon.className = 'fa-duotone fa-chevron-down';
                        icon.setAttribute('aria-hidden', 'true');
                        count.appendChild(icon);
                    }
                }

                if (! rows || rows.length === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'dashboard-breakdown-empty';
                    empty.textContent = 'Nema podataka';
                    container.appendChild(empty);
                    return;
                }

                if (rows.length > 3) {
                    container.tabIndex = 0;
                    container.setAttribute('aria-label', rows.length + ' stavki; popis se može pomicati');
                } else {
                    container.removeAttribute('tabindex');
                    container.removeAttribute('aria-label');
                }

                rows.forEach(row => {
                    const item = document.createElement('div');
                    const label = document.createElement('span');
                    const value = document.createElement('span');
                    const total = document.createElement('small');

                    item.className = 'dashboard-breakdown-row';
                    label.className = 'dashboard-breakdown-label';
                    value.className = 'dashboard-breakdown-value';
                    label.textContent = row.label || 'Nepoznato';
                    label.title = label.textContent;
                    value.textContent = fmtInteger.format(Number(row.orders) || 0) + ' nar.';
                    total.textContent = fmtCurrency.format(Number(row.total) || 0);

                    value.appendChild(total);
                    item.appendChild(label);
                    item.appendChild(value);
                    container.appendChild(item);
                });
            }

            function updateDetails(prefix, summary) {
                const data = summary || {};
                renderBreakdown(prefix + '-payment-methods', data.payment_methods || []);
                renderBreakdown(prefix + '-shipping-methods', data.shipping_methods || []);
                renderBreakdown(prefix + '-statuses', data.statuses || []);
            }

            function renderMonthChart(series) {
                if (monthChart) {
                    monthChart.destroy();
                }

                monthChart = new Chart(monthChartContext, {
                    type: 'line',
                    data: {
                        labels: series.labels,
                        datasets: [
                            {
                                label: 'Promet',
                                data: series.totals,
                                borderColor: '#315344',
                                backgroundColor: 'rgba(49, 83, 68, .12)',
                                borderWidth: 2,
                                pointRadius: 2,
                                pointHoverRadius: 5,
                                fill: true,
                                lineTension: 0,
                                yAxisID: 'turnover'
                            },
                            {
                                label: 'Narudžbe',
                                data: series.orders,
                                borderColor: '#a5793d',
                                backgroundColor: 'transparent',
                                borderWidth: 2,
                                pointRadius: 2,
                                pointHoverRadius: 5,
                                fill: false,
                                lineTension: 0,
                                yAxisID: 'orders'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        legend: {
                            labels: { usePointStyle: true, boxWidth: 8 }
                        },
                        tooltips: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: (tooltipItem, data) => {
                                    const dataset = data.datasets[tooltipItem.datasetIndex];
                                    return dataset.yAxisID === 'turnover'
                                        ? dataset.label + ': ' + fmtCurrency.format(tooltipItem.yLabel)
                                        : dataset.label + ': ' + fmtInteger.format(tooltipItem.yLabel);
                                }
                            }
                        },
                        scales: {
                            xAxes: [{
                                gridLines: { display: false },
                                ticks: { maxTicksLimit: 16 }
                            }],
                            yAxes: [
                                {
                                    id: 'turnover',
                                    position: 'left',
                                    ticks: {
                                        beginAtZero: true,
                                        callback: value => fmtInteger.format(value) + ' €'
                                    }
                                },
                                {
                                    id: 'orders',
                                    position: 'right',
                                    ticks: {
                                        beginAtZero: true,
                                        precision: 0
                                    },
                                    gridLines: { drawOnChartArea: false }
                                }
                            ]
                        }
                    }
                });
            }

            function renderYearChart(selectedYear, selectedTotals, previousTotals) {
                if (yearChart) {
                    yearChart.destroy();
                }

                yearChart = new Chart(yearChartContext, {
                    type: 'line',
                    data: {
                        labels: monthNames,
                        datasets: [
                            {
                                label: String(selectedYear),
                                data: selectedTotals,
                                borderColor: '#315344',
                                backgroundColor: 'rgba(49, 83, 68, .12)',
                                borderWidth: 2,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                                fill: true,
                                lineTension: 0
                            },
                            {
                                label: String(selectedYear - 1),
                                data: previousTotals,
                                borderColor: '#a5793d',
                                backgroundColor: 'transparent',
                                borderWidth: 2,
                                borderDash: [5, 4],
                                pointRadius: 2,
                                pointHoverRadius: 5,
                                fill: false,
                                lineTension: 0
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        legend: {
                            labels: { usePointStyle: true, boxWidth: 8 }
                        },
                        tooltips: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: (tooltipItem, data) =>
                                    data.datasets[tooltipItem.datasetIndex].label + ': ' +
                                    fmtCurrency.format(tooltipItem.yLabel)
                            }
                        },
                        scales: {
                            xAxes: [{ gridLines: { display: false } }],
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true,
                                    callback: value => fmtInteger.format(value) + ' €'
                                }
                            }]
                        }
                    }
                });
            }

            function loadMonth(year, month) {
                const requestId = ++monthRequestId;
                beginLoading();

                return $.get('{{ route('dashboard.chart.month') }}', { year, month })
                    .done(data => {
                        if (requestId !== monthRequestId) {
                            return;
                        }

                        const days = data.days || [];
                        const summary = data.summary || {};
                        const series = prepareMonthSeries(Number(year), Number(month), days);

                        updateSummary('month', summary);
                        updateDetails('monthly', summary);
                        renderMonthChart(series);
                        setChartEmpty('month-chart-empty', Number(summary.orders) === 0);
                        setError(false);
                    })
                    .fail(() => {
                        if (requestId === monthRequestId) {
                            setError(true);
                        }
                    })
                    .always(endLoading);
            }

            function loadYear(year) {
                const requestId = ++yearRequestId;
                beginLoading();

                return $.when(
                    $.get('{{ route('dashboard.chart.year') }}', { year }),
                    $.get('{{ route('dashboard.chart.year') }}', { year: Number(year) - 1 })
                )
                    .done((selectedResponse, previousResponse) => {
                        if (requestId !== yearRequestId) {
                            return;
                        }

                        const selected = selectedResponse[0] || {};
                        const previous = previousResponse[0] || {};
                        const summary = selected.summary || {};

                        updateSummary('year', summary);
                        updateDetails('yearly', summary);
                        renderYearChart(
                            Number(year),
                            prepareYearSeries(selected.months || []),
                            prepareYearSeries(previous.months || [])
                        );
                        setChartEmpty('year-chart-empty', Number(summary.orders) === 0);
                        setError(false);
                    })
                    .fail(() => {
                        if (requestId === yearRequestId) {
                            setError(true);
                        }
                    })
                    .always(endLoading);
            }

            yearSelect.value = String(currentYear);
            monthSelect.value = String(currentMonth);

            $('#chart-year').on('change', function () {
                const year = Number(this.value);
                loadMonth(year, Number(monthSelect.value));
                loadYear(year);
            });

            $('#chart-month').on('change', function () {
                loadMonth(Number(yearSelect.value), Number(this.value));
            });

            $('#salesTabs a[data-toggle="tab"]').on('shown.bs.tab', () => {
                if (monthChart) {
                    monthChart.resize();
                }
                if (yearChart) {
                    yearChart.resize();
                }
            });

            loadMonth(currentYear, currentMonth);
            loadYear(currentYear);
        })();
    </script>
    @endif
@endpush
