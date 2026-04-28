@extends('back.layouts.backend')

@push('css_after')
    <style>
        .vialibri-search-wrap { position: relative; }
        .vialibri-live-search {
            position: absolute;
            top: calc(100% + .25rem);
            left: 0;
            right: 0;
            z-index: 1050;
            display: none;
            background: #fff;
            border: 1px solid rgba(0, 0, 0, .15);
            border-radius: .25rem;
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
            max-height: 470px;
            overflow-y: auto;
        }

        .vialibri-live-search.show { display: block; }
        .vialibri-live-search .vialibri-search-head {
            padding: .75rem 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, .08);
            font-size: 13px;
            color: #495057;
        }
        .vialibri-live-search .table { margin-bottom: 0; }
        .vialibri-live-search .table tr:hover { background: #fcfcfc; }
        .vialibri-live-search .vialibri-image {
            width: 76px;
            padding: .75rem .5rem .75rem .75rem !important;
            vertical-align: middle;
        }
        .vialibri-live-search .vialibri-image img {
            width: 56px;
            height: 72px;
            object-fit: cover;
            border-radius: .25rem;
            display: block;
        }
        .vialibri-live-search .vialibri-main { vertical-align: middle; }
        .vialibri-live-search .vialibri-meta { font-size: 12px; color: #6c757d; }
        .vialibri-live-search .vialibri-action { width: 170px; text-align: right; vertical-align: middle; padding-right: .75rem !important; }
        .vialibri-search-empty { padding: .75rem; text-align: center; color: #6c757d; }
    </style>
@endpush

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">ViaLibri Harvest</h1>
                <a href="{{ route('vialibri.config') }}" class="btn btn-primary my-2">
                    <i class="si si-settings mr-1"></i> Config
                </a>
            </div>
        </div>
    </div>

    <div class="content">
        @include('back.layouts.partials.session')

        <div class="block block-rounded mb-4">
            <div class="block-header block-header-default">
                <h3 class="block-title">Dodaj novi naslov</h3>
            </div>
            <div class="block-content pb-4">
                <div class="form-group mb-0">
                    <label for="vialibri-search-box">Pretraži po naslovu, šifri ili autoru</label>
                    <div class="vialibri-search-wrap">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-search"></i></span>
                            </div>
                            <input id="vialibri-search-box"
                                   type="text"
                                   class="form-control"
                                   autocomplete="off"
                                   placeholder="Upiši najmanje 3 slova">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-light" id="vialibri-search-clear">Očisti</button>
                            </div>
                        </div>
                        <div id="vialibri-search-result" class="vialibri-live-search"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Odabrani naslovi</h3>
                <div class="block-options">
                    <span class="badge badge-info">Za export: {{ $exportableCount }}</span>
                </div>
            </div>
            <div class="block-content">
                @forelse ($selectedBooks as $item)
                    @php $product = $item->product; @endphp

                    <div class="block block-rounded block-bordered mb-3">
                        <div class="block-content block-content-full">
                            <div class="row align-items-center">
                                <div class="col-md-7">
                                    <div class="font-w700">{{ optional($product)->name ?: 'Artikl nije pronađen' }}</div>
                                    <div class="small text-muted">
                                        ID {{ $item->product_id }}
                                        @if ($product)
                                            | Šifra {{ $product->sku }}
                                            | Autor: {{ optional($product->author)->title ?: '—' }}
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-5 text-md-right mt-3 mt-md-0">
                                    @if ($item->translated_at)
                                        <span class="badge badge-success mr-2">Prevedeno</span>
                                    @else
                                        <span class="badge badge-secondary mr-2">Bez prijevoda</span>
                                    @endif

                                    @if ($product && (! $product->status || $product->quantity <= 0 || $product->price <= 0))
                                        <span class="badge badge-danger mr-2">Nije prodajan</span>
                                    @endif

                                    <a href="{{ route('vialibri.edit', ['vialibriBook' => $item]) }}" class="btn btn-sm btn-primary">
                                        Uredi / Prevedi
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="mb-0">Još nema odabranih naslova za ViaLibri.</p>
                @endforelse

                {{ $selectedBooks->links() }}
            </div>
        </div>
    </div>
@endsection

@push('js_after')
    <script>
        (function () {
            const input = document.getElementById('vialibri-search-box');
            const resultBox = document.getElementById('vialibri-search-result');
            const clearButton = document.getElementById('vialibri-search-clear');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const endpoint = @json(route('vialibri.autocomplete'));
            let debounceId = null;

            if (!input || !resultBox) {
                return;
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function closeResults() {
                resultBox.classList.remove('show');
                resultBox.innerHTML = '';
            }

            function renderEmpty(message) {
                resultBox.innerHTML = '<div class="vialibri-search-empty">' + escapeHtml(message) + '</div>';
                resultBox.classList.add('show');
            }

            function renderResults(items) {
                if (!items.length) {
                    renderEmpty('Nema pronađenih rezultata.');
                    return;
                }

                let html = '<div class="vialibri-search-head">Pronađeno: <strong>' + items.length + '</strong> rezultata</div>';
                html += '<table class="table table-hover table-vcenter mb-0"><tbody>';

                items.forEach(function (item) {
                    const categories = Array.isArray(item.categories) && item.categories.length
                        ? '<div class="mt-1">' + item.categories.map(function (category) {
                            return '<span class="badge badge-secondary mr-1">' + escapeHtml(category) + '</span>';
                        }).join('') + '</div>'
                        : '';
                    const stockBadge = item.is_saleable
                        ? ''
                        : '<div class="mt-1"><span class="badge badge-danger">Nije prodajan</span></div>';
                    const titleBadges = item.is_added
                        ? '<span class="badge badge-success mr-1">Dodano</span>'
                        : '';

                    const actionHtml = item.is_added
                        ? '<a href="' + item.edit_url + '" class="btn btn-sm btn-alt-secondary">Uredi</a>'
                        : '<form method="post" action="' + item.store_url + '" class="d-inline-block">'
                            + '<input type="hidden" name="_token" value="' + csrf + '">'
                            + '<button type="submit" class="btn btn-sm btn-primary">Dodaj u listu</button>'
                            + '</form>';

                    html += '<tr>';
                    html += '<td class="vialibri-image"><img src="' + escapeHtml(item.image_url) + '" alt="' + escapeHtml(item.name) + '"></td>';
                    html += '<td class="vialibri-main">';
                    html += '<div class="font-w600">' + titleBadges + escapeHtml(item.name) + '</div>';
                    html += '<div class="vialibri-meta">Šifra ' + escapeHtml(item.sku || '—') + ' | Autor: ' + escapeHtml(item.author_title || '—') + ' | ' + escapeHtml(item.price_text) + ' | Kol: ' + escapeHtml(item.quantity) + '</div>';
                    html += categories;
                    html += stockBadge;
                    html += '</td>';
                    html += '<td class="vialibri-action">' + actionHtml + '</td>';
                    html += '</tr>';
                });

                html += '</tbody></table>';

                resultBox.innerHTML = html;
                resultBox.classList.add('show');
            }

            function fetchResults(query) {
                axios.get(endpoint, {
                    params: { query: query }
                }).then(function (response) {
                    renderResults((response.data && response.data.items) || []);
                }).catch(function () {
                    renderEmpty('Greška pri pretrazi.');
                });
            }

            input.addEventListener('input', function (event) {
                const query = (event.target.value || '').trim();
                clearTimeout(debounceId);

                if (query.length < 3) {
                    closeResults();
                    return;
                }

                debounceId = setTimeout(function () {
                    fetchResults(query);
                }, 200);
            });

            clearButton?.addEventListener('click', function () {
                input.value = '';
                closeResults();
                input.focus();
            });

            document.addEventListener('click', function (event) {
                if (!event.target.closest('.vialibri-search-wrap')) {
                    closeResults();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeResults();
                }
            });
        }());
    </script>
@endpush
