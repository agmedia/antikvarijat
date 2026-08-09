@extends('back.layouts.backend')

@section('content')

    <div class="admin-page-hero">
        <div class="content content-full">
            <div class="admin-page-heading">
                <div>
                    <div class="admin-page-kicker"><i class="fa-duotone fa-file-lines" aria-hidden="true"></i> Postavke</div>
                    <h1 class="admin-page-title">Info stranice</h1>
                    <p class="admin-page-description">Uredite sadržajne stranice, podgrupe i njihovu vidljivost.</p>
                </div>
                <div class="admin-page-actions">
                    <a class="btn btn-primary" href="{{ route('pages.create') }}">
                        <i class="fa-duotone fa-plus mr-1" aria-hidden="true"></i> Nova stranica
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="content">
    @include('back.layouts.partials.session')


        <!-- Posts -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <div class="d-flex align-items-center min-width-0">
                    <span class="admin-section-icon mr-3"><i class="fa-duotone fa-files" aria-hidden="true"></i></span>
                    <div>
                        <h2 class="block-title mb-1">Stranice</h2>
                        <span class="admin-count">{{ number_format($pages->total(), 0, ',', '.') }} stranica</span>
                    </div>
                </div>
                <form action="{{ route('pages') }}" method="GET" class="admin-toolbar-group admin-directory-search">
                    <div class="admin-search">
                        <i class="fa-regular fa-magnifying-glass" aria-hidden="true"></i>
                        <input type="search" class="form-control" id="search-input" name="search" placeholder="Naziv stranice" value="{{ request()->query('search') }}">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fa-duotone fa-magnifying-glass mr-1" aria-hidden="true"></i> Pretraži</button>
                    @if(request()->filled('search'))
                        <a href="{{ route('pages') }}" class="btn btn-secondary"><i class="fa-regular fa-xmark mr-1" aria-hidden="true"></i> Očisti</a>
                    @endif
                </form>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                <table class="table table-striped table-borderless table-vcenter admin-data-table">
                    <thead>
                    <tr>
                        <th>Naziv</th>
                        <th>Podgrupa</th>
                        <th class="text-center">Status</th>
                        <th class="text-right">Radnje</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($pages as $page)
                        <tr>
                            <td data-label="Naziv">
                                <a href="{{ route('pages.edit', ['page' => $page]) }}">{{ $page->title }}</a>
                            </td>
                            <td data-label="Podgrupa">{{ $page->subgroup ?: '—' }}</td>
                            <td class="text-center" data-label="Status">
                                @if ($page->status)
                                    <span class="text-success font-w600"><i class="fa-duotone fa-circle-check mr-1" aria-hidden="true"></i> Aktivna</span>
                                @else
                                    <span class="text-muted font-w600"><i class="fa-duotone fa-circle-xmark mr-1" aria-hidden="true"></i> Neaktivna</span>
                                @endif
                            </td>
                            <td class="text-right" data-label="Radnje">
                                <a class="btn btn-sm btn-alt-secondary" href="{{ route('pages.edit', ['page' => $page]) }}" title="Uredi stranicu" aria-label="Uredi {{ $page->title }}">
                                    <i class="fa-duotone fa-pen-to-square" aria-hidden="true"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center text-muted py-5" colspan="4">Nema info stranica.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
                </div>
                {{ $pages->links() }}
            </div>
        </div>
        <!-- END Posts -->
    </div>
    <!-- END Page Content -->

@endsection

@push('js_after')

@endpush
