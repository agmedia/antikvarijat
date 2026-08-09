<div class="admin-page-hero">
    <div class="content content-full">
        <div class="admin-page-heading">
            <div>
                <div class="admin-page-kicker"><i class="fa-duotone {{ $icon }}"></i> Katalog</div>
                <h1 class="admin-page-title">{{ $title }}</h1>
                <p class="admin-page-description">{{ $description }}</p>
            </div>
            <div class="admin-page-actions">
                <a class="btn btn-primary" href="{{ route($createRoute) }}">
                    <i class="fa-duotone fa-plus mr-1"></i> {{ $createLabel }}
                </a>
            </div>
        </div>
    </div>
</div>

<div class="content content-full">
    @include('back.layouts.partials.session')

    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <div class="admin-toolbar">
                <div class="d-flex align-items-center">
                    <span class="admin-section-icon mr-3"><i class="fa-duotone {{ $icon }}"></i></span>
                    <div>
                        <h2 class="block-title mb-1">{{ $listTitle }}</h2>
                        <span class="admin-count">{{ number_format($items->total(), 0, ',', '.') }} {{ $countLabel ?? 'zapisa' }}</span>
                    </div>
                </div>

                <form action="{{ route($indexRoute) }}" method="GET" class="admin-toolbar-group admin-directory-search">
                    <div class="admin-search">
                        <i class="fa-regular fa-magnifying-glass"></i>
                        <input type="search" class="form-control" name="search" placeholder="{{ $searchPlaceholder }}" value="{{ request()->query('search') }}" aria-label="{{ $searchPlaceholder }}">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fa-regular fa-magnifying-glass mr-1"></i> Pretraži</button>
                    @if(request()->filled('search'))
                        <a href="{{ route($indexRoute) }}" class="btn btn-secondary"><i class="fa-regular fa-xmark mr-1"></i> Očisti</a>
                    @endif
                </form>
            </div>
        </div>

        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-borderless table-striped table-vcenter admin-directory-table">
                    <thead>
                    <tr>
                        <th>Naziv</th>
                        <th class="text-right">Status</th>
                        <th class="text-right">Radnje</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td data-label="Naziv">
                                <a class="font-w700" href="{{ route($editRoute, [$routeParameter => $item]) }}">{{ $item->title }}</a>
                            </td>
                            <td class="text-right" data-label="Status">
                                @if ($item->status)
                                    <span class="badge badge-success"><i class="fa-solid fa-circle-check mr-1"></i> Aktivan</span>
                                @else
                                    <span class="badge badge-secondary"><i class="fa-solid fa-circle-minus mr-1"></i> Neaktivan</span>
                                @endif
                            </td>
                            <td class="text-right" data-label="Radnje">
                                <span class="admin-row-actions">
                                    <a href="{{ route($editRoute, [$routeParameter => $item]) }}" class="btn btn-sm btn-alt-secondary" data-toggle="tooltip" title="Uredi" aria-label="Uredi {{ $item->title }}">
                                        <i class="fa-duotone fa-pen-to-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-alt-danger" onclick="event.preventDefault(); deleteItem({{ $item->id }}, '{{ route($destroyRoute) }}');" data-toggle="tooltip" title="Obriši" aria-label="Obriši {{ $item->title }}">
                                        <i class="fa-duotone fa-trash-can"></i>
                                    </button>
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <div class="admin-empty-state">
                                    <i class="fa-duotone {{ $icon }}"></i>
                                    <h3>{{ $emptyTitle }}</h3>
                                    <p class="text-muted mb-0">{{ $emptyDescription }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{ $items->appends(request()->query())->links() }}
        </div>
    </div>
</div>

@push('css_after')
    <style>
        .admin-directory-table th:nth-child(2), .admin-directory-table td:nth-child(2) { width: 10rem; }
        .admin-directory-table th:last-child, .admin-directory-table td:last-child { width: 7.5rem; }
        @media (min-width: 768px) { .admin-directory-search { flex-wrap: nowrap; } .admin-directory-search .admin-search { min-width: 17rem; } }
        @media (max-width: 575.98px) {
            .admin-directory-table, .admin-directory-table tbody, .admin-directory-table tr, .admin-directory-table td { display: block; width: 100% !important; }
            .admin-directory-table thead { display: none; }
            .admin-directory-table tr { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .5rem .75rem; padding: .85rem; border-bottom: 1px solid var(--admin-line); }
            .admin-directory-table tr:last-child { border-bottom: 0; }
            .admin-directory-table td { padding: 0 !important; border: 0 !important; background: transparent !important; text-align: left !important; }
            .admin-directory-table td:first-child { grid-column: 1 / -1; font-size: var(--admin-type-body); }
            .admin-directory-table td:nth-child(2) { align-self: center; }
            .admin-directory-table td:last-child { text-align: right !important; }
        }
    </style>
@endpush
