@extends('back.layouts.backend')
@push('css_before')

    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('js/plugins/select2/css/select2.min.css') }}">


@endpush

@section('content')

    <div class="admin-page-hero">
        <div class="content content-full">
            <div class="admin-page-heading">
                <div>
                    <div class="admin-page-kicker"><i class="fa-duotone fa-users" aria-hidden="true"></i> Administracija</div>
                    <h1 class="admin-page-title">Korisnici</h1>
                    <p class="admin-page-description">Pretražite korisničke račune i uredite njihove pristupne uloge.</p>
                </div>
                <div class="admin-page-actions">
                    <a class="btn btn-primary" href="{{ route('users.create') }}"><i class="fa-duotone fa-user-plus mr-1" aria-hidden="true"></i> Novi korisnik</a>
                </div>
            </div>
        </div>
    </div>


    <!-- Page Content -->
    <div class="content">
    @include('back.layouts.partials.session')


        <!-- All Orders -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <div class="d-flex align-items-center min-width-0">
                    <span class="admin-section-icon mr-3"><i class="fa-duotone fa-address-book" aria-hidden="true"></i></span>
                    <div>
                        <h2 class="block-title mb-1">Svi korisnici</h2>
                        <span class="admin-count">{{ number_format($users->total(), 0, ',', '.') }} {{ $users->total() === 1 ? 'korisnik' : 'korisnika' }}</span>
                    </div>
                </div>
                <form action="{{ route('users') }}" method="GET" class="admin-toolbar-group admin-directory-search admin-user-filters">
                    <div class="admin-search">
                        <i class="fa-regular fa-magnifying-glass" aria-hidden="true"></i>
                        <input type="search" class="form-control" id="search-input" name="search" placeholder="Ime ili e-mail" value="{{ request()->query('search') }}" aria-label="Pretraži korisnike">
                    </div>
                    <label class="sr-only" for="role-select">Uloga korisnika</label>
                    <select class="form-control admin-user-role-select" id="role-select" name="role" aria-label="Filtriraj korisnike po ulozi">
                        <option value="">Sve uloge</option>
                        @foreach($roleOptions as $role => $label)
                            <option value="{{ $role }}" {{ request('role') === $role ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary"><i class="fa-duotone fa-magnifying-glass mr-1" aria-hidden="true"></i> Pretraži</button>
                    @if(request()->filled('search') || request()->filled('role'))
                        <a href="{{ route('users') }}" class="btn btn-secondary admin-user-filter-clear" title="Očisti filtere" aria-label="Očisti filtere"><i class="fa-regular fa-xmark" aria-hidden="true"></i></a>
                    @endif
                </form>
            </div>
            <div class="block-content">
                <!-- All Orders Table -->
                <div class="table-responsive">
                    <table class="table table-borderless table-striped table-vcenter admin-data-table">
                        <thead>
                        <tr>
                            <th>Korisnik</th>
                            <th>Email</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Uloga</th>
                            <th class="text-right">Detalji</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td data-label="Korisnik">
                                    <a class="font-w600" href="{{ route('users.edit', ['user' => $user]) }}">{{ $user->name }}</a>
                                </td>
                                <td data-label="E-mail">{{ $user->email }}</td>
                                <td class="text-center" data-label="Status">
                                    @if(optional($user->details)->status)
                                        <span class="text-success font-w600"><i class="fa-duotone fa-circle-check mr-1" aria-hidden="true"></i> Aktivan</span>
                                    @else
                                        <span class="text-muted font-w600"><i class="fa-duotone fa-circle-minus mr-1" aria-hidden="true"></i> Neaktivan</span>
                                    @endif
                                </td>
                                <td class="text-center" data-label="Uloga">
                                    @php($userRole = optional($user->details)->role)
                                    {{ $roleLabels[$userRole] ?? ($userRole ? ucfirst($userRole) : 'Nije dodijeljena') }}
                                </td>
                                <td class="text-right" data-label="Radnje">
                                    <div class="admin-user-actions">
                                        @if(app(\App\Services\UserImpersonationService::class)->canImpersonate(auth()->user(), $user))
                                            <form action="{{ route('users.impersonate', ['user' => $user]) }}" method="POST" data-impersonation-form data-customer-name="{{ $user->name }}">
                                                @csrf
                                                <button class="btn btn-sm btn-alt-primary" type="submit" title="Prijavi se kao korisnik" aria-label="Prijavi se kao {{ $user->name }}">
                                                    <i class="fa-duotone fa-user-shield" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <a class="btn btn-sm btn-alt-secondary" href="{{ route('users.edit', ['user' => $user]) }}" title="Uredi korisnika" aria-label="Uredi korisnika">
                                            <i class="fa-duotone fa-pen-to-square" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $users->links() }}

            </div>
        </div>
        <!-- END All Orders -->
    </div>

@endsection

@push('css_after')
    <style>
        .admin-user-filters { width: min(100%, 42rem); flex-wrap: nowrap; }
        .admin-user-filters .admin-search { min-width: 16rem; flex: 1 1 21rem; }
        .admin-user-role-select { width: 11.5rem; flex: 0 0 11.5rem; }
        .admin-user-filter-clear { width: 2.5rem; flex: 0 0 2.5rem; padding: 0 !important; }
        .admin-user-actions { display: inline-flex; align-items: center; justify-content: flex-end; gap: .35rem; }
        .admin-user-actions form { margin: 0; }
        @media (max-width: 991.98px) {
            .admin-user-filters { display: grid; width: 100%; grid-template-columns: minmax(0, 1fr) 11rem auto; }
        }
        @media (max-width: 575.98px) {
            .admin-user-filters { grid-template-columns: 1fr 1fr; }
            .admin-user-filters .admin-search { min-width: 0; grid-column: 1 / -1; }
            .admin-user-role-select { width: 100%; min-width: 0; }
            .admin-user-filters .btn { width: 100%; }
        }
    </style>
@endpush

@push('js_after')
    <script>
        document.querySelectorAll('[data-impersonation-form]').forEach(form => {
            form.addEventListener('submit', event => {
                const customerName = form.dataset.customerName || 'ovog korisnika';

                if (! window.confirm(`Prijaviti se kao ${customerName}?`)) {
                    event.preventDefault();
                }
            });
        });
    </script>
@endpush
