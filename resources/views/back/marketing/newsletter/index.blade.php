@extends('back.layouts.backend')

@section('content')
    @php
        $adminUser = auth()->user();
        $canManageNewsletter = $adminUser
            && $adminUser->isAdministrator()
            && (bool) optional($adminUser->details)->status;
    @endphp

    <div class="admin-page-hero">
        <div class="content content-full">
            <div class="admin-page-heading">
                <div>
                    <div class="admin-page-kicker"><i class="fa-duotone fa-envelope-open-text" aria-hidden="true"></i> Marketing</div>
                    <h1 class="admin-page-title">Newsletter prijave</h1>
                    <p class="admin-page-description">Pregledajte pretplatnike, izvore prijave i GDPR privole.</p>
                </div>
                <div class="admin-page-actions">
                    @if($canManageNewsletter)
                        <form id="mailchimp-sync-form"
                              method="post"
                              action="{{ route('newsletter.mailchimp.sync') }}"
                              data-batch="20">
                            @csrf
                            <button type="submit" class="btn btn-success" {{ ($pendingMailchimpCount ?? 0) === 0 ? 'disabled' : '' }}>
                                <i class="fa-duotone fa-cloud-arrow-up mr-1" aria-hidden="true"></i>
                                <span data-mailchimp-button-label>Pošalji u Mailchimp ({{ $pendingMailchimpCount ?? 0 }})</span>
                            </button>
                        </form>
                    @endif
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
                @include('back.layouts.partials.session')

                @if (session('status'))
                    <div class="alert alert-info">
                        <pre class="mb-0" style="white-space: pre-wrap;">{{ session('status') }}</pre>
                    </div>
                @endif

                <div id="mailchimp-sync-status" class="alert d-none" role="status" aria-live="polite">
                    <i class="fa-duotone fa-arrows-rotate mr-2" aria-hidden="true"></i>
                    <span data-mailchimp-status-text></span>
                </div>

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

                @if($canManageNewsletter)
                    <form id="newsletter-cleanup-form"
                          method="post"
                          action="{{ route('newsletter.subscribers.destroy') }}">
                        @csrf
                        @method('DELETE')

                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between border rounded p-3 mb-3">
                            <div class="text-muted mb-2 mb-md-0 mr-md-3">
                                Odabrani kontakt prvo se reverzibilno arhivira u Mailchimpu, a lokalni zapis zatim se trajno briše.
                                Moguće je odabrati samo anonimne footer prijave bez korisnika i narudžbe.
                            </div>
                            <button type="submit" class="btn btn-outline-danger flex-shrink-0" data-newsletter-cleanup-submit disabled>
                                <i class="fa-duotone fa-user-slash mr-1" aria-hidden="true"></i>
                                <span data-newsletter-cleanup-label>Arhiviraj i ukloni (0)</span>
                            </button>
                        </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-borderless table-striped table-vcenter admin-data-table admin-newsletter-table">
                        <thead>
                        <tr>
                            @if($canManageNewsletter)
                                <th class="admin-newsletter-select-column">
                                    <input type="checkbox"
                                           data-newsletter-select-all
                                           aria-label="Odaberi sve anonimne footer prijave na ovoj stranici">
                                </th>
                            @endif
                            <th>ID</th>
                            <th>Email</th>
                            <th>User ID</th>
                            <th>Ime i prezime</th>
                            <th>Order ID</th>
                            <th>Izvor</th>
                            <th>GDPR</th>
                            <th>Mailchimp</th>
                            <th>Prijavljen</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($subscribers as $subscriber)
                            @php
                                $fullName = trim((optional(optional($subscriber->user)->details)->fname ?? '') . ' ' . (optional(optional($subscriber->user)->details)->lname ?? ''));
                                $cleanupEligible = $subscriber->source === 'footer'
                                    && (int) $subscriber->user_id === 0
                                    && (int) $subscriber->order_id === 0;
                            @endphp
                            <tr>
                                @if($canManageNewsletter)
                                    <td class="admin-newsletter-select-column" data-label="Odabir">
                                        @if($cleanupEligible)
                                            <input type="checkbox"
                                                   name="subscriber_ids[]"
                                                   value="{{ $subscriber->id }}"
                                                   data-newsletter-cleanup-checkbox
                                                   aria-label="Odaberi newsletter prijavu ID {{ $subscriber->id }}"
                                                   {{ in_array((int) $subscriber->id, array_map('intval', (array) old('subscriber_ids', [])), true) ? 'checked' : '' }}>
                                        @else
                                            <span class="text-muted" title="Zaštićeno jer nije anonimna footer prijava">—</span>
                                        @endif
                                    </td>
                                @endif
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
                                <td data-label="Mailchimp">
                                    @if($subscriber->mailchimp_synced_at)
                                        <span class="badge badge-success" title="{{ $subscriber->mailchimp_synced_at->format('d.m.Y. H:i') }}">Sinkronizirano</span>
                                    @elseif($subscriber->mailchimp_last_error)
                                        <span class="badge badge-danger" title="{{ $subscriber->mailchimp_last_error }}">Greška</span>
                                    @else
                                        <span class="badge badge-secondary">Čeka</span>
                                    @endif
                                </td>
                                <td data-label="Prijavljen">{{ optional($subscriber->subscribed_at)->format('d.m.Y. H:i') ?: optional($subscriber->created_at)->format('d.m.Y. H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center text-muted py-5" colspan="{{ $canManageNewsletter ? 10 : 9 }}">Nema newsletter prijava.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                @if($canManageNewsletter)
                    </form>
                @endif

                {{ $subscribers->appends(request()->query())->links() }}
            </div>
        </div>
</div>
@endsection

@push('css_after')
    <style>
        .admin-newsletter-table .admin-newsletter-select-column { width: 3rem !important; text-align: center; }
        .admin-newsletter-table th:last-child,
        .admin-newsletter-table td:last-child { width: 9.5rem !important; }
        @media (max-width: 767.98px) {
            .admin-newsletter-table .admin-newsletter-select-column { width: 100% !important; text-align: left; }
            .admin-newsletter-table th:last-child,
            .admin-newsletter-table td:last-child { width: 100% !important; }
        }
    </style>
@endpush

@push('js_after')
    <script>
        (function () {
            var form = document.getElementById('mailchimp-sync-form');
            var statusBox = document.getElementById('mailchimp-sync-status');

            if (! form || ! statusBox || ! window.fetch) {
                return;
            }

            var button = form.querySelector('button[type="submit"]');
            var buttonLabel = form.querySelector('[data-mailchimp-button-label]');
            var buttonIcon = button ? button.querySelector('i') : null;
            var statusText = statusBox.querySelector('[data-mailchimp-status-text]');
            var batchSize = parseInt(form.dataset.batch || '20', 10);
            var pending = {{ (int) ($pendingMailchimpCount ?? 0) }};
            var storageKey = 'mailchimp-newsletter-sync:' + form.action;
            var busy = false;

            function setStatus(type, message) {
                statusBox.classList.remove('d-none', 'alert-info', 'alert-success', 'alert-warning', 'alert-danger');
                statusBox.classList.add('alert-' + type);
                statusText.textContent = message;
            }

            function updateButton() {
                button.disabled = busy || pending === 0;
                buttonLabel.textContent = busy
                    ? 'Sinkroniziram s Mailchimpom...'
                    : 'Pošalji u Mailchimp (' + pending + ')';

                if (buttonIcon) {
                    buttonIcon.classList.toggle('fa-spin', busy);
                }
            }

            function loadState() {
                try {
                    var raw = window.localStorage.getItem(storageKey);
                    return raw ? JSON.parse(raw) : null;
                } catch (error) {
                    return null;
                }
            }

            function saveState(lastId, totals) {
                try {
                    window.localStorage.setItem(storageKey, JSON.stringify({
                        last_id: lastId,
                        totals: totals
                    }));
                } catch (error) {
                    // Sync can continue even when local storage is unavailable.
                }
            }

            function clearState() {
                try {
                    window.localStorage.removeItem(storageKey);
                } catch (error) {
                    // Nothing else is needed when local storage is unavailable.
                }
            }

            function runBatch(lastId, totals) {
                var payload = new FormData(form);
                payload.append('last_id', String(lastId || 0));
                payload.append('batch', String(batchSize));

                return fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: payload,
                    credentials: 'same-origin'
                }).then(function (response) {
                    return response.json().catch(function () {
                        return {};
                    }).then(function (data) {
                        if (! response.ok || ! data.ok) {
                            var requestError = new Error(data.message || ('HTTP ' + response.status));
                            requestError.syncData = data;
                            throw requestError;
                        }

                        return data;
                    });
                }).then(function (data) {
                    totals.processed += Number(data.processed || 0);
                    totals.synced += Number(data.synced || 0);
                    totals.failed += Number(data.failed || 0);
                    totals.total = totals.total || Number(data.total || 0);
                    pending = Number(data.pending || 0);

                    var progress = 'Mailchimp sinkronizacija — obrađeno ' + totals.processed;
                    if (totals.total > 0) {
                        progress += ' od ' + totals.total;
                    }
                    progress += ', uspješno ' + totals.synced + ', greške ' + totals.failed + '.';

                    setStatus(totals.failed > 0 ? 'warning' : 'info', progress);
                    saveState(Number(data.last_id || 0), totals);

                    if (data.finished) {
                        clearState();

                        var finalMessage = 'Mailchimp sinkronizacija je završena. Uspješno: '
                            + totals.synced + ', greške: ' + totals.failed + '.';

                        if (pending > 0) {
                            finalMessage += ' Za ponovni pokušaj ostalo je ' + pending + ' prijava.';
                        }

                        setStatus(pending > 0 ? 'warning' : 'success', finalMessage);
                        return data;
                    }

                    return runBatch(Number(data.last_id || 0), totals);
                });
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                if (busy || pending === 0) {
                    return;
                }

                var saved = loadState();
                var lastId = saved ? Number(saved.last_id || 0) : 0;
                var totals = saved && saved.totals ? saved.totals : {
                    processed: 0,
                    synced: 0,
                    failed: 0,
                    total: 0
                };

                busy = true;
                updateButton();
                setStatus('info', 'Provjeravam Mailchimp vezu i pokrećem sinkronizaciju...');

                runBatch(lastId, totals).catch(function (error) {
                    if (error.syncData) {
                        totals.processed += Number(error.syncData.processed || 0);
                        totals.synced += Number(error.syncData.synced || 0);
                        totals.failed += Number(error.syncData.failed || 0);
                        totals.total = totals.total || Number(error.syncData.total || 0);
                        if (typeof error.syncData.pending !== 'undefined') {
                            pending = Number(error.syncData.pending);
                        }
                        saveState(Number(error.syncData.last_id || lastId), totals);
                    }

                    setStatus(
                        'danger',
                        'Sinkronizacija je zaustavljena: ' + error.message
                            + ' Ponovni klik nastavlja od zadnje dovršene serije.'
                    );
                }).then(function () {
                    busy = false;
                    updateButton();
                });
            });

            updateButton();
        })();
    </script>
    <script>
        (function () {
            var form = document.getElementById('newsletter-cleanup-form');

            if (! form) {
                return;
            }

            var selectAll = form.querySelector('[data-newsletter-select-all]');
            var checkboxes = Array.prototype.slice.call(
                form.querySelectorAll('[data-newsletter-cleanup-checkbox]')
            );
            var submitButton = form.querySelector('[data-newsletter-cleanup-submit]');
            var submitLabel = form.querySelector('[data-newsletter-cleanup-label]');

            function selectedCheckboxes() {
                return checkboxes.filter(function (checkbox) {
                    return checkbox.checked;
                });
            }

            function updateControls() {
                var selectedCount = selectedCheckboxes().length;

                submitButton.disabled = selectedCount === 0;
                submitLabel.textContent = 'Arhiviraj i ukloni (' + selectedCount + ')';

                if (selectAll) {
                    selectAll.disabled = checkboxes.length === 0;
                    selectAll.checked = checkboxes.length > 0 && selectedCount === checkboxes.length;
                    selectAll.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checkboxes.forEach(function (checkbox) {
                        checkbox.checked = selectAll.checked;
                    });
                    updateControls();
                });
            }

            checkboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', updateControls);
            });

            form.addEventListener('submit', function (event) {
                var selectedCount = selectedCheckboxes().length;

                if (selectedCount === 0) {
                    event.preventDefault();
                    return;
                }

                var confirmed = window.confirm(
                    'Reverzibilno arhivirati ' + selectedCount + ' označenih kontakata u Mailchimpu i trajno ukloniti njihove lokalne zapise? '
                    + 'Prijave povezane s korisnicima ili narudžbama neće biti dirane.'
                );

                if (! confirmed) {
                    event.preventDefault();
                    return;
                }

                submitButton.disabled = true;
                submitLabel.textContent = 'Arhiviram i uklanjam...';
            });

            updateControls();
        })();
    </script>
@endpush
