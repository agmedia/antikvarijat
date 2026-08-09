@extends('back.layouts.backend')

@section('content')
    <div class="admin-page-hero">
        <div class="content content-full">
            <div class="admin-page-heading">
                <div>
                    <div class="admin-page-kicker"><i class="fa-duotone fa-map-location-dot"></i> Postavke dostave</div>
                    <h1 class="admin-page-title">Geo zone</h1>
                    <p class="admin-page-description">Upravljajte područjima na koja se primjenjuju pravila dostave, poreza i dostupnosti.</p>
                </div>
                <div class="admin-page-actions">
                    <a class="btn btn-primary" href="{{ route('geozones.create') }}">
                        <i class="fa-duotone fa-plus mr-1"></i> Nova geo zona
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        @include('back.layouts.partials.session')

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <div class="d-flex align-items-center min-width-0">
                    <span class="admin-section-icon mr-3"><i class="fa-duotone fa-map"></i></span>
                    <div>
                        <h2 class="block-title mb-1">Popis geo zona</h2>
                        <span class="admin-count">{{ number_format($geo_zones->count(), 0, ',', '.') }} zona</span>
                    </div>
                </div>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-striped table-borderless table-vcenter admin-data-table">
                        <thead>
                        <tr>
                            <th>Naziv</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Radnje</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($geo_zones as $geo_zone)
                            <tr>
                                <td data-label="Naziv">
                                    <a class="font-w600" href="{{ route('geozones.edit', ['geozone' => $geo_zone->id]) }}">{{ $geo_zone->title }}</a>
                                </td>
                                <td class="text-center" data-label="Status">
                                    @include('back.layouts.partials.status', ['status' => $geo_zone->status])
                                </td>
                                <td class="text-right" data-label="Radnje">
                                    <a class="btn btn-sm btn-alt-secondary" href="{{ route('geozones.edit', ['geozone' => $geo_zone->id]) }}" title="Uredi" aria-label="Uredi geo zonu {{ $geo_zone->title }}">
                                        <i class="fa-duotone fa-pen-to-square"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-muted py-5" colspan="3">Nema geo zona.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
