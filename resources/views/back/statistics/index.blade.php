@extends('back.layouts.backend')

@section('content')
    <div class="bg-body-light statistics-hero">
        <div class="content content-full">
            <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center">
                <div>
                    <a href="{{ route('dashboard') }}" class="statistics-back-link">
                        <i class="fa-duotone fa-arrow-left mr-2" aria-hidden="true"></i>Nadzorna ploča
                    </a>
                    <span class="statistics-title-kicker"><i class="fa-duotone fa-books" aria-hidden="true"></i> Poslovni uvidi antikvarijata</span>
                    <h1 class="font-size-h2 font-w600 mb-1">Detaljne statistike</h1>
                    <p class="text-muted mb-0">Prodaja, lokacije, artikli, kupci i operativa na jednom mjestu</p>
                </div>
                <div class="statistics-period-label mt-3 mt-md-0">
                    <i class="fa-duotone fa-calendar-range" aria-hidden="true"></i>
                    <div>
                        <small>Odabrano razdoblje</small>
                        <strong id="statistics-period-label" aria-live="polite">Učitavanje…</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="content statistics-content">
        <div class="block block-rounded statistics-filter-block">
            <div class="block-content py-3">
                <div class="statistics-filter-body">
                    <div class="statistics-presets" role="group" aria-label="Brzi odabir razdoblja">
                        <button type="button" class="btn btn-sm btn-light" data-days="7">7 dana</button>
                        <button type="button" class="btn btn-sm btn-primary" data-days="30">30 dana</button>
                        <button type="button" class="btn btn-sm btn-light" data-days="90">90 dana</button>
                        <button type="button" class="btn btn-sm btn-light" data-preset="year">Ova godina</button>
                        <button type="button" class="btn btn-sm btn-light" data-preset="previous-year">Prošla godina</button>
                    </div>
                    <form class="statistics-date-form" id="statistics-date-form">
                        <div class="statistics-date-field">
                            <label for="statistics-from-display">Od</label>
                            <input type="text" id="statistics-from-display" class="form-control statistics-datepicker" value="{{ $defaultFrom->format('d.m.Y') }}" autocomplete="off" inputmode="numeric" aria-label="Početni datum">
                            <input type="hidden" id="statistics-from" value="{{ $defaultFrom->toDateString() }}">
                            <i class="fa-duotone fa-calendar-days statistics-date-icon" aria-hidden="true"></i>
                        </div>
                        <div class="statistics-date-field">
                            <label for="statistics-to-display">Do</label>
                            <input type="text" id="statistics-to-display" class="form-control statistics-datepicker" value="{{ $defaultTo->format('d.m.Y') }}" autocomplete="off" inputmode="numeric" aria-label="Završni datum">
                            <input type="hidden" id="statistics-to" value="{{ $defaultTo->toDateString() }}">
                            <i class="fa-duotone fa-calendar-days statistics-date-icon" aria-hidden="true"></i>
                        </div>
                        <button type="submit" class="btn btn-primary statistics-apply-button">
                            <span>Primijeni</span><i class="fa-duotone fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div id="statistics-error" class="alert alert-danger d-none" role="alert">
            <i class="fa fa-exclamation-circle mr-2" aria-hidden="true"></i>
            <span>Statistike trenutno nije moguće učitati. Pokušajte ponovno.</span>
            <button type="button" class="btn btn-sm btn-danger ml-3" id="statistics-retry">Pokušaj ponovno</button>
        </div>

        <div class="statistics-loading" id="statistics-loading" aria-live="polite">
            <i class="fa fa-circle-notch fa-spin mr-2" aria-hidden="true"></i>Učitavanje detaljnih statistika…
        </div>

        <div id="statistics-results" class="d-none">
            <div class="statistics-kpi-grid">
                @foreach([
                    ['total', 'Promet', 'fa-euro-sign', 'primary'],
                    ['orders', 'Broj narudžbi', 'fa-bag-shopping', 'success'],
                    ['items', 'Prodani artikli', 'fa-books', 'info'],
                    ['average_order', 'Prosječna narudžba', 'fa-receipt', 'warning'],
                    ['average_items', 'Artikala po narudžbi', 'fa-layer-group', 'purple'],
                    ['customers', 'Jedinstveni kupci', 'fa-user-group', 'slate'],
                ] as [$key, $label, $icon, $tone])
                    <div class="col-12 col-sm-6 col-xl-4">
                        <div class="block block-rounded statistics-kpi statistics-kpi-{{ $tone }} {{ $loop->index < 3 ? 'statistics-kpi-featured' : 'statistics-kpi-secondary' }}">
                            <div class="block-content">
                                <div class="statistics-kpi-header">
                                    <span class="statistics-kpi-icon"><i class="fa-duotone {{ $icon }}" aria-hidden="true"></i></span>
                                    <span>{{ $label }}</span>
                                </div>
                                <div class="statistics-kpi-value" id="kpi-{{ $key }}">—</div>
                                <div class="statistics-kpi-change" id="change-{{ $key }}">—</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="statistics-tabs-shell">
                <ul class="nav nav-tabs statistics-tabs" id="statistics-tabs" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#statistics-overview" role="tab"><i class="fa-duotone fa-chart-mixed mr-2"></i>Pregled</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#statistics-geography" role="tab"><i class="fa-duotone fa-map-location-dot mr-2"></i>Lokacije</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#statistics-products" role="tab"><i class="fa-duotone fa-books mr-2"></i>Artikli</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#statistics-customers" role="tab"><i class="fa-duotone fa-user-group mr-2"></i>Kupci i operativa</a></li>
                </ul>
            </div>

            <div class="tab-content pt-3">
                <div class="tab-pane fade show active" id="statistics-overview" role="tabpanel">
                    <div class="row row-deck">
                        <div class="col-xl-9">
                            <div class="block block-rounded">
                                <div class="block-header block-header-default">
                                    <div>
                                        <h2 class="block-title font-size-h4">Kretanje prodaje</h2>
                                        <p class="statistics-block-subtitle mb-0">Promet, narudžbe i prodani artikli kroz odabrano razdoblje</p>
                                    </div>
                                </div>
                                <div class="block-content">
                                    <div class="statistics-chart"><canvas id="statistics-trend-chart"></canvas></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3">
                            <div class="block block-rounded">
                                <div class="block-header block-header-default">
                                    <h2 class="block-title font-size-h4">Novi i povratni</h2>
                                </div>
                                <div class="block-content">
                                    <div class="statistics-customer-chart"><canvas id="statistics-customer-chart"></canvas></div>
                                    <div class="statistics-mini-grid">
                                        <div><strong id="customer-new">0</strong><span>Novi kupci</span></div>
                                        <div><strong id="customer-returning">0</strong><span>Povratni kupci</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="block block-rounded">
                        <div class="block-header block-header-default">
                            <div>
                                <h2 class="block-title font-size-h4">Kada kupci naručuju</h2>
                                <p class="statistics-block-subtitle mb-0">Intenzitet narudžbi prema danu u tjednu i satu</p>
                            </div>
                            <span class="badge badge-light">Tamnije = više narudžbi</span>
                        </div>
                        <div class="block-content">
                            <div class="statistics-heatmap-wrap"><div class="statistics-heatmap" id="statistics-heatmap"></div></div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="statistics-geography" role="tabpanel">
                    <div class="statistics-section-toolbar">
                        <div>
                            <h2 class="font-size-h4 font-w600 mb-1">Promet prema lokaciji dostave</h2>
                            <p class="text-muted mb-0">Prikaz koristi državu i grad iz adrese dostave</p>
                        </div>
                        <div class="statistics-metric-select">
                            <label for="map-metric">Prikaži</label>
                            <select id="map-metric" class="form-control">
                                <option value="total">Promet</option>
                                <option value="orders">Narudžbe</option>
                                <option value="items">Artikli</option>
                                <option value="average_order">Prosjek narudžbe</option>
                            </select>
                        </div>
                    </div>

                    <div class="statistics-map-switch" role="group" aria-label="Odabir karte">
                        <button type="button" class="btn btn-primary" data-location-map="croatia"><i class="fa-duotone fa-location-dot mr-2"></i>Hrvatska</button>
                        <button type="button" class="btn btn-light" data-location-map="europe"><i class="fa-duotone fa-earth-europe mr-2"></i>Europa</button>
                        <button type="button" class="btn btn-light" data-location-map="world"><i class="fa-duotone fa-earth-americas mr-2"></i>Svijet</button>
                    </div>

                    <div class="row row-deck">
                        <div class="col-xl-8">
                            <div class="block block-rounded">
                                <div class="block-header block-header-default">
                                    <div>
                                        <h2 class="block-title font-size-h4" id="statistics-location-map-title">Hrvatska</h2>
                                        <p class="statistics-block-subtitle mb-0" id="statistics-location-map-subtitle">Stvarna karta županija s gradovima kupaca</p>
                                    </div>
                                </div>
                                <div class="block-content p-0 statistics-map-stage">
                                    <div class="statistics-map statistics-croatia-map" id="statistics-croatia-map"></div>
                                    <div class="statistics-map d-none" id="statistics-europe-map"></div>
                                    <div class="statistics-map d-none" id="statistics-world-map"></div>
                                    <div class="statistics-croatia-controls" id="statistics-croatia-controls" role="group" aria-label="Zumiranje karte Hrvatske">
                                        <button type="button" data-croatia-zoom="in" aria-label="Povećaj kartu" title="Povećaj kartu"><i class="fa-duotone fa-plus" aria-hidden="true"></i></button>
                                        <button type="button" data-croatia-zoom="out" aria-label="Smanji kartu" title="Smanji kartu"><i class="fa-duotone fa-minus" aria-hidden="true"></i></button>
                                        <button type="button" data-croatia-zoom="reset" aria-label="Vrati cijelu Hrvatsku" title="Vrati cijelu Hrvatsku"><i class="fa-duotone fa-expand" aria-hidden="true"></i></button>
                                        <span id="statistics-croatia-zoom-level" aria-live="polite">100%</span>
                                    </div>
                                    <div class="statistics-croatia-help" id="statistics-croatia-help">Kotačić za zoom · povucite za pomicanje</div>
                                </div>
                                <div class="statistics-map-attribution" id="statistics-map-attribution">
                                    Granice županija: <a href="https://www.geoboundaries.org/" target="_blank" rel="noopener">geoBoundaries</a> / OpenStreetMap (ODbL)
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4">
                            <div class="block block-rounded">
                                <div class="block-header block-header-default">
                                    <div>
                                        <h2 class="block-title font-size-h4" id="statistics-location-ranking-title">Top gradovi</h2>
                                        <p class="statistics-block-subtitle mb-0">Najuspješnije lokacije u odabranom razdoblju</p>
                                    </div>
                                </div>
                                <div class="block-content statistics-ranking-scroll" id="statistics-location-ranking"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="statistics-products" role="tabpanel">
                    <div class="row statistics-products-row statistics-products-primary-row">
                        <div class="col-xl-7">
                            <div class="block block-rounded statistics-scroll-block">
                                <div class="block-header block-header-default">
                                    <div>
                                        <h2 class="block-title font-size-h4">Najprodavaniji artikli</h2>
                                        <p class="statistics-block-subtitle mb-0">Do 25 naslova prema prodanom broju primjeraka</p>
                                    </div>
                                </div>
                                <div class="block-content p-0 statistics-scroll-block-content"><div class="table-responsive statistics-products-table-wrap"><table class="table table-hover table-vcenter mb-0"><thead><tr><th>Artikl</th><th class="text-right">Kom.</th><th class="text-right">Nar.</th><th class="text-right">Promet</th></tr></thead><tbody id="top-products-table"></tbody></table></div></div>
                            </div>
                        </div>
                        <div class="col-xl-5">
                            <div class="block block-rounded statistics-scroll-block">
                                <div class="block-header block-header-default"><h2 class="block-title font-size-h4">Kategorije</h2></div>
                                <div class="block-content statistics-products-ranking-scroll" id="category-ranking"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row statistics-products-row">
                        <div class="col-xl-4"><div class="block block-rounded"><div class="block-header block-header-default"><h2 class="block-title font-size-h4">Autori</h2></div><div class="block-content" id="author-ranking"></div></div></div>
                        <div class="col-xl-4"><div class="block block-rounded"><div class="block-header block-header-default"><h2 class="block-title font-size-h4">Izdavači</h2></div><div class="block-content" id="publisher-ranking"></div></div></div>
                        <div class="col-xl-4">
                            <div class="block block-rounded">
                                <div class="block-header block-header-default"><div><h2 class="block-title font-size-h4">Potražnja bez kupnje</h2><p class="statistics-block-subtitle mb-0">Trenutno najtraženiji artikli na listi želja</p></div></div>
                                <div class="block-content" id="wishlist-ranking"></div>
                            </div>
                        </div>
                    </div>
                    <div class="statistics-insight" id="discount-insight"></div>
                </div>

                <div class="tab-pane fade" id="statistics-customers" role="tabpanel">
                    <div class="row row-deck">
                        <div class="col-sm-6 col-xl-3"><div class="block block-rounded statistics-small-card"><div class="block-content"><span>Ponovljene kupnje</span><strong id="customer-repeat-rate">0%</strong><small id="customer-repeat-count">0 kupaca</small></div></div></div>
                        <div class="col-sm-6 col-xl-3"><div class="block block-rounded statistics-small-card"><div class="block-content"><span>Registrirane narudžbe</span><strong id="customer-registered">0</strong><small>S korisničkim računom</small></div></div></div>
                        <div class="col-sm-6 col-xl-3"><div class="block block-rounded statistics-small-card"><div class="block-content"><span>Gost narudžbe</span><strong id="customer-guests">0</strong><small>Bez prijave</small></div></div></div>
                        <div class="col-sm-6 col-xl-3"><div class="block block-rounded statistics-small-card"><div class="block-content"><span>Jedinstveni kupci</span><strong id="customer-unique">0</strong><small>Prema e-mail adresi</small></div></div></div>
                    </div>
                    <div class="row row-deck">
                        <div class="col-xl-4"><div class="block block-rounded"><div class="block-header block-header-default"><h2 class="block-title font-size-h4">Načini plaćanja</h2></div><div class="block-content" id="payment-ranking"></div></div></div>
                        <div class="col-xl-4"><div class="block block-rounded"><div class="block-header block-header-default"><h2 class="block-title font-size-h4">Načini dostave</h2></div><div class="block-content" id="shipping-ranking"></div></div></div>
                        <div class="col-xl-4"><div class="block block-rounded"><div class="block-header block-header-default"><div><h2 class="block-title font-size-h4">Statusi svih narudžbi</h2><p class="statistics-block-subtitle mb-0">Uključuje i narudžbe izvan prometa</p></div></div><div class="block-content" id="status-ranking"></div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css_before')
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('js/plugins/jvectormap/dist/jquery-jvectormap.css') }}">
@endpush

