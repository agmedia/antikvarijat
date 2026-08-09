@extends('back.layouts.backend')

@push('css_before')
    <script>
        try {
            if (window.localStorage.getItem('admin.products.filters.open') === '0') {
                document.documentElement.classList.add('admin-product-filters-closed');
            }
        } catch (e) {}
    </script>
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">

    <!-- Page JS Plugins CSS -->
    <link rel="stylesheet" href="{{ asset('js/plugins/magnific-popup/magnific-popup.css') }}">
@endpush

@section('content')

    <div class="admin-page-hero">
        <div class="content content-full">
            <div class="admin-page-heading">
                <div>
                    <div class="admin-page-kicker"><i class="fa-duotone fa-books"></i> Katalog</div>
                    <h1 class="admin-page-title">Artikli</h1>
                    <p class="admin-page-description">Pretražite, filtrirajte i uređujte bibliografske podatke, zalihe i dostupnost knjiga.</p>
                </div>
                <div class="admin-page-actions">
                    <a class="btn btn-primary" href="{{ route('products.create') }}">
                        <i class="fa-duotone fa-plus mr-1" aria-hidden="true"></i> Novi artikl
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
    @include('back.layouts.partials.session')

    <!-- All Products -->
        <div class="block block-rounded">
            @php
                $activeFilterCount = collect(['search', 'category', 'author', 'publisher', 'status', 'sort'])
                    ->filter(fn ($key) => request()->filled($key))
                    ->count();
                $filtersInitiallyOpen = true;
            @endphp
            <div class="block-header block-header-default admin-products-header">
                <div class="d-flex align-items-center min-width-0">
                    <span class="admin-section-icon mr-3"><i class="fa-duotone fa-shelves"></i></span>
                    <div>
                        <h2 class="block-title mb-1">Svi artikli</h2>
                        <span class="admin-count">{{ number_format($products->total(), 0, ',', '.') }} artikala</span>
                    </div>
                </div>
                <div class="block-options">
                    <div class="admin-toolbar-group justify-content-end">

                        <a href="{{ route('products.export.zero') }}" class="btn btn-outline-primary">
                            <i class="fa-duotone fa-file-excel mr-1"></i> <span class="d-none d-xl-inline">Izvoz artikala bez zalihe</span><span class="d-xl-none">Izvoz</span>
                        </a>
                        <button class="btn btn-outline-primary admin-filter-toggle" type="button" data-toggle="collapse" data-target="#productFiltersPanel" aria-expanded="{{ $filtersInitiallyOpen ? 'true' : 'false' }}" aria-controls="productFiltersPanel">
                            <i class="fa-duotone fa-filter-list mr-1"></i>
                            <span>Filteri</span>
                            @if($activeFilterCount)
                                <span class="admin-filter-count" aria-label="{{ $activeFilterCount }} aktivnih filtera">{{ $activeFilterCount }}</span>
                            @endif
                            <i class="fa fa-angle-down admin-filter-chevron ml-1" aria-hidden="true"></i>
                        </button>
                        @if($activeFilterCount)
                            <a class="btn btn-secondary" href="{{route('products')}}"><i class="fa-regular fa-xmark mr-1"></i> Očisti</a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="collapse {{ $filtersInitiallyOpen ? 'show' : '' }}" id="productFiltersPanel">
                <div class="block-content bg-body-dark admin-filter-panel">
                    <form action="{{ route('products') }}" method="get">

                        <div class="form-group row items-push mb-0">
                            <div class="col-md-9 mb-0">
                                <div class="form-group">
                                    <label class="admin-filter-label" for="search-input">Pretraživanje</label>
                                    <div class="input-group flex-nowrap">
                                        <input type="search" class="form-control" name="search" id="search-input" value="{{ request()->input('search') }}" placeholder="Naziv, šifra, godina ili polica">
                                        <button type="submit" class="btn btn-primary" aria-label="Pretraži"><i class="fa-duotone fa-magnifying-glass"></i><span class="ml-2">Traži</span></button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="admin-filter-label" for="category-select">Kategorija</label>
                                    <select class="js-select2 form-control admin-category-select" id="category-select" name="category" style="width: 100%;" data-placeholder="Odaberi kategoriju">
                                        <option></option><!-- Required for data-placeholder attribute to work with Select2 plugin -->
                                        @foreach ($categories as $group => $cats)
                                            <optgroup label="{{ $group }}">
                                                @foreach ($cats as $id => $category)
                                                    <option value="{{ $id }}"
                                                            data-level="0"
                                                            data-group="{{ $group }}"
                                                            {{ $id == request()->input('category') ? 'selected' : '' }}>{{ $category['title'] }}</option>
                                                    @if ( ! empty($category['subs']))
                                                        @foreach ($category['subs'] as $sub_id => $subcategory)
                                                            <option value="{{ $sub_id }}"
                                                                    data-level="1"
                                                                    data-group="{{ $group }}"
                                                                    data-parent="{{ $category['title'] }}"
                                                                    {{ $sub_id == request()->input('category') ? 'selected' : '' }}>{{ $subcategory['title'] }}</option>
                                                        @endforeach
                                                    @endif
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                        </div>
                        <div class="form-group row items-push mb-0">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="admin-filter-label" for="author-input">Autor</label>
                                    @livewire('back.layout.search.author-search', ['author_id' => request()->input('author') ?: '', 'list' => true])
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="admin-filter-label" for="publisher-input">Nakladnik</label>
                                    @livewire('back.layout.search.publisher-search', ['publisher_id' => request()->input('publisher') ?: '', 'list' => true])
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="admin-filter-label" for="status-select">Dostupnost</label>
                                    <select class="js-select2 form-control" id="status-select" name="status" style="width: 100%;" data-placeholder="Odaberi Status">
                                        <option></option><!-- Required for data-placeholder attribute to work with Select2 plugin -->
                                        <option value="all" {{ 'all' == request()->input('status') ? 'selected' : '' }}>Svi artikli</option>
                                        <option value="available" {{ 'available' == request()->input('status') ? 'selected' : '' }}>Dostupni: qty > 0</option>
                                        <option value="unavailable" {{ 'unavailable' == request()->input('status') ? 'selected' : '' }}>Nedostupni: qty = 0</option>
                                        <option value="with_action" {{ 'with_action' == request()->input('status') ? 'selected' : '' }}>Sa akcijama</option>
                                        <option value="without_action" {{ 'without_action' == request()->input('status') ? 'selected' : '' }}>Bez akcija</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="admin-filter-label" for="sort-select">Sortiranje</label>
                                    <select class="js-select2 form-control" id="sort-select" name="sort" style="width: 100%;" data-placeholder="Sortiraj artikle">
                                        <option></option><!-- Required for data-placeholder attribute to work with Select2 plugin -->
                                        <option value="new" {{ 'new' == request()->input('sort') ? 'selected' : '' }}>Najnovije</option>
                                        <option value="old" {{ 'old' == request()->input('sort') ? 'selected' : '' }}>Najstarije</option>
                                        <option value="price_up" {{ 'price_up' == request()->input('sort') ? 'selected' : '' }}>Cijena od manje</option>
                                        <option value="price_down" {{ 'price_down' == request()->input('sort') ? 'selected' : '' }}>Cijena od više</option>
                                        <option value="az" {{ 'az' == request()->input('sort') ? 'selected' : '' }}>Od A do Ž</option>
                                        <option value="za" {{ 'za' == request()->input('sort') ? 'selected' : '' }}>Od Ž do A</option>
                                    </select>
                                </div>
                            </div>

                        </div>

                    </form>
                </div>
            </div>
            <div class="block-content">
                <div class="table-responsive admin-products-table-wrap">
                    <table class="table table-borderless table-striped table-vcenter admin-products-table">
                        <thead>
                        <tr>
                            <th>Artikl</th>
                            <th>Lokacija</th>
                            <th>Bibliografski podaci</th>
                            <th class="text-right">Cijena</th>
                            <th>Zaliha</th>
                            <th>Aktivnost</th>
                            <th class="text-right">Radnje</th>
                        </tr>
                        </thead>
                        <tbody id="ag-table-with-input-fields" class="js-gallery" >
                        @forelse ($products as $product)
                            @php
                                $subcategory = $product->subcategories->first();
                                $categoryLabels = collect($product->categories ?? [])->pluck('title')->filter();
                                if ($subcategory && ! $categoryLabels->contains($subcategory->title)) {
                                    $categoryLabels->push($subcategory->title);
                                }
                            @endphp
                            <tr>
                                <td class="admin-product-main" data-label="Artikl">
                                    <div class="admin-product-identity">
                                        <a class="img-link img-link-zoom-in img-lightbox admin-product-cover" href="{{ \App\Support\AdminImage::url($product->image) }}">
                                            <img class="admin-product-thumb" src="{{ \App\Support\AdminImage::url($product->thumb) }}" alt="{{ $product->name }}" loading="lazy"/>
                                        </a>
                                        <div class="admin-product-copy">
                                            <a class="admin-product-name" href="{{ route('products.edit', ['product' => $product]) }}">{{ $product->name }}</a>
                                            <div class="admin-product-sku"><i class="fa-duotone fa-barcode mr-1" aria-hidden="true"></i>{{ $product->sku }}</div>
                                            @if($categoryLabels->isNotEmpty())
                                                <div class="admin-product-categories">
                                                    @foreach($categoryLabels->take(2) as $categoryTitle)
                                                        <span>{{ $categoryTitle }}</span>
                                                    @endforeach
                                                    @if($categoryLabels->count() > 2)
                                                        <span title="{{ $categoryLabels->slice(2)->implode(', ') }}">+{{ $categoryLabels->count() - 2 }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="admin-product-quick-fields" data-label="Lokacija">
                                    <div class="admin-quick-field"><span>Polica</span><ag-input-field item="{{ $product }}" target="polica"></ag-input-field></div>
                                    <div class="admin-quick-field"><span>Skladište</span><ag-input-field item="{{ $product }}" target="skl"></ag-input-field></div>
                                </td>
                                <td class="admin-product-quick-fields" data-label="Bibliografski podaci">
                                    <div class="admin-quick-field"><span>Godina</span><ag-input-field item="{{ $product }}" target="year"></ag-input-field></div>
                                    <div class="admin-quick-field"><span>Dimenzije</span><ag-input-field item="{{ $product }}" target="dimensions"></ag-input-field></div>
                                </td>
                                <td class="text-right admin-product-price" data-label="Cijena">
                                    <ag-input-field item="{{ $product }}" target="price" field="price"></ag-input-field>
                                </td>
                                <td class="admin-product-stock" data-label="Zaliha">
                                    <div class="admin-product-quantity"><ag-input-field item="{{ $product }}" target="quantity"></ag-input-field></div>
                                </td>
                                <td class="admin-product-activity" data-label="Aktivnost">
                                    @if($product->last_order_id)
                                        <div><span>Zadnja narudžba</span><a href="{{ route('orders.edit', ['order' => $product->last_order_id]) }}">#{{ $product->last_order_number ?? $product->last_order_id }}</a></div>
                                        <small>{{ optional($product->last_order_at)->format('d.m.Y. H:i') }}</small>
                                    @else
                                        <div><span>Zadnja narudžba</span><strong>—</strong></div>
                                    @endif
                                    <small><strong>Dodano</strong> {{ \Illuminate\Support\Carbon::make($product->created_at)->format('d.m.Y.') }}</small>
                                    <small><strong>Zadnja izmjena</strong> {{ \Illuminate\Support\Carbon::make($product->updated_at)->format('d.m.Y.') }}</small>
                                </td>
                                <td class="text-right admin-product-actions" data-label="Radnje">
                                    <span class="admin-row-actions">
                                    <a class="btn btn-sm btn-alt-secondary" target="_blank" href="{{ url($product->url) }}" title="Otvori artikl" aria-label="Otvori {{ $product->name }}">
                                        <i class="fa-duotone fa-eye" aria-hidden="true"></i>
                                    </a>
                                    <a class="btn btn-sm btn-alt-secondary" href="{{ route('products.edit', ['product' => $product]) }}" title="Uredi artikl" aria-label="Uredi {{ $product->name }}">
                                        <i class="fa-duotone fa-pen-to-square" aria-hidden="true"></i>
                                    </a>
                                    </span>
                                    <div class="admin-product-status">
                                        <div class="custom-control custom-switch custom-control-success mb-0">
                                            <input type="checkbox" class="custom-control-input" id="status-{{ $product->id }}" onclick="setStatus({{ $product->id }})" name="status" @if ($product->status) checked @endif>
                                            <label class="custom-control-label" for="status-{{ $product->id }}"><span class="sr-only">Promijeni status artikla</span></label>
                                        </div>
                                        <span id="status-label-{{ $product->id }}" class="admin-product-status-label">{{ $product->status ? 'Aktivan' : 'Neaktivan' }}</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center" colspan="7">
                                    <div class="py-5 text-muted"><i class="fa-duotone fa-books mr-2" aria-hidden="true"></i>Nema pronađenih artikala.</div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                {{ $products->links() }}
            </div>
        </div>
    </div>
@endsection

@push('css_after')
    <style>
        .admin-products-table-wrap { overflow: visible; }
        .admin-products-table { width: 100%; min-width: 0; table-layout: fixed; }
        .admin-products-table th:nth-child(1) { width: 25%; }
        .admin-products-table th:nth-child(2) { width: 13%; }
        .admin-products-table th:nth-child(3) { width: 13%; }
        .admin-products-table th:nth-child(4) { width: 10%; }
        .admin-products-table th:nth-child(5) { width: 14%; }
        .admin-products-table th:nth-child(6) { width: 15%; }
        .admin-products-table th:nth-child(7) { width: 10%; }
        .admin-products-table th,
        .admin-products-table td { padding: .8rem .65rem; vertical-align: middle; }
        .admin-products-table .admin-product-thumb { width: 3.9rem; height: 5.35rem; border: 1px solid #d8d1c5; border-radius: .2rem; object-fit: cover; box-shadow: none; }
        .admin-product-identity { display: flex; min-width: 0; gap: .75rem; align-items: center; }
        .admin-product-cover { flex: 0 0 auto; }
        .admin-product-copy { min-width: 0; }
        .admin-product-name { display: -webkit-box; overflow: hidden; color: #264c3d; font-size: .96rem; font-weight: 750; line-height: 1.32; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
        .admin-product-sku { margin-top: .24rem; color: #65736b; font-size: var(--admin-type-sm); font-variant-numeric: tabular-nums; }
        .admin-product-categories { display: flex; overflow: hidden; gap: .25rem; margin-top: .35rem; }
        .admin-product-categories span { overflow: hidden; max-width: 8.6rem; padding: .13rem .38rem; border: 1px solid #d8d1c5; border-radius: 99rem; color: #58665e; background: #fff; font-size: var(--admin-type-xs); font-weight: 700; line-height: 1.25; text-overflow: ellipsis; white-space: nowrap; }
        .admin-product-quick-fields { min-width: 0; }
        .admin-quick-field + .admin-quick-field { margin-top: .55rem; }
        .admin-quick-field > span,
        .admin-product-quantity > span,
        .admin-product-activity span { display: block; margin-bottom: .12rem; color: #68756d; font-size: var(--admin-type-xs); font-weight: 800; letter-spacing: .035em; line-height: 1.2; text-transform: uppercase; }
        .admin-products-table ag-input-field { display: block; min-width: 0; color: #26342d; font-size: var(--admin-type-body); font-variant-numeric: tabular-nums; }
        .admin-products-table .ag-input-field-value { max-width: 100%; padding-bottom: .06rem; border-bottom: 1px dashed #a8b2ab; line-height: 1.25; word-break: break-word; }
        .admin-products-table .input-group { min-width: 7rem; }
        .admin-products-table .form-control,
        .admin-products-table .btn { min-height: 2.25rem; height: 2.25rem; }
        .admin-product-price { color: #26342d; font-size: .93rem; font-variant-numeric: tabular-nums; }
        .admin-product-status { display: flex; gap: .4rem; align-items: center; justify-content: flex-end; margin-top: .5rem; }
        .admin-product-status-label { color: #56635b; font-size: var(--admin-type-sm); font-weight: 700; }
        .admin-product-activity { color: #34423a; font-size: var(--admin-type-sm); line-height: 1.35; }
        .admin-product-activity div { margin-bottom: .28rem; }
        .admin-product-activity small { display: block; color: #647168; font-size: var(--admin-type-xs); line-height: 1.3; }
        .admin-product-activity small strong { display: block; margin-bottom: .05rem; color: #536158; font-weight: 800; text-transform: uppercase; }
        .admin-product-activity a { color: #285846; font-weight: 750; }
        .admin-row-actions { display: inline-grid; grid-template-columns: repeat(2, 2.25rem); gap: .35rem; justify-content: end; }
        .admin-row-actions .btn { width: 2.25rem; padding: 0; }
        .admin-filter-toggle { display: inline-flex; align-items: center; }
        .admin-filter-count { display: inline-flex; min-width: 1.35rem; height: 1.35rem; align-items: center; justify-content: center; margin-left: .4rem; padding: 0 .3rem; border-radius: 99rem; color: #fff; background: var(--admin-forest); font-size: .7rem; font-weight: 800; }
        .admin-filter-chevron { transition: transform .16s ease; }
        .admin-filter-toggle[aria-expanded="true"] .admin-filter-chevron { transform: rotate(180deg); }
        html.admin-product-filters-closed #productFiltersPanel { display: none !important; }
        #productFiltersPanel { border-top: 1px solid var(--admin-line); }
        .admin-filter-panel .form-group { margin-bottom: .8rem; }
        .admin-filter-label { display: block; margin-bottom: .32rem !important; color: #36443c !important; font-size: var(--admin-type-xs) !important; font-weight: 800 !important; letter-spacing: .045em; text-transform: uppercase; }

        @media (max-width: 1099.98px) {
            .admin-products-table-wrap { overflow: visible; }
            .admin-products-table,
            .admin-products-table tbody { display: block; width: 100%; }
            .admin-products-table thead { display: none; }
            .admin-products-table tr { display: grid; grid-template-columns: minmax(14rem, 1.5fr) minmax(8rem, .72fr) minmax(8rem, .72fr) minmax(8rem, .65fr); gap: .75rem 1rem; width: 100%; padding: 1rem; border-bottom: 1px solid var(--admin-line); background: #fff !important; }
            .admin-products-table tr:nth-child(even) { background: #faf9f6 !important; }
            .admin-products-table td { display: block; min-width: 0 !important; padding: 0 !important; border: 0 !important; background: transparent !important; text-align: left !important; }
            .admin-products-table td::before { display: block; margin-bottom: .3rem; color: #737f77; content: attr(data-label); font-size: .66rem; font-weight: 800; letter-spacing: .045em; text-transform: uppercase; }
            .admin-products-table .admin-product-main { grid-row: span 2; }
            .admin-products-table .admin-product-main::before { display: none; }
            .admin-products-table .admin-product-price { text-align: left !important; }
            .admin-products-table td:last-child { align-self: end; }
            .admin-products-table .admin-row-actions { display: flex; justify-content: flex-start; }
            .admin-products-table .admin-product-status { justify-content: flex-start; }
        }

        @media (max-width: 767.98px) {
            .admin-products-header { align-items: stretch !important; flex-direction: column; gap: .8rem; }
            .admin-products-header .block-options { width: 100%; }
            .admin-products-header .admin-toolbar-group { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .admin-products-header .admin-toolbar-group .btn { width: 100%; }
            .admin-filter-panel .items-push > [class*=col-] { margin-bottom: 0 !important; }
            .admin-filter-panel .form-group { margin-bottom: .55rem; }
            .admin-products-table tr { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem .75rem; padding: .9rem .75rem; }
            .admin-products-table .admin-product-main { grid-row: auto; grid-column: 1 / -1; padding-bottom: .7rem !important; border-bottom: 1px solid #e7e2d9 !important; }
            .admin-products-table .admin-product-cover { width: 3.9rem; }
            .admin-products-table td:nth-child(6) { grid-column: 1 / -1; }
            .admin-products-table td:last-child { grid-column: 1 / -1; padding-top: .65rem !important; border-top: 1px solid #e7e2d9 !important; }
            .admin-products-table .admin-product-activity { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .35rem .75rem; }
            .admin-products-table .admin-product-activity::before { grid-column: 1 / -1; }
            .admin-products-table .admin-product-activity div { margin: 0; }
            .admin-products-table .admin-product-activity small { white-space: normal; }
            .admin-products-table .admin-row-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); width: 100%; }
            .admin-products-table .admin-row-actions .btn { width: 100%; }
            .admin-products-table .admin-product-status { margin-top: .65rem; }
        }
    </style>
@endpush

@push('js_after')
    <script src="{{ asset('js/ag-input-field.js') }}?v={{ filemtime(public_path('js/ag-input-field.js')) }}"></script>

    <!-- Page JS Plugins -->
    <script src="{{ asset('js/plugins/magnific-popup/jquery.magnific-popup.min.js') }}"></script>

    <!-- Page JS Helpers (Magnific Popup Plugin) -->
    <script>jQuery(function(){Dashmix.helpers('magnific-popup');});</script>

    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(() => {
            const filterPanel = $('#productFiltersPanel');
            const filterStateKey = 'admin.products.filters.open';
            const initiallyClosed = document.documentElement.classList.contains('admin-product-filters-closed');

            if (initiallyClosed) {
                filterPanel.removeClass('show');
                $('.admin-filter-toggle').attr('aria-expanded', 'false');
            }
            document.documentElement.classList.remove('admin-product-filters-closed');

            filterPanel.on('shown.bs.collapse', () => {
                window.localStorage.setItem(filterStateKey, '1');
            });

            filterPanel.on('hidden.bs.collapse', () => {
                window.localStorage.setItem(filterStateKey, '0');
            });

            $('#category-select').select2({
                placeholder: 'Odaberite kategoriju',
                allowClear: true,
                width: '100%',
                dropdownCssClass: 'admin-category-dropdown',
                templateResult: function (item) {
                    if (!item.id) {
                        return item.text;
                    }

                    const element = $(item.element);
                    const level = Number(element.data('level') || 0);
                    const parent = element.data('parent') || '';
                    const icon = level > 0 ? 'fa-turn-down-right' : 'fa-folder';
                    const row = $('<span class="admin-category-option"></span>');

                    row.toggleClass('is-subcategory', level > 0);
                    row.append($('<i class="fa-solid ' + icon + ' admin-category-option-icon" aria-hidden="true"></i>'));
                    row.append($('<span class="admin-category-option-copy"></span>')
                        .append($('<strong></strong>').text(item.text))
                        .append(parent ? $('<small></small>').text(parent) : $('<small></small>').text('Glavna')));

                    return row;
                },
                templateSelection: function (item) {
                    if (!item.id) {
                        return item.text;
                    }

                    const element = $(item.element);
                    const group = element.data('group') || '';
                    const parent = element.data('parent') || '';
                    const path = [group, parent, item.text].filter(Boolean).join(' / ');

                    return $('<span class="admin-category-selection"></span>')
                        .append($('<i class="fa-solid fa-folder-tree" aria-hidden="true"></i>'))
                        .append($('<span></span>').text(path));
                }
            });
            $('#status-select').select2({
                placeholder: 'Odaberite status',
                allowClear: true
            });
            $('#sort-select').select2({
                placeholder: 'Sortiraj artikle',
                allowClear: true
            });

            //
            $('#category-select').on('change', (e) => {
                console.log(e.currentTarget.selectedOptions[0])
                setURL('category', e.currentTarget.selectedOptions[0]);
            });
            $('#status-select').on('change', (e) => {
                setURL('status', e.currentTarget.selectedOptions[0]);
            });
            $('#sort-select').on('change', (e) => {
                setURL('sort', e.currentTarget.selectedOptions[0]);
            });

            //
            Livewire.on('authorSelect', (e) => {
                setURL('author', e.author.id, true);
            });
            Livewire.on('publisherSelect', (e) => {
                setURL('publisher', e.publisher.id, true);
            });

            /*$('#btn-inactive').on('click', () => {
                setRegularURL('active', false);
            });
            $('#btn-today').on('click', () => {
                setRegularURL('today', true);
            });
            $('#btn-week').on('click', () => {
                setRegularURL('week', true);
            });*/

        });

        /**
         *
         * @param type
         * @param search
         */
        function setURL(type, search, isValue = false) {
            let url = new URL(location.href);
            let params = new URLSearchParams(url.search);
            let keys = [];

            for(var key of params.keys()) {
                if (key === type) {
                    keys.push(key);
                }
            }

            keys.forEach((value) => {
                if (params.has(value)) {
                    params.delete(value);
                }
            })

            if (search.value) {
                params.append(type, search.value);
            }

            if (isValue && search) {
                params.append(type, search);
            }

            url.search = params;
            location.href = url;
        }

        /**
         *
         * @param type
         * @param search
         */
        function setRegularURL(type, search) {
            let searches = ['active', 'today', 'week'];
            let url = new URL(location.href);
            let params = new URLSearchParams(url.search);
            let keys = [];

            for(var key of params.keys()) {
                if (key === type) {
                    keys.push(key);
                }
            }

            keys.forEach((value) => {
                if (params.has(value)) {
                    params.delete(value);
                }
            })

            params.append(type, search);

            url.search = params;
            location.href = url;
        }

        /**
         *
         * @param id
         */
        function setStatus(id) {
            const checkbox = $('#status-' + id)[0];
            const val = checkbox.checked;
            const label = $('#status-label-' + id);

            label.text(val ? 'Aktivan' : 'Neaktivan');

            axios.post("{{ route('products.change.status') }}", { id: id, value: val })
            .then((response) => {
                successToast.fire()
            })
            .catch((error) => {
                checkbox.checked = !val;
                label.text(!val ? 'Aktivan' : 'Neaktivan');
                errorToast.fire()
            });
        }
    </script>

@endpush
