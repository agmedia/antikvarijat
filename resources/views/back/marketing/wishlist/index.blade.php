@extends('back.layouts.backend')

@section('content')
    @php
        // Koji tab je aktivan? (default: wishlists)
        $activeTab = request('tab', 'wishlists');
    @endphp

    <div class="admin-page-hero">
        <div class="content content-full">
            <div class="admin-page-heading">
                <div>
                    <div class="admin-page-kicker"><i class="fa-duotone fa-heart" aria-hidden="true"></i> Marketing</div>
                    <h1 class="admin-page-title">Liste želja</h1>
                    <p class="admin-page-description">Pratite interes kupaca i artikle koji se najčešće spremaju.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <div class="d-flex align-items-center min-width-0">
                    <span class="admin-section-icon mr-3"><i class="fa-duotone fa-chart-line-up" aria-hidden="true"></i></span>
                    <div>
                        <h2 class="block-title mb-1">Interes kupaca</h2>
                        <span class="admin-count">Liste želja i najtraženiji artikli</span>
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
                {{-- Tabs navigacija (linkovi, ne JS tabovi) --}}
                <ul class="nav nav-tabs nav-tabs-block" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab === 'wishlists' ? 'active' : '' }}"
                           href="{{ route('wishlists', array_merge(request()->except('page'), ['tab' => 'wishlists'])) }}">
                            Sve liste želja
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab === 'top-products' ? 'active' : '' }}"
                           href="{{ route('wishlists', array_merge(request()->except('page'), ['tab' => 'top-products'])) }}">
                            Najtraženiji artikli
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- TAB 1: Sve liste želja --}}
                    @if ($activeTab === 'wishlists')
                        <div class="tab-pane fade show active" id="tab-wishlists" role="tabpanel">
                            <div class="block-content pt-3">
                                {{-- Filter box --}}
                                <div class="admin-filter-panel p-3 mb-3">
                                    <form method="get" action="{{ route('wishlists') }}">
                                        {{-- zadrži aktivni tab --}}
                                        <input type="hidden" name="tab" value="wishlists">
                                        <div class="form-group row mb-0">
                                            <div class="col-md-9">
                                                <label class="admin-filter-label" for="wishlist-search">Pretraživanje</label>
                                                <div class="input-group">
                                                    <input type="text"
                                                           class="form-control"
                                                           id="wishlist-search"
                                                           name="search"
                                                           value="{{ request()->input('search') }}"
                                                           placeholder="Pretraži po nazivu ili šifri artikla">
                                                    <div class="input-group-append">
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="fa-duotone fa-magnifying-glass mr-1" aria-hidden="true"></i> Pretraži
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                {{-- Tablica: sve liste želja --}}
                                <div class="table-responsive">
                                    <table class="table table-borderless table-striped table-vcenter admin-data-table admin-wishlist-table">
                                        <thead>
                                        <tr>
                                            <th style="width: 80px;">Slika</th>
                                            <th>Naziv</th>
                                            <th>Šifra</th>
                                            <th>E-mail (korisnik)</th>
                                            <th>Dodano</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($wishlists as $w)
                                            <tr>
                                                <td data-label="Slika">
                                                    @if($w->product && $w->product->image)
                                                        <img class="admin-wishlist-thumb" src="{{ \App\Support\AdminImage::url($w->product->image) }}" alt="{{ $w->product->name }}" loading="lazy">
                                                    @endif
                                                </td>
                                                <td data-label="Naziv">{{ $w->product->name ?? '—' }}</td>
                                                <td data-label="Šifra">{{ $w->product->sku ?? '—' }}</td>
                                                <td data-label="E-mail">{{ $w->email }}</td>
                                                <td data-label="Dodano">{{ $w->created_at->format('d.m.Y.') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td class="text-center text-muted py-5" colspan="5">Nema zapisa u listi želja.</td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Paginacija zadržava aktivni tab i sve upite --}}
                                {{ $wishlists->appends(array_merge(request()->query(), ['tab' => 'wishlists']))->links() }}
                            </div>
                        </div>
                    @endif

                    {{-- TAB 2: Najtraženiji artikli --}}
                    @if ($activeTab === 'top-products')
                        <div class="tab-pane fade show active" id="tab-top-products" role="tabpanel">
                            <div class="block-content pt-3">
                                {{-- (Ako jednog dana dodaš filtere i ovdje, ne zaboravi hidden tab input) --}}

                                <div class="table-responsive">
                                    <table class="table table-borderless table-striped admin-data-table">
                                        <thead>
                                        <tr>
                                            <th>Naziv artikla</th>
                                            <th>Šifra</th>
                                            <th class="text-right">Broj prijava</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse ($topProducts as $item)
                                            <tr>
                                                <td data-label="Naziv artikla">{{ optional($item->product)->name ?? '—' }}</td>
                                                <td data-label="Šifra">{{ optional($item->product)->sku ?? '—' }}</td>
                                                <td class="text-right" data-label="Broj prijava"><strong>{{ number_format($item->total, 0, ',', '.') }}</strong></td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td class="text-center text-muted py-5" colspan="3">Nema zapisa za najtraženije artikle.</td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>

                                    {{-- Paginacija zadržava aktivni tab i sve upite --}}
                                    {{ $topProducts->appends(array_merge(request()->query(), ['tab' => 'top-products']))->links() }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
</div>
@endsection

@push('css_after')
    <style>
        .admin-wishlist-table th:first-child, .admin-wishlist-table td:first-child { width: 6rem; }
        .admin-wishlist-thumb { width: 3.9rem; height: 5.2rem; border: 1px solid #d8d2c8; border-radius: .2rem; object-fit: cover; }
        @media (max-width: 767.98px) {
            .admin-wishlist-table th:first-child, .admin-wishlist-table td:first-child { width: 100%; }
        }
    </style>
@endpush