@push('css_after')
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('js/plugins/bootstrap-datepicker/css/bootstrap-datepicker.css') }}">
    <style>
        .statistics-hero { border-bottom: 1px solid #ded8cc; background: #f7f4ed !important; }
        .statistics-hero .content-full { padding-top: 1.45rem !important; padding-bottom: 1.45rem !important; }
        .statistics-hero h1 { color: #29332d; font-family: var(--admin-font); font-size: 2rem; letter-spacing: -.025em; }
        .statistics-back-link { display: inline-flex; align-items: center; margin-bottom: .45rem; color: #6f746f; font-size: var(--admin-type-sm); font-weight: 700; text-transform: uppercase; letter-spacing: .045em; }
        .statistics-title-kicker { display: block; margin-bottom: .28rem; color: #8b6535; font-size: var(--admin-type-xs); font-weight: 800; letter-spacing: .055em; text-transform: uppercase; }
        .statistics-title-kicker i { margin-right: .3rem; }
        .statistics-period-label { display: flex; min-width: 260px; align-items: center; gap: .7rem; padding: .65rem .8rem; border: 1px solid #d9d2c5; border-radius: .5rem; background: #fff; color: #536159; box-shadow: none; }
        .statistics-period-label > i { display: inline-flex; width: 32px; height: 32px; flex: 0 0 auto; align-items: center; justify-content: center; border-radius: .4rem; background: #ece6d9; color: #315344; }
        .statistics-period-label small, .statistics-period-label strong { display: block; }
        .statistics-period-label small { margin-bottom: .08rem; color: #827f76; font-size: var(--admin-type-xs); font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
        .statistics-period-label strong { color: #344039; font-size: .9rem; white-space: nowrap; }
        .statistics-content { --stats-primary: #315344; --stats-success: #4d725c; --stats-info: #55706a; --stats-warning: #a5793d; --stats-purple: #725c74; --stats-slate: #657069; --stats-ink: #29332d; --stats-muted: #6f786f; --stats-line: #ded8cc; --stats-soft: #f7f4ed; padding-top: 1rem; }
        .statistics-filter-block { margin-bottom: 1rem; border: 1px solid var(--stats-line); border-radius: .55rem; box-shadow: none; }
        .statistics-filter-block .block-content { display: block; padding: .8rem .95rem !important; }
        .statistics-filter-body { display: flex; width: 100%; align-items: center; justify-content: space-between; flex-wrap: nowrap; gap: .75rem; }
        .statistics-presets { display: flex; flex: 0 1 auto; flex-wrap: nowrap; gap: .4rem; }
        #main-container .statistics-presets .btn { min-height: 44px; height: 44px; padding-right: .55rem; padding-left: .55rem; border: 1px solid #c9c4b9; border-radius: .38rem; color: #4d574f; background: #faf8f3; font-weight: 700; white-space: nowrap; }
        #main-container .statistics-presets .btn:hover { border-color: #aaa392; background: #f1ede4; }
        #main-container .statistics-presets .btn-primary { border-color: var(--stats-primary); color: #fff; background: var(--stats-primary); box-shadow: none; }
        .statistics-date-form { display: flex; flex: 0 0 auto; align-items: center; gap: .4rem; }
        .statistics-date-field { position: relative; display: flex; min-width: 0; align-items: stretch; }
        #main-container .statistics-date-field label { display: inline-flex; width: 2.45rem; min-width: 2.45rem; min-height: 44px; height: 44px; align-items: center; justify-content: center; margin: 0; padding: 0 .4rem; border: 1px solid #c9c4b9; border-right: 0; border-radius: .38rem 0 0 .38rem; color: #59655e; background: #f4f1ea; font-size: var(--admin-type-xs); font-weight: 800; letter-spacing: .04em; line-height: 1; text-transform: uppercase; }
        .statistics-metric-select label { display: block; margin-bottom: .25rem; color: #747970; font-size: var(--admin-type-xs); font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
        #main-container .statistics-date-field .form-control { width: 132px; min-width: 132px; min-height: 44px; height: 44px; padding-right: 2rem; border-color: #c9c4b9; border-radius: 0 .38rem .38rem 0; background: #fff; color: #3f4942; font-weight: 600; }
        #main-container .statistics-date-field .form-control:focus { z-index: 1; border-color: #789284; box-shadow: inset 0 0 0 1px #789284; }
        .statistics-date-icon { position: absolute; z-index: 2; top: 50%; right: .65rem; color: #738077; font-size: .82rem; transform: translateY(-50%); pointer-events: none; }
        #main-container .statistics-apply-button { display: inline-flex; min-width: 108px; min-height: 44px; height: 44px; align-items: center; justify-content: center; gap: .45rem; border-color: var(--stats-primary); border-radius: .4rem; background: var(--stats-primary); font-weight: 700; white-space: nowrap; }
        #main-container .statistics-apply-button:hover, #main-container .statistics-apply-button:focus { border-color: #243f33; background: #243f33; }
        .statistics-apply-button i { font-size: .68rem; transition: transform .15s ease; }
        .statistics-apply-button:hover i { transform: translateX(2px); }
        .statistics-loading { display: flex; justify-content: center; align-items: center; min-height: 240px; color: var(--stats-muted); font-weight: 600; }
        .statistics-kpi-grid { display: grid; overflow: hidden; margin: 0 0 1rem; border: 1px solid var(--stats-line); border-radius: .55rem; background: #fff; box-shadow: none; grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .statistics-kpi-grid > div { display: block; width: auto; max-width: none; padding: 0; }
        .statistics-kpi { min-width: 0; height: 100%; margin: 0 !important; overflow: hidden; border: 0 !important; border-right: 1px solid var(--stats-line) !important; border-bottom: 1px solid var(--stats-line) !important; border-radius: 0 !important; background: #fff; box-shadow: none !important; }
        .statistics-kpi-grid > div:nth-child(3n) .statistics-kpi { border-right: 0 !important; }
        .statistics-kpi-grid > div:nth-child(n+4) .statistics-kpi { border-bottom: 0 !important; }
        .statistics-kpi .block-content, .statistics-kpi-secondary .block-content { padding: .9rem 1rem !important; }
        .statistics-kpi-header { display: flex; align-items: center; gap: .55rem; color: #6d756e; font-size: var(--admin-type-xs); font-weight: 800; letter-spacing: .035em; text-transform: uppercase; }
        .statistics-kpi-icon { display: inline-flex; width: 31px; height: 31px; flex: 0 0 auto; align-items: center; justify-content: center; border-radius: 50%; background: #edf1eb; color: var(--stats-primary); }
        .statistics-kpi-success .statistics-kpi-icon { color: #41644f; background: #edf3ee; } .statistics-kpi-info .statistics-kpi-icon { color: #55706a; background: #eef2f0; } .statistics-kpi-warning .statistics-kpi-icon { color: #946b34; background: #f7f0e4; } .statistics-kpi-purple .statistics-kpi-icon { color: #725c74; background: #f2edf2; } .statistics-kpi-slate .statistics-kpi-icon { color: #657069; background: #eef0ed; }
        .statistics-kpi-value, .statistics-kpi-featured .statistics-kpi-value, .statistics-kpi-secondary .statistics-kpi-value { margin-top: .55rem; color: var(--stats-ink); font-size: var(--admin-type-display); font-weight: 700; line-height: 1.1; }
        .statistics-kpi-change { min-height: 1rem; margin-top: .32rem; color: #7b827b; font-size: var(--admin-type-xs); }
        .statistics-change-up { color: var(--stats-success); } .statistics-change-down { color: #c34141; }
        .statistics-tabs-shell { margin-bottom: .35rem; padding: .38rem; overflow-x: auto; border: 1px solid #d8d1c5; border-radius: .5rem; background: #f7f4ed; box-shadow: none; }
        .statistics-tabs { width: max-content; min-width: 100%; gap: .38rem; border: 0; flex-wrap: nowrap; }
        .statistics-tabs .nav-item { min-width: 10rem; flex: 1 0 auto; }
        #statistics-tabs.statistics-tabs .nav-link { display: flex; min-height: 46px; align-items: center; justify-content: center; padding: .62rem 1rem; white-space: nowrap; border: 1px solid transparent; border-radius: .34rem; color: #536159; background: transparent; font-size: .9rem; font-weight: 750; line-height: 1.2; transition: color .15s ease, background-color .15s ease, border-color .15s ease; }
        #statistics-tabs.statistics-tabs .nav-link i { margin-right: .55rem !important; color: #7d8b82; font-size: 1.05rem; transition: color .15s ease; }
        #statistics-tabs.statistics-tabs .nav-link:hover { border-color: #d6d0c5; color: #294c3e; background: #ede9df; }
        #statistics-tabs.statistics-tabs .nav-link:hover i { color: #496d5d; }
        #statistics-tabs.statistics-tabs .nav-link:focus-visible { outline: 3px solid rgba(49, 83, 68, .2); outline-offset: 1px; }
        #statistics-tabs.statistics-tabs .nav-link.active { border-color: var(--stats-primary); color: #fff; background: var(--stats-primary); box-shadow: none; }
        #statistics-tabs.statistics-tabs .nav-link.active i { color: #f1d6a9; }
        .statistics-block-subtitle { color: #7c837c; font-size: var(--admin-type-sm); }
        #statistics-results .tab-content .block { border: 1px solid var(--stats-line); border-radius: .55rem; box-shadow: none; }
        #statistics-results .tab-content .block-header-default { min-height: 3.75rem; padding-right: 1rem; padding-left: 1rem; border-bottom: 1px solid #e6e0d5; background: #faf8f3; }
        #statistics-results .tab-content .block-title { color: var(--stats-ink); font-size: var(--admin-type-title) !important; font-weight: 700; line-height: 1.35; }
        #statistics-results .table thead th { border-top: 0; border-bottom: 1px solid #e2dccf; background: #f7f4ed; color: #6f786f; font-size: var(--admin-type-xs); font-weight: 800; letter-spacing: .035em; text-transform: uppercase; }
        #statistics-results .table tbody td { border-top-color: #ebe6dc; }
        #statistics-results .table tbody { color: #3f4942; font-size: .88rem; }
        .statistics-section-toolbar h2 { color: var(--stats-ink); font-size: 1.05rem !important; }
        .statistics-products-primary-row > [class*=col-] { display: flex; }
        .statistics-products-primary-row .statistics-scroll-block { display: flex; width: 100%; height: 460px; flex-direction: column; }
        .statistics-scroll-block-content { min-height: 0; flex: 1 1 auto; }
        .statistics-products-table-wrap { height: 100%; overflow-y: auto; scrollbar-color: #c9c2b5 transparent; scrollbar-width: thin; }
        .statistics-products-table-wrap thead th { position: sticky; z-index: 2; top: 0; }
        .statistics-products-ranking-scroll { min-height: 0; overflow-y: auto; flex: 1 1 auto; scrollbar-color: #c9c2b5 transparent; scrollbar-width: thin; }
        .statistics-chart { position: relative; height: 360px; }
        .statistics-customer-chart { position: relative; height: 250px; }
        .statistics-mini-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .7rem; margin: 1rem 0; }
        .statistics-mini-grid div { padding: .7rem; border-radius: .45rem; background: #f7f4ed; text-align: center; }
        .statistics-mini-grid strong, .statistics-mini-grid span { display: block; } .statistics-mini-grid strong { font-size: 1.25rem; color: var(--stats-ink); } .statistics-mini-grid span { color: #7c837c; font-size: var(--admin-type-sm); }
        .statistics-heatmap-wrap { overflow-x: auto; padding-bottom: .6rem; }
        .statistics-heatmap { display: grid; grid-template-columns: 72px repeat(24, minmax(25px, 1fr)); gap: 3px; min-width: 820px; }
        .statistics-heatmap-label { display: flex; align-items: center; color: #66758a; font-size: var(--admin-type-sm); font-weight: 600; }
        .statistics-heatmap-hour { text-align: center; color: #8c9aad; font-size: var(--admin-type-xs); }
        .statistics-heatmap-cell { height: 26px; border-radius: 4px; background: #ede9df; cursor: default; transition: transform .12s ease; }
        .statistics-heatmap-cell:hover { transform: scale(1.12); outline: 2px solid rgba(49,83,68,.2); }
        .statistics-section-toolbar { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; margin-bottom: 1rem; }
        .statistics-metric-select .form-control { min-width: 190px; }
        .statistics-map-switch { display: flex; gap: .45rem; margin-bottom: 1rem; }
        .statistics-map-switch .btn { min-width: 120px; min-height: 40px; border: 1px solid #ddd7cc; border-radius: .42rem; color: #4f5951; background: #faf8f3; font-weight: 700; }
        .statistics-map-switch .btn-primary { border-color: var(--stats-primary); color: #fff; background: var(--stats-primary); box-shadow: none; }
        .statistics-map-stage { position: relative; min-height: 510px; background: linear-gradient(145deg, #f8f6f0, #ece8df); }
        .statistics-map { height: 510px; background: transparent; }
        .statistics-croatia-map { display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 1rem; }
        .statistics-croatia-map svg { display: block; width: 100%; height: 100%; overflow: hidden; cursor: grab; touch-action: none; user-select: none; }
        .statistics-croatia-map svg:active { cursor: grabbing; }
        .statistics-croatia-map svg:focus-visible { outline: 3px solid rgba(49,83,68,.3); outline-offset: -3px; }
        .statistics-croatia-controls { position: absolute; z-index: 4; top: .8rem; left: .8rem; display: flex; align-items: center; overflow: hidden; border: 1px solid #d9d3c7; border-radius: .4rem; background: #fff; box-shadow: none; }
        .statistics-croatia-controls button { display: inline-flex; width: 34px; height: 34px; align-items: center; justify-content: center; padding: 0; border: 0; border-right: 1px solid #e6e0d5; background: #fff; color: #3f4c44; font-size: .85rem; font-weight: 700; }
        .statistics-croatia-controls button:hover, .statistics-croatia-controls button:focus { background: #edf1eb; color: var(--stats-primary); outline: 0; }
        .statistics-croatia-controls button:disabled { color: #adb8c5; cursor: not-allowed; }
        .statistics-croatia-controls span { min-width: 48px; padding: 0 .55rem; color: #66758a; font-size: var(--admin-type-xs); font-weight: 700; text-align: center; }
        .statistics-croatia-help { position: absolute; z-index: 3; bottom: .75rem; left: .8rem; padding: .4rem .6rem; border: 1px solid #e3ddd1; border-radius: .3rem; background: rgba(255,255,255,.94); color: #727c74; font-size: var(--admin-type-xs); box-shadow: none; pointer-events: none; }
        .statistics-county { fill: #dce4dc; stroke: #faf8f3; stroke-width: 1.2; vector-effect: non-scaling-stroke; transition: fill .15s ease, opacity .15s ease; }
        .statistics-county:hover { fill: #bdcdbf; }
        .statistics-city-marker { fill: #a5793d; stroke: #fff; stroke-width: 2; vector-effect: non-scaling-stroke; cursor: pointer; transition: fill .15s ease, opacity .15s ease; }
        .statistics-city-marker:hover { fill: #745022; opacity: 1; }
        .statistics-city-label { fill: #344039; font-size: 12px; font-weight: 700; paint-order: stroke; stroke: #fff; stroke-width: 3px; stroke-linejoin: round; pointer-events: none; }
        .statistics-map-attribution { padding: .45rem .75rem; border-top: 1px solid #e6e0d5; background: #faf8f3; color: #8a8d86; font-size: var(--admin-type-xs); text-align: right; }
        .statistics-ranking-scroll { max-height: 550px; overflow-y: auto; padding: .65rem 1rem !important; scrollbar-color: #c9c2b5 transparent; scrollbar-width: thin; }
        .statistics-ranking-row { display: grid; grid-template-columns: minmax(0,1fr) auto; gap: .75rem; align-items: center; padding: .72rem 0; border-bottom: 1px solid #e9e4da; }
        .statistics-ranking-row:last-child { border-bottom: 0; }
        .statistics-ranking-main { min-width: 0; } .statistics-ranking-name { display: block; overflow: hidden; color: #3c4941; font-weight: 650; text-overflow: ellipsis; white-space: nowrap; }
        .statistics-ranking-meta { color: #858a83; font-size: var(--admin-type-sm); }
        .statistics-ranking-value { color: var(--stats-ink); font-weight: 700; white-space: nowrap; text-align: right; }
        .statistics-ranking-row-compact { gap: .55rem; padding: .58rem 0; }
        .statistics-ranking-row-compact .statistics-ranking-name, .statistics-ranking-row-compact .statistics-ranking-value { font-size: .9rem; }
        .statistics-ranking-row-compact .statistics-ranking-meta { font-size: var(--admin-type-xs); }
        .statistics-ranking-bar { height: 4px; margin-top: .4rem; overflow: hidden; border-radius: 4px; background: #ebe7dd; } .statistics-ranking-bar span { display: block; height: 100%; border-radius: 4px; background: var(--stats-primary); }
        .statistics-empty { padding: 2.5rem 1rem; color: #8a98aa; text-align: center; }
        .statistics-small-card { min-width: 100%; overflow: hidden; border: 1px solid var(--stats-line); background: #fff; }
        .statistics-small-card span, .statistics-small-card strong, .statistics-small-card small { display: block; } .statistics-small-card span { color: #66758a; font-size: var(--admin-type-xs); font-weight: 700; text-transform: uppercase; } .statistics-small-card strong { margin: .5rem 0 .2rem; color: #263548; font-size: 1.9rem; } .statistics-small-card small { color: #8795a7; font-size: var(--admin-type-sm); }
        .statistics-insight { display: flex; align-items: center; gap: .85rem; margin-bottom: 1.5rem; padding: 1rem 1.2rem; border: 1px solid #f1d6aa; border-radius: .5rem; background: #fff8ed; color: #6e562e; }
        .statistics-insight i { color: var(--stats-warning); font-size: 1.4rem; }
        .statistics-status-badge { display: inline-block; margin-top: .25rem; padding: .2rem .42rem; border-radius: .25rem; background: #eef8f2; color: #2f8256; font-size: var(--admin-type-xs); font-weight: 700; } .statistics-status-badge-muted { background: #f1f3f6; color: #77869a; }
        @media (max-width: 1199.98px) { .statistics-filter-body { overflow-x: auto; padding-bottom: .15rem; } .statistics-date-form { justify-content: flex-start; } .statistics-products-primary-row .statistics-scroll-block { height: 420px; } }
        @media (max-width: 991.98px) { .statistics-filter-body { align-items: stretch; flex-direction: column; overflow: visible; } .statistics-presets { width: 100%; overflow-x: auto; padding-bottom: .15rem; } .statistics-date-form { width: 100%; flex-wrap: nowrap; } .statistics-date-field { flex: 1 1 50%; } .statistics-date-field .form-control { width: 100%; min-width: 0; } .statistics-kpi-grid { grid-template-columns: repeat(2, minmax(0,1fr)); } .statistics-kpi-grid > div .statistics-kpi { border-right: 1px solid var(--stats-line) !important; border-bottom: 1px solid var(--stats-line) !important; } .statistics-kpi-grid > div:nth-child(2n) .statistics-kpi { border-right: 0 !important; } .statistics-kpi-grid > div:nth-child(n+5) .statistics-kpi { border-bottom: 0 !important; } .statistics-section-toolbar { align-items: stretch; flex-direction: column; } .statistics-metric-select { width: 100%; } .statistics-metric-select .form-control { width: 100%; } }
        @media (max-width: 575.98px) { .statistics-hero h1 { font-size: 1.72rem; } .statistics-period-label { min-width: 0; width: 100%; } .statistics-date-form { display: grid; width: 100%; min-width: 0; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } .statistics-date-field { min-width: 0; } .statistics-date-field .form-control { width: 100%; max-width: 100%; min-width: 0; } .statistics-apply-button { width: 100%; max-width: 100%; min-width: 0; grid-column: 1 / -1; } .statistics-kpi-grid { grid-template-columns: minmax(0,1fr); } .statistics-kpi-grid > div .statistics-kpi { border-right: 0 !important; border-bottom: 1px solid var(--stats-line) !important; } .statistics-kpi-grid > div:last-child .statistics-kpi { border-bottom: 0 !important; } .statistics-tabs-shell { margin-right: -.1rem; margin-left: -.1rem; overflow: visible; } .statistics-tabs { display: grid; width: 100%; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .3rem; } .statistics-tabs .nav-item { min-width: 0; } .statistics-tabs .nav-link { min-height: 42px; padding: .45rem .35rem; white-space: normal; text-align: center; } .statistics-map-switch { display: grid; grid-template-columns: repeat(3, 1fr); } .statistics-map-switch .btn { min-width: 0; padding-right: .45rem; padding-left: .45rem; } .statistics-map-stage { min-height: 370px; } .statistics-map { height: 370px; } .statistics-croatia-help { display: none; } .statistics-kpi-value, .statistics-kpi-featured .statistics-kpi-value, .statistics-kpi-secondary .statistics-kpi-value { font-size: 1.58rem; } }
    </style>
@endpush

@push('js_after')
    <script src="{{ \App\Helpers\Asset::url('js/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js') }}"></script>
    <script src="{{ \App\Helpers\Asset::url('js/plugins/bootstrap-datepicker/locales/bootstrap-datepicker.hr.min.js') }}"></script>
    <script src="{{ \App\Helpers\Asset::url('js/plugins/chart.js/Chart.bundle.min.js') }}"></script>
    <script src="{{ \App\Helpers\Asset::url('js/plugins/jvectormap/dist/jquery-jvectormap.min.js') }}"></script>
    <script src="{{ \App\Helpers\Asset::url('js/plugins/jvectormap/maps/jquery-jvectormap-world-mill-en.js') }}"></script>
    <script src="{{ \App\Helpers\Asset::url('js/plugins/jvectormap/maps/jquery-jvectormap-europe-mill-en.js') }}"></script>
    <script>
        (() => {
            'use strict';

            const endpoint = @json(route('statistics.data'));
            const croatiaGeoJsonUrl = @json(asset('assets/croatia-counties.geojson'));
            const latestStatisticsDate = localDate(@json($latestStatisticsDate->toDateString()));
            const currentYear = {{ Carbon\Carbon::today()->year }};
            const fmtInteger = new Intl.NumberFormat('hr-HR', { maximumFractionDigits: 0 });
            const fmtDecimal = new Intl.NumberFormat('hr-HR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const fmtCurrency = new Intl.NumberFormat('hr-HR', { style: 'currency', currency: 'EUR' });
            const fmtDate = new Intl.DateTimeFormat('hr-HR', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const regionNames = typeof Intl.DisplayNames === 'function' ? new Intl.DisplayNames(['hr'], { type: 'region' }) : null;
            const metricLabels = { total: 'Promet', orders: 'Narudžbe', items: 'Artikli', average_order: 'Prosjek narudžbe' };
            const weekDays = ['Ponedjeljak', 'Utorak', 'Srijeda', 'Četvrtak', 'Petak', 'Subota', 'Nedjelja'];
            const europeanCountryCodes = new Set(['AL','AD','AT','BY','BE','BA','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IS','IE','IT','XK','LV','LI','LT','LU','MT','MD','MC','ME','NL','MK','NO','PL','PT','RO','RU','SM','RS','SK','SI','ES','SE','CH','TR','UA','GB','VA']);
            let statistics = null;
            let trendChart = null;
            let customerChart = null;
            let worldMap = null;
            let europeMap = null;
            let croatiaGeoJson = null;
            let croatiaMapViewport = { x: 0, y: 0, width: 920, height: 520 };
            let currentLocationMap = 'croatia';
            let requestId = 0;

            const fromInput = document.getElementById('statistics-from');
            const toInput = document.getElementById('statistics-to');
            const fromDisplay = $('#statistics-from-display');
            const toDisplay = $('#statistics-to-display');

            const datepickerOptions = {
                format: 'dd.mm.yyyy',
                language: 'hr',
                weekStart: 1,
                autoclose: true,
                todayHighlight: true,
                endDate: latestStatisticsDate,
                orientation: 'bottom auto',
                templates: {
                    leftArrow: '<i class="fa-solid fa-chevron-left" aria-hidden="true"></i>',
                    rightArrow: '<i class="fa-solid fa-chevron-right" aria-hidden="true"></i>'
                }
            };

            fromDisplay.datepicker(datepickerOptions).on('changeDate', event => {
                fromInput.value = isoDate(event.date);
            });
            toDisplay.datepicker(datepickerOptions).on('changeDate', event => {
                toInput.value = isoDate(event.date);
            });

            function updateDateField(hiddenInput, displayInput, date) {
                hiddenInput.value = isoDate(date);
                displayInput.datepicker('update', date);
            }

            updateDateField(fromInput, fromDisplay, localDate(fromInput.value));
            updateDateField(toInput, toDisplay, localDate(toInput.value));

            function metricValue(row, metric) {
                const value = Number(row && row[metric]) || 0;
                return metric === 'total' || metric === 'average_order' ? fmtCurrency.format(value) : fmtInteger.format(value);
            }

            function countLabel(value, forms) {
                const number = Math.abs(Number(value) || 0);
                const lastTwo = number % 100;
                const last = number % 10;
                const form = last === 1 && lastTwo !== 11 ? forms[0] : (last >= 2 && last <= 4 && (lastTwo < 12 || lastTwo > 14) ? forms[1] : forms[2]);
                return `${fmtInteger.format(number)} ${form}`;
            }

            function countryDisplayName(row) {
                if (!row || !row.code || !regionNames) return row && row.name ? row.name : 'Nepoznato';
                return regionNames.of(row.code) || row.name;
            }

            function localDate(dateString) {
                return new Date(dateString + 'T12:00:00');
            }

            function isoDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            function setLoading(active) {
                document.getElementById('statistics-loading').classList.toggle('d-none', !active);
                document.getElementById('statistics-results').classList.toggle('d-none', active);
                document.querySelector('.statistics-apply-button').disabled = active;
            }

            function setError(message) {
                const error = document.getElementById('statistics-error');
                error.classList.toggle('d-none', !message);
                if (message) error.querySelector('span').textContent = message;
            }

            function loadStatistics() {
                const currentRequest = ++requestId;
                setError(null);
                setLoading(true);

                $.get(endpoint, { from: fromInput.value, to: toInput.value })
                    .done(data => {
                        if (currentRequest !== requestId) return;
                        statistics = data;
                        renderAll(data);
                    })
                    .fail(xhr => {
                        if (currentRequest !== requestId) return;
                        const validation = xhr.responseJSON && xhr.responseJSON.errors;
                        const message = validation
                            ? Object.values(validation)[0][0]
                            : 'Statistike trenutno nije moguće učitati. Pokušajte ponovno.';
                        setError(message);
                    })
                    .always(() => {
                        if (currentRequest === requestId) setLoading(false);
                    });
            }

            function renderAll(data) {
                renderPeriod(data.period);
                renderSummary(data.summary, data.comparison);
                renderTrend(data.trend);
                renderCustomers(data.customers);
                renderHeatmap(data.heatmap);
                renderGeography(data.geography);
                renderProducts(data.products);
                renderOperations(data.operations);
            }

            function renderPeriod(period) {
                const from = fmtDate.format(localDate(period.from));
                const to = fmtDate.format(localDate(period.to));
                document.getElementById('statistics-period-label').textContent = `${from} – ${to} · ${fmtInteger.format(period.days)} dana`;
            }

            function renderSummary(summary, comparison) {
                const values = {
                    total: fmtCurrency.format(Number(summary.total) || 0),
                    orders: fmtInteger.format(Number(summary.orders) || 0),
                    items: fmtInteger.format(Number(summary.items) || 0),
                    average_order: fmtCurrency.format(Number(summary.average_order) || 0),
                    average_items: fmtDecimal.format(Number(summary.average_items) || 0),
                    customers: fmtInteger.format(Number(summary.customers) || 0)
                };

                Object.entries(values).forEach(([key, value]) => {
                    document.getElementById('kpi-' + key).textContent = value;
                    const element = document.getElementById('change-' + key);
                    const change = comparison[key] && comparison[key].change;
                    element.className = 'statistics-kpi-change';
                    if (change === null) {
                        element.textContent = 'Nema prethodnih podataka za usporedbu';
                    } else {
                        const numeric = Number(change) || 0;
                        element.textContent = `${numeric > 0 ? '▲' : (numeric < 0 ? '▼' : '•')} ${fmtDecimal.format(Math.abs(numeric))}% prema prethodnom razdoblju`;
                        if (numeric > 0) element.classList.add('statistics-change-up');
                        if (numeric < 0) element.classList.add('statistics-change-down');
                    }
                });
            }

            function renderTrend(trend) {
                if (trendChart) trendChart.destroy();
                const series = trend.series || [];
                trendChart = new Chart(document.getElementById('statistics-trend-chart').getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: series.map(row => row.label),
                        datasets: [
                            { label: 'Promet', data: series.map(row => row.total), borderColor: '#315344', backgroundColor: 'rgba(49,83,68,.10)', borderWidth: 2, pointRadius: series.length > 60 ? 0 : 2, fill: true, lineTension: 0, yAxisID: 'money' },
                            { label: 'Narudžbe', data: series.map(row => row.orders), borderColor: '#a5793d', backgroundColor: 'transparent', borderWidth: 2, pointRadius: series.length > 60 ? 0 : 2, fill: false, lineTension: 0, yAxisID: 'count' },
                            { label: 'Artikli', data: series.map(row => row.items), borderColor: '#725c74', backgroundColor: 'transparent', borderWidth: 1.5, borderDash: [5,4], pointRadius: 0, fill: false, lineTension: 0, yAxisID: 'count' }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        legend: { labels: { usePointStyle: true, boxWidth: 8 } },
                        tooltips: { mode: 'index', intersect: false, callbacks: { label: (item, data) => data.datasets[item.datasetIndex].yAxisID === 'money' ? `${data.datasets[item.datasetIndex].label}: ${fmtCurrency.format(item.yLabel)}` : `${data.datasets[item.datasetIndex].label}: ${fmtInteger.format(item.yLabel)}` } },
                        scales: { xAxes: [{ gridLines: { display: false }, ticks: { maxTicksLimit: 16 } }], yAxes: [{ id: 'money', position: 'left', ticks: { beginAtZero: true, callback: value => fmtInteger.format(value) + ' €' } }, { id: 'count', position: 'right', gridLines: { drawOnChartArea: false }, ticks: { beginAtZero: true, precision: 0 } }] }
                    }
                });
            }

            function renderCustomers(customers) {
                ['new', 'returning', 'unique'].forEach(key => document.getElementById('customer-' + key).textContent = fmtInteger.format(customers[key] || 0));
                document.getElementById('customer-repeat-rate').textContent = fmtDecimal.format(customers.repeat_rate || 0) + '%';
                document.getElementById('customer-repeat-count').textContent = fmtInteger.format(customers.repeat || 0) + ' kupaca';
                document.getElementById('customer-registered').textContent = fmtInteger.format(customers.registered_orders || 0);
                document.getElementById('customer-guests').textContent = fmtInteger.format(customers.guest_orders || 0);

                if (customerChart) customerChart.destroy();
                customerChart = new Chart(document.getElementById('statistics-customer-chart').getContext('2d'), {
                    type: 'doughnut',
                    data: { labels: ['Novi', 'Povratni'], datasets: [{ data: [customers.new || 0, customers.returning || 0], backgroundColor: ['#55706a', '#a5793d'], borderWidth: 0 }] },
                    options: { responsive: true, maintainAspectRatio: false, cutoutPercentage: 70, legend: { display: false }, tooltips: { callbacks: { label: (item, data) => `${data.labels[item.index]}: ${fmtInteger.format(data.datasets[0].data[item.index])}` } } }
                });
            }

            function renderHeatmap(rows) {
                const container = document.getElementById('statistics-heatmap');
                const lookup = {};
                (rows || []).forEach(row => lookup[`${row.weekday}-${row.hour}`] = row);
                const max = Math.max(1, ...(rows || []).map(row => Number(row.orders) || 0));
                container.innerHTML = '';
                container.appendChild(document.createElement('div'));
                for (let hour = 0; hour < 24; hour++) {
                    const header = document.createElement('div'); header.className = 'statistics-heatmap-hour'; header.textContent = String(hour).padStart(2, '0'); container.appendChild(header);
                }
                weekDays.forEach((day, weekday) => {
                    const label = document.createElement('div'); label.className = 'statistics-heatmap-label'; label.textContent = day; container.appendChild(label);
                    for (let hour = 0; hour < 24; hour++) {
                        const row = lookup[`${weekday}-${hour}`] || { orders: 0, total: 0 };
                        const intensity = Number(row.orders) / max;
                        const cell = document.createElement('div'); cell.className = 'statistics-heatmap-cell';
                        cell.style.backgroundColor = intensity ? `rgba(49, 83, 68, ${0.12 + intensity * 0.82})` : '#ede9df';
                        cell.title = `${day}, ${String(hour).padStart(2, '0')}:00 · ${countLabel(row.orders, ['narudžba', 'narudžbe', 'narudžbi'])} · ${fmtCurrency.format(row.total)}`;
                        container.appendChild(cell);
                    }
                });
            }

            function removeMap(map) {
                if (map && typeof map.remove === 'function') map.remove();
            }

            function renderGeography(geography) {
                const metric = document.getElementById('map-metric').value;
                const countryRows = geography.countries || [];
                const isCroatia = currentLocationMap === 'croatia';
                const isEurope = currentLocationMap === 'europe';
                const rankingRows = isCroatia
                    ? (geography.cities || [])
                    : (isEurope ? countryRows.filter(row => europeanCountryCodes.has(row.code)) : countryRows)
                        .map(row => Object.assign({}, row, { name: countryDisplayName(row) }));

                document.getElementById('statistics-location-map-title').textContent = isCroatia ? 'Hrvatska' : (isEurope ? 'Europa' : 'Svijet');
                document.getElementById('statistics-location-map-subtitle').textContent = isCroatia
                    ? `Gradovi kupaca na stvarnoj karti županija · ${metricLabels[metric]}`
                    : (isEurope ? `${metricLabels[metric]} prema europskim državama` : `${metricLabels[metric]} prema državama svijeta`);
                document.getElementById('statistics-location-ranking-title').textContent = isCroatia ? 'Top gradovi' : (isEurope ? 'Top države Europe' : 'Top države svijeta');
                document.getElementById('statistics-map-attribution').classList.toggle('d-none', !isCroatia);
                document.getElementById('statistics-croatia-controls').classList.toggle('d-none', !isCroatia);
                document.getElementById('statistics-croatia-help').classList.toggle('d-none', !isCroatia);
                renderRanking('statistics-location-ranking', rankingRows.slice().sort((a,b) => Number(b[metric]) - Number(a[metric])).slice(0, 15), metric, { compact: true, showBar: false });

                ['croatia', 'europe', 'world'].forEach(mapName => {
                    document.getElementById(`statistics-${mapName}-map`).classList.toggle('d-none', mapName !== currentLocationMap);
                });

                // Vector maps need visible dimensions. Rankings can render while
                // the tab is hidden, but maps are initialized after it is shown.
                if (!$('#statistics-geography').hasClass('show')) return;

                removeMap(worldMap); removeMap(europeMap);
                worldMap = null; europeMap = null;

                if (isCroatia) {
                    renderCroatiaMap(geography.cities || [], metric);
                    return;
                }

                const mapRows = isEurope ? countryRows.filter(row => europeanCountryCodes.has(row.code)) : countryRows;
                const regionValues = {};
                mapRows.forEach(row => { if (row.code) regionValues[row.code] = Number(row[metric]) || 0; });
                const targetId = isEurope ? '#statistics-europe-map' : '#statistics-world-map';
                $(targetId).empty().vectorMap({
                    map: isEurope ? 'europe_mill_en' : 'world_mill_en', backgroundColor: '#f7f4ed', zoomOnScroll: true,
                    regionStyle: { initial: { fill: '#dce4dc', 'fill-opacity': 1, stroke: '#faf8f3', 'stroke-width': .5 }, hover: { 'fill-opacity': .8 } },
                    series: { regions: [{ values: regionValues, scale: ['#dfe8df', '#315344'], normalizeFunction: 'polynomial' }] },
                    onRegionTipShow: (event, label, code) => {
                        const row = mapRows.find(item => item.code === code);
                        if (row) label.html(`<strong>${escapeHtml(countryDisplayName(row))}</strong><br>${metricLabels[metric]}: ${metricValue(row, metric)}<br>${countLabel(row.orders, ['narudžba', 'narudžbe', 'narudžbi'])}`);
                    }
                });
                if (isEurope) europeMap = $(targetId).vectorMap('get', 'mapObject');
                else worldMap = $(targetId).vectorMap('get', 'mapObject');
            }

            function renderCroatiaMap(cities, metric) {
                const container = document.getElementById('statistics-croatia-map');
                container.innerHTML = '';
                if (!croatiaGeoJson) {
                    container.innerHTML = '<div class="statistics-empty"><i class="fa fa-circle-notch fa-spin mr-2"></i>Učitavanje karte Hrvatske…</div>';
                    return;
                }

                const svgNamespace = 'http://www.w3.org/2000/svg';
                const width = 920;
                const height = 520;
                const padding = 24;
                const bounds = { minLon: Infinity, maxLon: -Infinity, minLat: Infinity, maxLat: -Infinity };

                function visitPoints(coordinates, callback) {
                    if (Array.isArray(coordinates) && typeof coordinates[0] === 'number') {
                        callback(coordinates);
                        return;
                    }
                    (coordinates || []).forEach(part => visitPoints(part, callback));
                }

                croatiaGeoJson.features.forEach(feature => visitPoints(feature.geometry.coordinates, point => {
                    bounds.minLon = Math.min(bounds.minLon, point[0]); bounds.maxLon = Math.max(bounds.maxLon, point[0]);
                    bounds.minLat = Math.min(bounds.minLat, point[1]); bounds.maxLat = Math.max(bounds.maxLat, point[1]);
                }));

                const longitudeFactor = Math.cos(((bounds.minLat + bounds.maxLat) / 2) * Math.PI / 180);
                const sourceWidth = (bounds.maxLon - bounds.minLon) * longitudeFactor;
                const sourceHeight = bounds.maxLat - bounds.minLat;
                const scale = Math.min((width - padding * 2) / sourceWidth, (height - padding * 2) / sourceHeight);
                const offsetX = (width - sourceWidth * scale) / 2;
                const offsetY = (height - sourceHeight * scale) / 2;
                const project = point => [
                    offsetX + (point[0] - bounds.minLon) * longitudeFactor * scale,
                    offsetY + (bounds.maxLat - point[1]) * scale
                ];

                function ringPath(ring) {
                    return ring.map((point, index) => {
                        const projected = project(point);
                        return `${index ? 'L' : 'M'}${projected[0].toFixed(1)},${projected[1].toFixed(1)}`;
                    }).join(' ') + ' Z';
                }

                function geometryPath(geometry) {
                    if (geometry.type === 'Polygon') return geometry.coordinates.map(ringPath).join(' ');
                    return geometry.coordinates.map(polygon => polygon.map(ringPath).join(' ')).join(' ');
                }

                const svg = document.createElementNS(svgNamespace, 'svg');
                svg.setAttribute('viewBox', `${croatiaMapViewport.x} ${croatiaMapViewport.y} ${croatiaMapViewport.width} ${croatiaMapViewport.height}`);
                svg.setAttribute('role', 'img');
                svg.setAttribute('aria-label', 'Interaktivna karta Hrvatske s gradovima kupaca');
                svg.setAttribute('aria-describedby', 'statistics-croatia-help');
                svg.setAttribute('tabindex', '0');

                croatiaGeoJson.features.forEach(feature => {
                    const path = document.createElementNS(svgNamespace, 'path');
                    path.setAttribute('d', geometryPath(feature.geometry));
                    path.setAttribute('fill-rule', 'evenodd');
                    path.setAttribute('class', 'statistics-county');
                    const title = document.createElementNS(svgNamespace, 'title');
                    title.textContent = feature.properties.shapeName;
                    path.appendChild(title); svg.appendChild(path);
                });

                const markerCities = cities.filter(city => Array.isArray(city.lat_lng));
                const maximum = Math.max(1, ...markerCities.map(city => Number(city[metric]) || 0));
                const sortedCities = markerCities.slice().sort((a, b) => Number(b[metric]) - Number(a[metric]));
                sortedCities.forEach((city, index) => {
                    const projected = project([city.lat_lng[1], city.lat_lng[0]]);
                    const value = Number(city[metric]) || 0;
                    const circle = document.createElementNS(svgNamespace, 'circle');
                    circle.setAttribute('cx', projected[0]); circle.setAttribute('cy', projected[1]);
                    circle.setAttribute('r', 4 + Math.sqrt(value / maximum) * 10);
                    circle.setAttribute('class', 'statistics-city-marker');
                    circle.setAttribute('opacity', .78);
                    const title = document.createElementNS(svgNamespace, 'title');
                    title.textContent = `${city.name} — ${metricLabels[metric]}: ${metricValue(city, metric)} — ${countLabel(city.orders, ['narudžba', 'narudžbe', 'narudžbi'])}`;
                    circle.appendChild(title); svg.appendChild(circle);

                    if (index < 7) {
                        const label = document.createElementNS(svgNamespace, 'text');
                        label.setAttribute('x', projected[0] + 10); label.setAttribute('y', projected[1] - 8);
                        label.setAttribute('class', 'statistics-city-label'); label.textContent = city.name; svg.appendChild(label);
                    }
                });

                container.appendChild(svg);
                enableCroatiaMapNavigation(svg, width, height);
            }

            function enableCroatiaMapNavigation(svg, width, height) {
                const minimumWidth = width / 5;
                const aspectRatio = width / height;
                let drag = null;

                function constrainViewport(viewport) {
                    const constrainedWidth = Math.max(minimumWidth, Math.min(width, viewport.width));
                    const constrainedHeight = constrainedWidth / aspectRatio;
                    return {
                        width: constrainedWidth,
                        height: constrainedHeight,
                        x: Math.max(0, Math.min(width - constrainedWidth, viewport.x)),
                        y: Math.max(0, Math.min(height - constrainedHeight, viewport.y))
                    };
                }

                function applyViewport(viewport) {
                    croatiaMapViewport = constrainViewport(viewport);
                    svg.setAttribute('viewBox', `${croatiaMapViewport.x} ${croatiaMapViewport.y} ${croatiaMapViewport.width} ${croatiaMapViewport.height}`);
                    const level = Math.round((width / croatiaMapViewport.width) * 100);
                    document.getElementById('statistics-croatia-zoom-level').textContent = `${level}%`;
                    document.querySelector('[data-croatia-zoom="out"]').disabled = croatiaMapViewport.width >= width - .5;
                    document.querySelector('[data-croatia-zoom="in"]').disabled = croatiaMapViewport.width <= minimumWidth + .5;
                }

                function zoom(factor, clientX, clientY) {
                    const rectangle = svg.getBoundingClientRect();
                    const relativeX = clientX == null ? .5 : Math.max(0, Math.min(1, (clientX - rectangle.left) / rectangle.width));
                    const relativeY = clientY == null ? .5 : Math.max(0, Math.min(1, (clientY - rectangle.top) / rectangle.height));
                    const focusX = croatiaMapViewport.x + croatiaMapViewport.width * relativeX;
                    const focusY = croatiaMapViewport.y + croatiaMapViewport.height * relativeY;
                    const nextWidth = croatiaMapViewport.width * factor;
                    const nextHeight = nextWidth / aspectRatio;
                    applyViewport({
                        x: focusX - nextWidth * relativeX,
                        y: focusY - nextHeight * relativeY,
                        width: nextWidth,
                        height: nextHeight
                    });
                }

                document.querySelector('[data-croatia-zoom="in"]').onclick = () => zoom(.72);
                document.querySelector('[data-croatia-zoom="out"]').onclick = () => zoom(1.38);
                document.querySelector('[data-croatia-zoom="reset"]').onclick = () => applyViewport({ x: 0, y: 0, width, height });

                svg.addEventListener('wheel', event => {
                    event.preventDefault();
                    zoom(event.deltaY < 0 ? .84 : 1.19, event.clientX, event.clientY);
                }, { passive: false });
                svg.addEventListener('dblclick', event => { event.preventDefault(); zoom(.65, event.clientX, event.clientY); });
                svg.addEventListener('pointerdown', event => {
                    if (event.button !== 0 || croatiaMapViewport.width >= width) return;
                    svg.setPointerCapture(event.pointerId);
                    drag = { x: event.clientX, y: event.clientY, viewport: Object.assign({}, croatiaMapViewport) };
                });
                svg.addEventListener('pointermove', event => {
                    if (!drag) return;
                    const rectangle = svg.getBoundingClientRect();
                    applyViewport({
                        x: drag.viewport.x - (event.clientX - drag.x) * drag.viewport.width / rectangle.width,
                        y: drag.viewport.y - (event.clientY - drag.y) * drag.viewport.height / rectangle.height,
                        width: drag.viewport.width,
                        height: drag.viewport.height
                    });
                });
                const stopDragging = () => { drag = null; };
                svg.addEventListener('pointerup', stopDragging);
                svg.addEventListener('pointercancel', stopDragging);
                svg.addEventListener('keydown', event => {
                    const step = croatiaMapViewport.width * .08;
                    if (event.key === '+' || event.key === '=') zoom(.72);
                    else if (event.key === '-') zoom(1.38);
                    else if (event.key === '0') applyViewport({ x: 0, y: 0, width, height });
                    else if (event.key === 'ArrowLeft') applyViewport(Object.assign({}, croatiaMapViewport, { x: croatiaMapViewport.x - step }));
                    else if (event.key === 'ArrowRight') applyViewport(Object.assign({}, croatiaMapViewport, { x: croatiaMapViewport.x + step }));
                    else if (event.key === 'ArrowUp') applyViewport(Object.assign({}, croatiaMapViewport, { y: croatiaMapViewport.y - step }));
                    else if (event.key === 'ArrowDown') applyViewport(Object.assign({}, croatiaMapViewport, { y: croatiaMapViewport.y + step }));
                    else return;
                    event.preventDefault();
                });
                applyViewport(croatiaMapViewport);
            }

            function renderRanking(id, rows, metric, options = {}) {
                const container = document.getElementById(id);
                container.innerHTML = '';
                if (!rows.length) { container.innerHTML = '<div class="statistics-empty">Nema podataka za odabrano razdoblje.</div>'; return; }
                const max = Math.max(1, ...rows.map(row => Number(row[metric]) || 0));
                rows.forEach((row, index) => {
                    const wrapper = document.createElement('div');
                    wrapper.className = `statistics-ranking-row${options.compact ? ' statistics-ranking-row-compact' : ''}`;
                    const main = document.createElement('div'); main.className = 'statistics-ranking-main';
                    const name = row.url ? document.createElement('a') : document.createElement('span'); name.className = 'statistics-ranking-name'; name.textContent = `${index + 1}. ${row.name || row.label || 'Nepoznato'}`; if (row.url) name.href = row.url;
                    const meta = document.createElement('span'); meta.className = 'statistics-ranking-meta';
                    if (options.wishlist) meta.textContent = `${fmtInteger.format(row.stock)} na zalihi`;
                    else if (options.ordersOnly) meta.textContent = countLabel(row.orders, ['narudžba', 'narudžbe', 'narudžbi']);
                    else meta.textContent = `${countLabel(row.orders, ['narudžba', 'narudžbe', 'narudžbi'])} · ${countLabel(row.items, ['artikl', 'artikla', 'artikala'])}`;
                    main.append(name, meta);
                    if (options.showBar !== false) {
                        const bar = document.createElement('div'); bar.className = 'statistics-ranking-bar';
                        const fill = document.createElement('span'); fill.style.width = `${Math.max(2, ((Number(row[metric]) || 0) / max) * 100)}%`; bar.appendChild(fill);
                        main.appendChild(bar);
                    }
                    const value = document.createElement('div'); value.className = 'statistics-ranking-value'; value.textContent = options.wishlist ? countLabel(row.wishes, ['želja', 'želje', 'želja']) : metricValue(row, metric);
                    wrapper.append(main, value); container.appendChild(wrapper);
                });
            }

            function renderProducts(products) {
                const tbody = document.getElementById('top-products-table'); tbody.innerHTML = '';
                if (!(products.top_products || []).length) tbody.innerHTML = '<tr><td colspan="4" class="statistics-empty">Nema prodanih artikala u odabranom razdoblju.</td></tr>';
                (products.top_products || []).forEach(row => {
                    const tr = document.createElement('tr');
                    const name = document.createElement('td'); const link = document.createElement('a'); link.href = row.url; link.className = 'font-w600'; link.textContent = row.name; name.appendChild(link);
                    const items = document.createElement('td'); items.className = 'text-right'; items.textContent = fmtInteger.format(row.items);
                    const orders = document.createElement('td'); orders.className = 'text-right'; orders.textContent = fmtInteger.format(row.orders);
                    const total = document.createElement('td'); total.className = 'text-right font-w600'; total.textContent = fmtCurrency.format(row.total);
                    tr.append(name, items, orders, total); tbody.appendChild(tr);
                });
                renderRanking('category-ranking', products.categories || [], 'total');
                renderRanking('author-ranking', products.authors || [], 'total');
                renderRanking('publisher-ranking', products.publishers || [], 'total');
                renderRanking('wishlist-ranking', products.wishlist || [], 'wishes', { wishlist: true });
                const discount = products.discounts || {};
                document.getElementById('discount-insight').innerHTML = `<i class="fa fa-tags" aria-hidden="true"></i><div><strong>Popusti u odabranom razdoblju</strong><br><span>${fmtInteger.format(discount.orders || 0)} narudžbi sadržavalo je snižene artikle, ukupna razlika prema izvornoj cijeni iznosi <strong>${fmtCurrency.format(discount.amount || 0)}</strong>.</span></div>`;
            }

            function renderOperations(operations) {
                renderRanking('payment-ranking', operations.payment_methods || [], 'total', { ordersOnly: true });
                renderRanking('shipping-ranking', operations.shipping_methods || [], 'total', { ordersOnly: true });
                const container = document.getElementById('status-ranking'); container.innerHTML = '';
                const rows = operations.statuses || [];
                if (!rows.length) { container.innerHTML = '<div class="statistics-empty">Nema narudžbi u odabranom razdoblju.</div>'; return; }
                const max = Math.max(1, ...rows.map(row => Number(row.orders) || 0));
                rows.forEach(row => {
                    const wrapper = document.createElement('div'); wrapper.className = 'statistics-ranking-row';
                    const main = document.createElement('div'); main.className = 'statistics-ranking-main';
                    const name = document.createElement('span'); name.className = 'statistics-ranking-name'; name.textContent = row.label;
                    const badge = document.createElement('span'); badge.className = 'statistics-status-badge' + (row.included_in_sales ? '' : ' statistics-status-badge-muted'); badge.textContent = row.included_in_sales ? 'U prometu' : 'Izvan prometa';
                    const bar = document.createElement('div'); bar.className = 'statistics-ranking-bar'; const fill = document.createElement('span'); fill.style.width = `${Math.max(2, (Number(row.orders) / max) * 100)}%`; bar.appendChild(fill); main.append(name, badge, bar);
                    const value = document.createElement('div'); value.className = 'statistics-ranking-value'; value.textContent = fmtInteger.format(row.orders); wrapper.append(main, value); container.appendChild(wrapper);
                });
            }

            function escapeHtml(value) {
                const element = document.createElement('div'); element.textContent = value == null ? '' : String(value); return element.innerHTML;
            }

            document.getElementById('statistics-date-form').addEventListener('submit', event => {
                event.preventDefault();
                const typedFrom = fromDisplay.datepicker('getDate');
                const typedTo = toDisplay.datepicker('getDate');
                if (typedFrom) fromInput.value = isoDate(typedFrom);
                if (typedTo) toInput.value = isoDate(typedTo);
                loadStatistics();
            });
            document.getElementById('statistics-retry').addEventListener('click', loadStatistics);
            document.getElementById('map-metric').addEventListener('change', () => { if (statistics) renderGeography(statistics.geography); });
            document.querySelectorAll('[data-location-map]').forEach(button => button.addEventListener('click', () => {
                currentLocationMap = button.dataset.locationMap;
                document.querySelectorAll('[data-location-map]').forEach(item => item.className = 'btn btn-light');
                button.className = 'btn btn-primary';
                if (statistics) renderGeography(statistics.geography);
            }));
            document.querySelectorAll('.statistics-presets button').forEach(button => button.addEventListener('click', () => {
                let to = localDate(toInput.value || isoDate(latestStatisticsDate));
                let from = new Date(to);
                if (button.dataset.days) {
                    to = new Date(latestStatisticsDate);
                    from = new Date(to);
                    from.setDate(to.getDate() - Number(button.dataset.days) + 1);
                    updateDateField(toInput, toDisplay, to);
                }
                if (button.dataset.preset === 'year') { from = new Date(currentYear, 0, 1); updateDateField(toInput, toDisplay, latestStatisticsDate); }
                if (button.dataset.preset === 'previous-year') { const year = currentYear - 1; from = new Date(year, 0, 1); updateDateField(toInput, toDisplay, new Date(year, 11, 31)); }
                updateDateField(fromInput, fromDisplay, from);
                document.querySelectorAll('.statistics-presets button').forEach(item => item.className = 'btn btn-sm btn-light'); button.className = 'btn btn-sm btn-primary';
                loadStatistics();
            }));
            $('#statistics-tabs a[data-toggle="tab"]').on('shown.bs.tab', event => {
                if (event.target.getAttribute('href') === '#statistics-geography' && statistics) renderGeography(statistics.geography);
                if (trendChart) trendChart.resize(); if (customerChart) customerChart.resize();
            });

            $.getJSON(croatiaGeoJsonUrl)
                .done(data => { croatiaGeoJson = data; if (statistics && currentLocationMap === 'croatia') renderGeography(statistics.geography); })
                .fail(() => { const map = document.getElementById('statistics-croatia-map'); if (map) map.innerHTML = '<div class="statistics-empty">Kartu Hrvatske trenutno nije moguće učitati.</div>'; });
            loadStatistics();
        })();
    </script>
@endpush
