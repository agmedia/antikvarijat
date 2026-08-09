@extends('back.layouts.backend')

@section('content')
    <div class="admin-page-hero">
        <div class="content content-full">
            <div class="admin-page-heading">
                <div>
                    <div class="admin-page-kicker"><i class="fa-duotone fa-file-signature" aria-hidden="true"></i> Prodaja</div>
                    <h1 class="admin-page-title">Jednostrani raskidi ugovora</h1>
                    <p class="admin-page-description">Pregledajte zaprimljene izjave, povezane narudžbe i tijek obrade zahtjeva.</p>
                </div>
                <div class="admin-page-actions">
                    <a class="btn btn-outline-primary" href="{{ route('contract-withdrawal-settings.edit') }}">
                        <i class="fa-duotone fa-gear mr-1" aria-hidden="true"></i> Postavke
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
                    <span class="admin-section-icon mr-3"><i class="fa-duotone fa-inbox-in" aria-hidden="true"></i></span>
                    <div>
                        <h2 class="block-title mb-1">Zaprimljene izjave</h2>
                        <span class="admin-count">{{ number_format($withdrawals->total(), 0, ',', '.') }} zahtjeva</span>
                    </div>
                </div>
            </div>

            <div class="block-content bg-body-dark">
                <form method="GET" action="{{ route('contract-withdrawals.index') }}">
                    <div class="form-row align-items-center">
                        <div class="col-md-7 mb-2">
                            <label class="admin-filter-label" for="withdrawal-search">Pretraživanje</label>
                            <input
                                class="form-control"
                                id="withdrawal-search"
                                type="search"
                                name="search"
                                value="{{ $search }}"
                                placeholder="Referenca, narudžba, ime ili e-mail..."
                            >
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="admin-filter-label" for="withdrawal-status">Status</label>
                            <select class="form-control" id="withdrawal-status" name="status">
                                <option value="">Svi statusi</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @if($selectedStatus === $value) selected @endif>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <span class="admin-filter-label d-none d-md-block" aria-hidden="true">&nbsp;</span>
                            <button class="btn btn-primary btn-block" type="submit">
                                <i class="fa-duotone fa-magnifying-glass mr-1"></i> Pretraži
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-borderless table-striped table-vcenter admin-data-table">
                        <thead>
                            <tr>
                                <th>Referenca</th>
                                <th>Podneseno</th>
                                <th>Status</th>
                                <th>Kupac</th>
                                <th>Narudžba</th>
                                <th class="text-center" style="width: 90px;">Akcija</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($withdrawals as $withdrawal)
                                <tr>
                                    <td data-label="Referenca">
                                        <a class="font-w600" href="{{ route('contract-withdrawals.show', $withdrawal) }}">
                                            {{ $withdrawal->reference }}
                                        </a>
                                    </td>
                                    <td class="text-nowrap" data-label="Podneseno">
                                        {{ optional($withdrawal->submitted_at)->format('d.m.Y. H:i') }}
                                    </td>
                                    <td data-label="Status">
                                        <span class="badge badge-{{ $statusColors[$withdrawal->status] ?? 'secondary' }}">
                                            {{ $statuses[$withdrawal->status] ?? $withdrawal->status }}
                                        </span>
                                    </td>
                                    <td data-label="Kupac">
                                        <div class="font-w600">{{ $withdrawal->full_name }}</div>
                                        <a href="mailto:{{ $withdrawal->email }}">{{ $withdrawal->email }}</a>
                                    </td>
                                    <td data-label="Narudžba">
                                        @if ($withdrawal->order)
                                            <a href="{{ route('orders.show', $withdrawal->order) }}">#{{ $withdrawal->order->id }}</a>
                                        @else
                                            {{ $withdrawal->order_number }}
                                        @endif
                                    </td>
                                    <td class="text-center" data-label="Radnje">
                                        <a
                                            class="btn btn-sm btn-alt-primary"
                                            href="{{ route('contract-withdrawals.show', $withdrawal) }}"
                                            title="Otvori zahtjev"
                                        >
                                            <i class="fa-duotone fa-eye" aria-hidden="true"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center text-muted py-5" colspan="6">
                                        Nema pronađenih izjava o raskidu ugovora.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $withdrawals->links() }}
            </div>
        </div>
    </div>
@endsection
