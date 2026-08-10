@extends('back.layouts.backend')
@push('css_before')

    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('js/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('js/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.min.css') }}">


@endpush

@section('content')

    <div class="admin-page-hero">
        <div class="content content-full">
            <div class="admin-page-heading">
                <div>
                    <div class="admin-page-kicker"><i class="fa-duotone fa-badge-percent" aria-hidden="true"></i> Marketing</div>
                    <h1 class="admin-page-title">Akcije</h1>
                    <p class="admin-page-description">Upravljajte promotivnim razdobljima, popustima i njihovom aktivnošću.</p>
                </div>
                <div class="admin-page-actions">
                    <a class="btn btn-primary" href="{{ route('actions.create') }}">
                        <i class="fa-duotone fa-plus mr-1" aria-hidden="true"></i> Nova akcija
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="content">
    @include('back.layouts.partials.session')


        <!-- All Products -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <div class="d-flex align-items-center min-width-0">
                    <span class="admin-section-icon mr-3"><i class="fa-duotone fa-tags" aria-hidden="true"></i></span>
                    <div>
                        <h2 class="block-title mb-1">Sve akcije</h2>
                        <span class="admin-count">{{ number_format($actions->total(), 0, ',', '.') }} akcija</span>
                    </div>
                </div>
            </div>


            <div class="block-content">
                <!-- All Products Table -->
                <div class="table-responsive">
                    <table class="table table-borderless table-striped table-vcenter admin-data-table">
                        <thead>
                        <tr>
                            <th class="text-left">Naziv</th>
                            <th>Vrijedi od</th>
                            <th>Vrijedi do</th>
                            <th>Popust</th>
                            <th class="text-center font-size-sm">Status</th>
                            <th class="text-right">Radnje</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($actions as $action)
                            <tr>
                                <td data-label="Naziv">
                                    <a class="font-w600" href="{{ route('actions.edit', ['action' => $action]) }}">{{ $action->title }}</a>
                                </td>
                                <td data-label="Vrijedi od">{{ $action->date_start ? \Illuminate\Support\Carbon::make($action->date_start)->format('d.m.Y.') : '—' }}</td>
                                <td data-label="Vrijedi do">{{ $action->date_end ? \Illuminate\Support\Carbon::make($action->date_end)->format('d.m.Y.') : '—' }}</td>
                                <td data-label="Popust"><strong>{{ $action->discount }}</strong></td>
                                <td class="text-center" data-label="Status">
                                    @include('back.layouts.partials.status', ['status' => $action->status, 'simple' => true])
                                </td>
                                <td class="text-right" data-label="Radnje">
                                    <a class="btn btn-sm btn-alt-secondary" href="{{ route('actions.edit', ['action' => $action]) }}" title="Uredi akciju" aria-label="Uredi {{ $action->title }}">
                                        <i class="fa-duotone fa-pen-to-square" aria-hidden="true"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-alt-danger" onclick="event.preventDefault(); deleteItem({{ $action->id }}, '{{ route('actions.destroy.api') }}');" title="Obriši akciju" aria-label="Obriši {{ $action->title }}"><i class="fa-duotone fa-trash-can" aria-hidden="true"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center text-muted py-5" colspan="6">
                                    Nema akcija.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $actions->links() }}
            </div>
        </div>
        <!-- END All Products -->
    </div>
    <!-- END Page Content -->

@endsection

@push('js_after')


    <script src="{{ \App\Helpers\Asset::url('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ \App\Helpers\Asset::url('js/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>

    <!-- Page JS Helpers (CKEditor 5 plugins) -->
    <script>jQuery(function(){Dashmix.helpers(['select2','datepicker']);});</script>
    <script>
        $(() => {
            $('#category-select').select2({
                placeholder: 'Odaberite kategoriju'
            });
            $('#author-select').select2({
                placeholder: 'Odaberite autora'
            });
            $('#publisher-select').select2({
                placeholder: 'Odaberite izdavača'
            });
        })
    </script>
    <script>
        $("#checkAll").click(function () {
            $('input:checkbox').not(this).prop('checked', this.checked);
        });
    </script>

@endpush
