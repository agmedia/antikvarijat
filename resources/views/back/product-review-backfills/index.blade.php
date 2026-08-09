@extends('back.layouts.backend')

@section('content')
    <div class="admin-page-hero">
        <div class="content content-full">
            <div class="admin-page-heading">
                <div>
                    <div class="admin-page-kicker"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Recenzije</div>
                    <h1 class="admin-page-title">Pozivi za stare kupnje</h1>
                    <p class="admin-page-description">Odaberite starije razdoblje, količinu i tempo. Prije slanja uvijek ćete vidjeti točan pregled.</p>
                </div>
                <div class="admin-page-actions">
                    <a class="btn btn-alt-primary px-3 text-nowrap" href="{{ route('product-reviews.index') }}">
                        <i class="fa-solid fa-comments mr-1"></i> Moderacija recenzija
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        @include('back.layouts.partials.session')

        @unless($available)
            <div class="alert alert-danger">
                Modul još nema potrebne tablice. Primijenite novu migraciju ili <strong>database/035_create_product_review_backfills.sql</strong>.
            </div>
        @endunless

        @unless($enabled)
            <div class="alert alert-warning">
                Slanje je trenutačno isključeno. Prije pokretanja postavite <strong>REVIEW_REQUEST_EMAILS_ENABLED=true</strong>.
            </div>
        @endunless

        <div class="alert alert-info">
            <strong>Automatsko pravilo ostaje netaknuto:</strong> redovno slanje i dalje uzima samo narudžbe stare točno {{ config('reviews.request_delay_days', 30) }} dana. Ovaj ekran obuhvaća tek {{ config('reviews.request_delay_days', 30) + 1 }}. dan i starije kupnje, a već poslani pozivi i već recenzirani artikli automatski se preskaču.
        </div>

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <div>
                    <h2 class="block-title mb-1">1. Pripremite pregled</h2>
                    <span class="admin-count">Ništa se ne šalje u ovom koraku</span>
                </div>
            </div>
            <div class="block-content">
                <form method="GET" action="{{ route('product-review-backfills.index') }}">
                    <input type="hidden" name="preview" value="1">
                    <div class="form-row">
                        <div class="col-md-3 mb-3">
                            <label class="admin-filter-label" for="review-backfill-from">Od datuma</label>
                            <input class="form-control" id="review-backfill-from" type="date" name="date_from" required
                                   max="{{ $latestDate }}" value="{{ old('date_from', request('date_from', now()->subYear()->toDateString())) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="admin-filter-label" for="review-backfill-to">Do datuma</label>
                            <input class="form-control" id="review-backfill-to" type="date" name="date_to" required
                                   max="{{ $latestDate }}" value="{{ old('date_to', request('date_to', $latestDate)) }}">
                            <small class="form-text text-muted">Najkasnije {{ \Carbon\Carbon::parse($latestDate)->format('d.m.Y.') }}</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="admin-filter-label" for="review-backfill-limit">Najviše poruka</label>
                            <input class="form-control" id="review-backfill-limit" type="number" name="limit" required min="1" max="{{ $maxOrders }}"
                                   value="{{ old('limit', request('limit', 1000)) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="admin-filter-label" for="review-backfill-interval">Razmak slanja</label>
                            <select class="form-control" id="review-backfill-interval" name="interval_seconds">
                                @foreach($intervalOptions as $seconds)
                                    <option value="{{ $seconds }}" @if((int) old('interval_seconds', request('interval_seconds', $defaultInterval)) === $seconds) selected @endif>
                                        svakih {{ $seconds }} s{{ $seconds === 5 ? ' — preporučeno' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary px-4 text-nowrap" type="submit" @unless($available) disabled @endunless>
                        <i class="fa-solid fa-magnifying-glass mr-1"></i> Pregledaj primatelje
                    </button>
                </form>
            </div>
        </div>

        @if($preview)
            @php
                $hours = intdiv($preview['estimated_seconds'], 3600);
                $minutes = (int) ceil(($preview['estimated_seconds'] % 3600) / 60);
                $duration = $hours > 0 ? $hours . ' h ' . $minutes . ' min' : max(1, $minutes) . ' min';
            @endphp
            <div class="block block-rounded border border-primary">
                <div class="block-header block-header-default">
                    <div>
                        <h2 class="block-title mb-1">2. Potvrdite batch</h2>
                        <span class="admin-count">Pronađeno {{ number_format($preview['eligible_count'], 0, ',', '.') }}, šalje se najviše {{ number_format($preview['selected_count'], 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="block-content">
                    <div class="row mb-4">
                        <div class="col-md-4 mb-2"><strong>{{ number_format($preview['selected_count'], 0, ',', '.') }}</strong><br><span class="text-muted">poruka u batchu</span></div>
                        <div class="col-md-4 mb-2"><strong>{{ $preview['values']['interval_seconds'] }} sekundi</strong><br><span class="text-muted">razmak između poruka</span></div>
                        <div class="col-md-4 mb-2"><strong>oko {{ $duration }}</strong><br><span class="text-muted">procijenjeno trajanje</span></div>
                    </div>

                    @if($preview['orders']->isNotEmpty())
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-striped table-vcenter">
                                <thead><tr><th>Narudžba</th><th>Datum kupnje/slanja</th><th>Primatelj</th></tr></thead>
                                <tbody>
                                @foreach($preview['orders'] as $order)
                                    <tr>
                                        <td><a href="{{ route('orders.show', $order) }}">#{{ $order->id }}</a></td>
                                        <td>{{ $order->eligible_date->format('d.m.Y. H:i') }}</td>
                                        <td>{{ $order->masked_email }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($preview['selected_count'] > $preview['orders']->count())
                            <p class="small text-muted">Prikazano je prvih {{ $preview['orders']->count() }} primatelja.</p>
                        @endif
                    @endif

                    @if($preview['selected_count'] > 0)
                        <form method="POST" action="{{ route('product-review-backfills.store') }}" onsubmit="return confirm('Pokrenuti stvarno slanje za {{ $preview['selected_count'] }} primatelja?');">
                            @csrf
                            <input type="hidden" name="date_from" value="{{ $preview['values']['date_from'] }}">
                            <input type="hidden" name="date_to" value="{{ $preview['values']['date_to'] }}">
                            <input type="hidden" name="limit" value="{{ $preview['values']['limit'] }}">
                            <input type="hidden" name="interval_seconds" value="{{ $preview['values']['interval_seconds'] }}">
                            <div class="custom-control custom-checkbox mb-3">
                                <input class="custom-control-input" id="review-backfill-confirmed" type="checkbox" name="confirmed" value="1" required>
                                <label class="custom-control-label" for="review-backfill-confirmed">Provjerio/la sam razdoblje, količinu i tempo slanja.</label>
                            </div>
                            <button class="btn btn-success px-4 text-nowrap" type="submit" @unless($available && $enabled) disabled @endunless>
                                <i class="fa-solid fa-paper-plane mr-1"></i> Pokreni slanje
                            </button>
                        </form>
                    @else
                        <div class="alert alert-warning mb-0">Za odabrane uvjete nema novih primatelja.</div>
                    @endif
                </div>
            </div>
        @endif

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <div>
                    <h2 class="block-title mb-1">Povijest slanja</h2>
                    <span class="admin-count">Zadnjih {{ $batches->count() }} batcheva</span>
                </div>
                <a class="btn btn-sm btn-alt-primary px-3 text-nowrap" href="{{ route('product-review-backfills.index') }}">
                    <i class="fa-solid fa-rotate mr-1"></i> Osvježi status
                </a>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-borderless table-striped table-vcenter admin-data-table">
                        <thead><tr><th>Batch</th><th>Razdoblje i tempo</th><th>Napredak</th><th>Rezultat</th><th>Status</th><th class="text-right" style="width: 130px;">Akcija</th></tr></thead>
                        <tbody>
                        @forelse($batches as $batch)
                            @php
                                $percent = $batch->total_count > 0 ? min(100, (int) round(($batch->processed_count / $batch->total_count) * 100)) : 100;
                                $badge = ['pending' => 'warning', 'running' => 'primary', 'completed' => 'success', 'cancelled' => 'secondary'][$batch->status] ?? 'secondary';
                            @endphp
                            <tr>
                                <td data-label="Batch"><strong>#{{ $batch->id }}</strong><div class="small text-muted">{{ $batch->created_at->format('d.m.Y. H:i') }}</div></td>
                                <td data-label="Razdoblje">{{ $batch->date_from->format('d.m.Y.') }} – {{ $batch->date_to->format('d.m.Y.') }}<div class="small text-muted">svakih {{ $batch->interval_seconds }} s · limit {{ number_format($batch->requested_limit, 0, ',', '.') }}</div></td>
                                <td data-label="Napredak" style="min-width:180px;">
                                    <div>{{ number_format($batch->processed_count, 0, ',', '.') }} / {{ number_format($batch->total_count, 0, ',', '.') }}</div>
                                    <div class="progress" style="height:6px;"><div class="progress-bar" role="progressbar" style="width:{{ $percent }}%" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div></div>
                                </td>
                                <td data-label="Rezultat"><span class="text-success">{{ $batch->sent_count }} poslano</span><div class="small text-muted">{{ $batch->skipped_count }} preskočeno · {{ $batch->failed_count }} neuspjelo</div></td>
                                <td data-label="Status"><span class="badge badge-{{ $badge }}">{{ $statuses[$batch->status] ?? $batch->status }}</span></td>
                                <td class="text-right">
                                    @if($batch->isActive())
                                        <form method="POST" action="{{ route('product-review-backfills.cancel', $batch) }}" onsubmit="return confirm('Zaustaviti batch #{{ $batch->id }}? Već poslane poruke ostaju poslane.');">
                                            @csrf
                                            <button class="btn btn-sm btn-alt-danger px-3 text-nowrap" type="submit">
                                                <i class="fa-solid fa-stop mr-1" aria-hidden="true"></i> Zaustavi
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">Još nema pokrenutih povijesnih slanja.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
