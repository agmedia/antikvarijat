@extends('back.layouts.backend')

@push('css_before')
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
@endpush

@section('content')
    <div class="admin-page-hero">
        <div class="content content-full">
            <div class="admin-page-heading">
                <div>
                    <div class="admin-page-kicker"><i class="fa-duotone fa-clock-rotate-left"></i> Sustav</div>
                    <h1 class="admin-page-title">Povijest promjena</h1>
                    <p class="admin-page-description">Pronađite tko je, kada i na kojem zapisu napravio izmjenu.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        @include('back.layouts.partials.session')

        <div class="block block-rounded admin-history-search">
            <div class="block-header block-header-default">
                <div class="d-flex align-items-center min-width-0">
                    <span class="admin-section-icon mr-3"><i class="fa-duotone fa-magnifying-glass"></i></span>
                    <div>
                        <h2 class="block-title mb-1">Pretraživanje zapisa</h2>
                        <span class="admin-count">Filtrirajte prema vrsti ili pojmu</span>
                    </div>
                </div>
            </div>
            <div class="block-content bg-body-dark admin-history-filters">
                <form action="{{ route('history') }}" method="GET" class="row align-items-end">
                    <div class="col-md-4 mb-2">
                        <label class="admin-filter-label" for="trazi-select">Vrsta zapisa</label>
                        <select class="js-select2 form-control" id="trazi-select" name="trazi" style="width: 100%;" data-placeholder="Sve vrste">
                            <option></option>
                            <option value="knjige" {{ 'knjige' == request('trazi') ? 'selected' : '' }}>Knjige</option>
                            <option value="narudzba" {{ 'narudzba' == request('trazi') ? 'selected' : '' }}>Narudžbe</option>
                            <option value="autor" {{ 'autor' == request('trazi') ? 'selected' : '' }}>Autori</option>
                            <option value="nakladnik" {{ 'nakladnik' == request('trazi') ? 'selected' : '' }}>Nakladnici</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="admin-filter-label" for="search-input">Pojam</label>
                        <input type="search" class="form-control" id="search-input" name="pojam" placeholder="Naziv, broj ili korisnik" value="{{ request('pojam') }}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="submit" class="btn btn-primary btn-block"><i class="fa-duotone fa-magnifying-glass mr-1"></i> Traži</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="accordion" class="admin-history-list" role="tablist" aria-multiselectable="true">
            @forelse($history as $item)
                <article class="block block-rounded admin-history-entry">
                    <div class="block-header block-header-default" role="tab" id="accordion_h{{ $item->id }}">
                        <a class="admin-history-entry-toggle" data-toggle="collapse" data-parent="#accordion" href="#accordion_q{{ $item->id }}" aria-expanded="false" aria-controls="accordion_q{{ $item->id }}">
                            <span class="admin-history-entry-icon"><i class="fa-duotone fa-file-pen"></i></span>
                            <span class="admin-history-entry-copy">
                                <strong>{!! $item->title !!}</strong>
                                <small>
                                    <span><i class="fa-duotone fa-user mr-1"></i>{{ optional($item->user())->name ?: 'Nepoznat korisnik' }}</span>
                                    <span><i class="fa-duotone fa-calendar-days mr-1"></i>{{ $item->created_at->format('d.m.Y. H:i') }}</span>
                                </small>
                            </span>
                            <i class="fa fa-angle-down admin-history-chevron" aria-hidden="true"></i>
                        </a>
                        <div class="block-options">
                            <a href="{{ route('history.show', ['history' => $item->id]) }}" class="btn btn-sm btn-alt-secondary" title="Prikaži detalje" aria-label="Prikaži detalje zapisa"><i class="fa-duotone fa-eye"></i></a>
                        </div>
                    </div>
                    <div id="accordion_q{{ $item->id }}" class="collapse" role="tabpanel" aria-labelledby="accordion_h{{ $item->id }}" data-parent="#accordion">
                        <div class="block-content admin-history-changes">{!! $item->changes !!}</div>
                    </div>
                </article>
            @empty
                <div class="block block-rounded"><div class="block-content text-center text-muted py-5"><i class="fa-duotone fa-clock-rotate-left mr-2"></i>Nema zapisa povijesti.</div></div>
            @endforelse
        </div>
        {{ $history->links() }}
    </div>
@endsection

@push('css_after')
    <style>
        #main-container .admin-history-search { margin-bottom: .8rem !important; }
        .admin-history-list { display: grid; gap: .35rem; }
        #main-container .admin-history-entry { margin-bottom: 0 !important; }
        #main-container .admin-history-entry .block-header { min-height: 3.5rem; padding-top: .4rem; padding-bottom: .4rem; }
        .admin-history-entry-toggle { display: flex; min-width: 0; flex: 1; align-items: center; gap: .65rem; color: var(--admin-ink); }
        .admin-history-entry-toggle:hover { color: var(--admin-forest); }
        .admin-history-entry-icon { display: inline-flex; width: 2.15rem; height: 2.15rem; flex: 0 0 auto; align-items: center; justify-content: center; border-radius: .3rem; color: #8c6934; background: #f2ece1; }
        .admin-history-entry-copy { display: grid; min-width: 0; gap: .18rem; }
        .admin-history-entry-copy > strong { overflow: hidden; font-size: var(--admin-type-body); line-height: 1.35; text-overflow: ellipsis; }
        .admin-history-entry-copy small { display: flex; flex-wrap: wrap; gap: .35rem 1rem; color: #647168; font-size: var(--admin-type-xs); }
        .admin-history-chevron { margin-left: auto; color: #748078; transition: transform .16s ease; }
        .admin-history-entry-toggle[aria-expanded="true"] .admin-history-chevron { transform: rotate(180deg); }
        .admin-history-changes { overflow-wrap: anywhere; padding-top: .8rem; padding-bottom: 1rem; border-top: 1px solid var(--admin-line); }
        @media (max-width: 575.98px) {
            .admin-history-entry .block-header { align-items: flex-start; }
            .admin-history-entry-copy small { display: grid; }
        }
    </style>
@endpush

@push('js_after')
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(() => {
            $('#trazi-select').select2({ placeholder: 'Sve vrste', allowClear: true });
        });
    </script>
@endpush
