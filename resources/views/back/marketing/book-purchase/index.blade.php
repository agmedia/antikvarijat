@extends('back.layouts.backend')

@section('content')
    <div class="admin-page-hero">
        <div class="content content-full">
            <div class="admin-page-heading">
                <div>
                    <div class="admin-page-kicker"><i class="fa-duotone fa-books-medical"></i> Marketing</div>
                    <h1 class="admin-page-title">Otkup knjiga</h1>
                    <p class="admin-page-description">Pregledajte i obradite prijave korisnika za prodaju knjiga antikvarijatu.</p>
                </div>
                <div class="admin-page-actions">
                    <a class="btn btn-secondary" href="{{ route('book.purchases.content.edit') }}">
                        <i class="fa-duotone fa-pen-to-square mr-1"></i> Uredi tekstove HR / EN
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <div class="d-flex align-items-center min-width-0">
                    <span class="admin-section-icon mr-3"><i class="fa-duotone fa-inbox-full"></i></span>
                    <div>
                        <h2 class="block-title mb-1">Prijave za otkup knjiga</h2>
                        <span class="admin-count">{{ number_format($purchases->total(), 0, ',', '.') }} prijava</span>
                    </div>
                </div>
            </div>
            <div class="block-content">
                @if (session('status'))
                    <div class="alert alert-info">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="admin-book-purchase-filter admin-filter-panel mb-3">
                    <form method="get" action="{{ route('book.purchases') }}">
                        <div class="admin-book-purchase-filter-grid">
                            <div class="admin-book-purchase-filter-field">
                                <label class="mb-1">Ime i prezime</label>
                                <input type="text" class="form-control" name="name" value="{{ request('name') }}" placeholder="npr. Ivan Horvat">
                            </div>
                            <div class="admin-book-purchase-filter-field">
                                <label class="mb-1">Email</label>
                                <input type="text" class="form-control" name="email" value="{{ request('email') }}" placeholder="npr. korisnik@mail.com">
                            </div>
                            <div class="admin-book-purchase-filter-field">
                                <label class="mb-1">Datum od</label>
                                <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}">
                            </div>
                            <div class="admin-book-purchase-filter-field">
                                <label class="mb-1">Datum do</label>
                                <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}">
                            </div>
                            <div class="admin-book-purchase-filter-actions">
                                <button type="submit" class="btn btn-primary"><i class="fa-duotone fa-magnifying-glass mr-1"></i> Filtriraj</button>
                                <a href="{{ route('book.purchases') }}" class="btn btn-secondary"><i class="fa-regular fa-xmark mr-1"></i> Očisti</a>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-vcenter">
                        <thead>
                        <tr>
                            <th style="width: 170px;">Vrijeme</th>
                            <th>Podaci</th>
                            <th style="width: 180px;">Fotografije</th>
                            <th style="width: 110px;">Detalji</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($purchases as $item)
                            <tr>
                                <td>
                                    <div class="font-w600">{{ optional($item->submitted_at)->format('d.m.Y H:i') ?: '-' }}</div>
                                    <div class="font-size-sm text-muted">{{ $item->submission_id }}</div>
                                </td>
                                <td>
                                    <div><strong>{{ $item->full_name }}</strong></div>
                                    <div class="font-size-sm">Poštanski broj: {{ $item->postal_code }}</div>
                                    <div class="font-size-sm">Email: <a href="mailto:{{ $item->email }}">{{ $item->email }}</a></div>
                                    <div class="font-size-sm">Mobitel: {{ $item->phone }}</div>
                                </td>
                                <td>
                                    @php($photos = collect($item->photos ?? []))
                                    @if($photos->isNotEmpty())
                                        @php($firstPhoto = $photos->first())
                                        <div>
                                            <img src="{{ $firstPhoto['url'] ?? '' }}" alt="{{ $firstPhoto['name'] ?? '' }}" style="width:56px; height:56px; object-fit:cover; border-radius:6px; border:1px solid #e2e2e2;">
                                        </div>
                                        <div class="font-size-sm text-muted mt-2">
                                            Ukupno fotografija: <strong>{{ $photos->count() }}</strong>
                                        </div>
                                    @else
                                        <span class="text-muted">Nema fotografija</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('book.purchases.show', ['purchase' => $item->id]) }}" class="btn btn-sm btn-alt-primary">
                                        Pregled
                                    </a>
                                    <form method="post"
                                          action="{{ route('book.purchases.destroy', ['purchase' => $item->id]) }}"
                                          class="mt-2"
                                          onsubmit="return confirm('Jeste li sigurni da želite obrisati prijavu i sve fotografije?');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="redirect_to" value="{{ url()->full() }}">
                                        <button type="submit" class="btn btn-sm btn-alt-danger">Obriši</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">Nema prijava za otkup knjiga.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $purchases->links() }}
            </div>
        </div>
    </div>
@endsection

@push('css_after')
    <style>
        .admin-book-purchase-filter { padding: 1rem; border: 1px solid var(--admin-line); background: #f2f4f2; }
        .admin-book-purchase-filter-grid { display: grid; grid-template-columns: minmax(12rem, 1.2fr) minmax(12rem, 1.2fr) minmax(10rem, .8fr) minmax(10rem, .8fr) auto; gap: .8rem; align-items: end; }
        .admin-book-purchase-filter-field { min-width: 0; }
        .admin-book-purchase-filter-field label { display: block; font-size: var(--admin-type-xs) !important; font-weight: 800 !important; letter-spacing: .04em; text-transform: uppercase; }
        .admin-book-purchase-filter-actions { display: flex; gap: .5rem; align-items: center; }
        @media (max-width: 1399.98px) { .admin-book-purchase-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .admin-book-purchase-filter-actions { grid-column: 1 / -1; } }
        @media (max-width: 575.98px) { .admin-book-purchase-filter-grid { grid-template-columns: minmax(0, 1fr); } .admin-book-purchase-filter-actions { grid-column: 1; } .admin-book-purchase-filter-actions .btn { flex: 1 1 0; } }
    </style>
@endpush
