@extends('back.layouts.backend')

@section('content')
    <div class="admin-page-hero">
        <div class="content content-full">
            <div class="admin-page-heading">
                <div>
                    <div class="admin-page-kicker"><i class="fa-duotone fa-envelope-open-text" aria-hidden="true"></i> Marketing</div>
                    <h1 class="admin-page-title">Newsletter prijave</h1>
                    <p class="admin-page-description">Pregledajte pretplatnike, izvore prijave i GDPR privole.</p>
                </div>
                <div class="admin-page-actions">
                    <form method="post" action="{{ route('newsletter.caches.clear') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-warning"><i class="fa-duotone fa-arrows-rotate mr-1" aria-hidden="true"></i> Očisti cache</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <div class="d-flex align-items-center min-width-0">
                    <span class="admin-section-icon mr-3"><i class="fa-duotone fa-users" aria-hidden="true"></i></span>
                    <div>
                        <h2 class="block-title mb-1">Pretplatnici</h2>
                        <span class="admin-count">{{ number_format($subscribers->total(), 0, ',', '.') }} prijava</span>
                    </div>
                </div>
                @if(request()->query())
                <div class="block-options">
                    <a class="btn btn-primary" href="{{ route('newsletter.subscribers') }}">
                        <i class="fa-regular fa-xmark mr-1" aria-hidden="true"></i> Očisti filter
                    </a>
                </div>
                @endif
            </div>

            <div class="block-content">
                @if (session('status'))
                    <div class="alert alert-info">
                        <pre class="mb-0" style="white-space: pre-wrap;">{{ session('status') }}</pre>
                    </div>
                @endif

                <div class="admin-filter-panel p-3 mb-3">
                    <form method="get" action="{{ route('newsletter.subscribers') }}">
                        <div class="form-group row mb-0">
                            <div class="col-md-9 mb-2 mb-md-0">
                                <label class="admin-filter-label" for="newsletter-search">Pretraživanje</label>
                                <div class="input-group">
                                    <input type="text"
                                           class="form-control"
                                           id="newsletter-search"
                                           name="search"
                                           value="{{ request()->input('search') }}"
                                           placeholder="E-mail, korisnik ili broj narudžbe">
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

                <div class="table-responsive">
                    <table class="table table-borderless table-striped table-vcenter admin-data-table admin-newsletter-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>User ID</th>
                            <th>Ime i prezime</th>
                            <th>Order ID</th>
                            <th>Izvor</th>
                            <th>GDPR</th>
                            <th>Prijavljen</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($subscribers as $subscriber)
                            @php
                                $fullName = trim((optional(optional($subscriber->user)->details)->fname ?? '') . ' ' . (optional(optional($subscriber->user)->details)->lname ?? ''));
                            @endphp
                            <tr>
                                <td data-label="ID">{{ $subscriber->id }}</td>
                                <td data-label="E-mail">{{ $subscriber->email }}</td>
                                <td data-label="User ID">{{ $subscriber->user_id ?: '—' }}</td>
                                <td data-label="Ime i prezime">
                                    @if ($subscriber->user_id)
                                        {{ $fullName !== '' ? $fullName : (optional($subscriber->user)->name ?? '—') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td data-label="Order ID">{{ $subscriber->order_id ?: '—' }}</td>
                                <td data-label="Izvor">{{ $subscriber->source }}</td>
                                <td data-label="GDPR"><span class="font-w600 {{ $subscriber->gdpr ? 'text-success' : 'text-muted' }}">{{ $subscriber->gdpr ? 'Da' : 'Ne' }}</span></td>
                                <td data-label="Prijavljen">{{ optional($subscriber->subscribed_at)->format('d.m.Y. H:i') ?: optional($subscriber->created_at)->format('d.m.Y. H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center text-muted py-5" colspan="8">Nema newsletter prijava.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $subscribers->appends(request()->query())->links() }}
            </div>
        </div>
</div>
@endsection

@push('css_after')
    <style>
        .admin-newsletter-table th:last-child,
        .admin-newsletter-table td:last-child { width: 9.5rem !important; }
        @media (max-width: 767.98px) {
            .admin-newsletter-table th:last-child,
            .admin-newsletter-table td:last-child { width: 100% !important; }
        }
    </style>
@endpush
