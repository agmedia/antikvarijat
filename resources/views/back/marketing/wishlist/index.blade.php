@extends('back.layouts.backend')

@section('content')
    <div class="admin-page-hero">
        <div class="content content-full">
            <div class="admin-page-heading">
                <div>
                    <div class="admin-page-kicker"><i class="fa-duotone fa-heart" aria-hidden="true"></i> Marketing</div>
                    <h1 class="admin-page-title">Liste želja</h1>
                    <p class="admin-page-description">Pratite interes kupaca, stanje artikala i ručno šaljite obavijesti kada se knjiga vrati na zalihu.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        @include('back.layouts.partials.session')

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <div class="d-flex align-items-center min-width-0">
                    <span class="admin-section-icon mr-3"><i class="fa-duotone fa-chart-line-up" aria-hidden="true"></i></span>
                    <div>
                        <h2 class="block-title mb-1">Liste želja i najtraženiji artikli</h2>
                        <span class="admin-count">{{ number_format($stats['ready'], 0, ',', '.') }} spremno za ručno slanje</span>
                    </div>
                </div>
                @if(count(request()->except('tab')))
                    <div class="block-options">
                        <a class="btn btn-secondary" href="{{ route('wishlists', ['tab' => $activeTab]) }}">
                            <i class="fa-regular fa-xmark mr-1" aria-hidden="true"></i> Očisti filtere
                        </a>
                    </div>
                @endif
            </div>

            <div class="block-content">
                <div class="alert alert-info d-flex align-items-start mb-4" role="alert">
                    <i class="fa-duotone fa-circle-info mr-2 mt-1" aria-hidden="true"></i>
                    <div>
                        Wishlist mailovi se ne šalju automatski. Gornje srce prikazuje koliko ih je spremno, a slanje se potvrđuje ručno za svakog kupca.
                        @if(!config('wishlist.emails_enabled')) <strong>U ovom lokalnom okruženju stvarno slanje je sigurnosno isključeno.</strong> @endif
                    </div>
                </div>

                <ul class="nav nav-tabs nav-tabs-block" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab === 'wishlists' ? 'active' : '' }}" href="{{ route('wishlists', ['tab' => 'wishlists']) }}">Sve liste želja</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab === 'top-products' ? 'active' : '' }}" href="{{ route('wishlists', ['tab' => 'top-products']) }}">Najtraženiji artikli</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab === 'statistics' ? 'active' : '' }}" href="{{ route('wishlists', ['tab' => 'statistics']) }}">Statistike</a>
                    </li>
                </ul>

                @if ($activeTab !== 'statistics')
                    <div class="admin-filter-panel p-3 my-3">
                        <form method="GET" action="{{ route('wishlists') }}">
                            <input type="hidden" name="tab" value="{{ $activeTab }}">
                            <div class="form-row align-items-end">
                                <div class="{{ $activeTab === 'wishlists' ? 'col-md-8' : 'col-md-10' }} mb-2">
                                    <label class="admin-filter-label" for="wishlist-search">Pretraživanje</label>
                                    <input class="form-control" id="wishlist-search" type="search" name="search" value="{{ $search }}" placeholder="Pretraži po nazivu ili šifri artikla">
                                </div>
                                @if ($activeTab === 'wishlists')
                                    <div class="col-md-3 mb-2">
                                        <label class="admin-filter-label" for="wishlist-stock">Filtriraj stanje</label>
                                        <select class="form-control" id="wishlist-stock" name="stock">
                                            <option value="">Sve prijave</option>
                                            <option value="ready" @if($stock === 'ready') selected @endif>Spremno za slanje</option>
                                            <option value="unsent" @if($stock === 'unsent') selected @endif>Nije poslano</option>
                                            <option value="sent" @if($stock === 'sent') selected @endif>Poslano</option>
                                            <option value="waiting" @if($stock === 'waiting') selected @endif>Čeka zalihu</option>
                                        </select>
                                    </div>
                                @endif
                                <div class="col-md-1 mb-2">
                                    <button class="btn btn-primary btn-block" type="submit" title="Primijeni filter">
                                        <i class="fa-duotone fa-filter" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                @endif

                @if ($activeTab === 'wishlists')
                    <div class="table-responsive">
                        <table class="table table-borderless table-striped table-vcenter admin-data-table admin-wishlist-table">
                            <thead>
                            <tr>
                                <th style="width: 72px;">Slika</th>
                                <th>Artikl</th>
                                <th>Šifra</th>
                                <th>Stanje</th>
                                <th>E-mail</th>
                                <th>Status</th>
                                <th>Dodano</th>
                                <th>Poslano</th>
                                <th class="text-right">Akcija</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($wishlists as $wishlist)
                                @php
                                    $product = $wishlist->product;
                                    $isReady = $product && $product->status && $product->quantity > 0 && !$wishlist->sent && $wishlist->status;
                                @endphp
                                <tr>
                                    <td data-label="Slika">
                                        @if($product && $product->image)
                                            <img class="admin-wishlist-thumb" src="{{ \App\Support\AdminImage::url($product->image) }}" alt="{{ $product->name }}" loading="lazy">
                                        @endif
                                    </td>
                                    <td data-label="Artikl">
                                        @if($product)
                                            <a class="font-w600" href="{{ route('products.edit', ['product' => $product->id]) }}">{{ $product->name }}</a>
                                        @else
                                            <span class="text-muted">Artikl #{{ $wishlist->product_id }} nije dostupan</span>
                                        @endif
                                    </td>
                                    <td data-label="Šifra">{{ optional($product)->sku ?? '—' }}</td>
                                    <td data-label="Stanje">
                                        @if(!$product)
                                            <span class="badge badge-secondary">Nedostupan</span>
                                        @elseif($product->status && $product->quantity > 0)
                                            <span class="badge badge-success">Na stanju: {{ $product->quantity }}</span>
                                        @else
                                            <span class="badge badge-warning">Nema na stanju</span>
                                        @endif
                                    </td>
                                    <td data-label="E-mail"><a href="mailto:{{ $wishlist->email }}">{{ $wishlist->email }}</a></td>
                                    <td data-label="Status">
                                        @if($wishlist->sent)
                                            <span class="badge badge-success">Poslano</span>
                                        @elseif($isReady)
                                            <span class="badge badge-danger">Spremno</span>
                                        @elseif(!$wishlist->status)
                                            <span class="badge badge-secondary">Neaktivno</span>
                                        @else
                                            <span class="badge badge-warning">Čeka zalihu</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap" data-label="Dodano">{{ optional($wishlist->created_at)->format('d.m.Y. H:i') }}</td>
                                    <td class="text-nowrap" data-label="Poslano">{{ optional($wishlist->sent_at)->format('d.m.Y. H:i') ?: '—' }}</td>
                                    <td class="text-right" data-label="Akcija">
                                        @if($isReady && config('wishlist.emails_enabled'))
                                            <form method="POST" action="{{ route('wishlists.send', $wishlist) }}" onsubmit="return confirm('Poslati wishlist obavijest ovom kupcu?');">
                                                @csrf
                                                <button class="btn btn-sm btn-primary" type="submit"><i class="fa-duotone fa-paper-plane mr-1" aria-hidden="true"></i> Pošalji</button>
                                            </form>
                                        @elseif($isReady)
                                            <span class="badge badge-secondary">Lokalno isključeno</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="text-center text-muted py-5" colspan="9">Nema zapisa za odabrane filtere.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $wishlists->appends(array_merge(request()->query(), ['tab' => 'wishlists']))->links() }}
                @elseif ($activeTab === 'top-products')
                    <div class="table-responsive mt-3">
                        <table class="table table-borderless table-striped table-vcenter admin-data-table">
                            <thead>
                            <tr>
                                <th>Artikl</th>
                                <th>Šifra</th>
                                <th>Stanje</th>
                                <th class="text-right">Ukupno prijava</th>
                                <th class="text-right">Nije poslano</th>
                                <th class="text-right">Poslano</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($topProducts as $item)
                                <tr>
                                    <td data-label="Artikl">
                                        @if($item->product)<a class="font-w600" href="{{ route('products.edit', ['product' => $item->product->id]) }}">{{ $item->product->name }}</a>@else — @endif
                                    </td>
                                    <td data-label="Šifra">{{ optional($item->product)->sku ?? '—' }}</td>
                                    <td data-label="Stanje">{{ optional($item->product)->quantity ?? '—' }}</td>
                                    <td class="text-right" data-label="Ukupno"><strong>{{ number_format($item->total, 0, ',', '.') }}</strong></td>
                                    <td class="text-right" data-label="Nije poslano">{{ number_format($item->unsent_total, 0, ',', '.') }}</td>
                                    <td class="text-right" data-label="Poslano">{{ number_format($item->sent_total, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td class="text-center text-muted py-5" colspan="6">Nema pronađenih artikala.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $topProducts->appends(array_merge(request()->query(), ['tab' => 'top-products']))->links() }}
                @else
                    <div class="row mt-4">
                        @foreach([
                            ['label' => 'Sve prijave', 'value' => $stats['total'], 'icon' => 'fa-heart'],
                            ['label' => 'Spremno za slanje', 'value' => $stats['ready'], 'icon' => 'fa-paper-plane'],
                            ['label' => 'Nije poslano', 'value' => $stats['unsent'], 'icon' => 'fa-hourglass-half'],
                            ['label' => 'Poslano', 'value' => $stats['sent'], 'icon' => 'fa-circle-check'],
                            ['label' => 'Jedinstveni e-mailovi', 'value' => $stats['unique_emails'], 'icon' => 'fa-users'],
                            ['label' => 'Dodano ovaj mjesec', 'value' => $stats['this_month'], 'icon' => 'fa-calendar'],
                        ] as $card)
                            <div class="col-sm-6 col-xl-4">
                                <div class="block block-rounded bg-body-light">
                                    <div class="block-content d-flex align-items-center py-4">
                                        <span class="admin-section-icon mr-3"><i class="fa-duotone {{ $card['icon'] }}" aria-hidden="true"></i></span>
                                        <div><div class="font-size-h3 font-w700">{{ number_format($card['value'], 0, ',', '.') }}</div><div class="text-muted">{{ $card['label'] }}</div></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <a class="btn btn-primary mb-4" href="{{ route('wishlists', ['tab' => 'wishlists', 'stock' => 'ready']) }}"><i class="fa-duotone fa-paper-plane mr-1"></i> Otvori spremne za slanje</a>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('css_after')
    <style>
        .admin-wishlist-table th:first-child, .admin-wishlist-table td:first-child { width: 5rem; }
        .admin-wishlist-thumb { width: 3.5rem; height: 4.6rem; border: 1px solid #d8d2c8; border-radius: .25rem; object-fit: cover; }
        @media (max-width: 767.98px) {
            .admin-wishlist-table th:first-child, .admin-wishlist-table td:first-child { width: 100%; }
        }
    </style>
@endpush
