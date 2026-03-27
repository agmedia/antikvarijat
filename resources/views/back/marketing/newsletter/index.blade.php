@extends('back.layouts.backend')

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">Newsletter prijave</h1>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Pretplatnici</h3>
                <div class="block-options">
                    <form method="post" action="{{ route('newsletter.subscribers.sync') }}" class="d-inline-block mr-2">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            Import novih u Mailchimp ({{ $pendingSyncCount ?? 0 }})
                        </button>
                    </form>
                    <form method="post"
                          action="{{ route('newsletter.products.sync') }}"
                          class="d-inline-block mr-2 js-mailchimp-batch-form"
                          data-sync-type="artikala"
                          data-batch="25">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            Sync artikala u Mailchimp
                        </button>
                    </form>
                    <form method="post"
                          action="{{ route('newsletter.orders.sync') }}"
                          class="d-inline-block mr-2 js-mailchimp-batch-form"
                          data-sync-type="ordera"
                          data-batch="10">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            Sync ordera u Mailchimp
                        </button>
                    </form>
                    <form method="post" action="{{ route('newsletter.caches.clear') }}" class="d-inline-block mr-2">
                        @csrf
                        <button type="submit" class="btn btn-warning">
                            Očisti app cache
                        </button>
                    </form>
                    <a class="btn btn-primary" href="{{ route('newsletter.subscribers') }}">
                        Očisti filter
                    </a>
                </div>
            </div>

            <div class="block-content">
                @if (session('status'))
                    <div class="alert alert-info">
                        <pre class="mb-0" style="white-space: pre-wrap;">{{ session('status') }}</pre>
                    </div>
                @endif

                <div id="mailchimp-batch-status" class="alert alert-secondary d-none">
                    <pre class="mb-0" style="white-space: pre-wrap;"></pre>
                </div>

                <div class="alert alert-warning">
                    Za veće kataloge sync artikala i ordera ide u batch režimu iz ovog ekrana, tako da ne padne timeout na hostingu.
                </div>

                <div class="block block-rounded block-bordered">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Sync odabranih artikala</h3>
                    </div>
                    <div class="block-content">
                        <form method="post" action="{{ route('newsletter.products.selected.sync') }}">
                            @csrf
                            <div class="form-group">
                                <label for="product_refs">ID ili SKU artikala</label>
                                <textarea id="product_refs"
                                          name="product_refs"
                                          rows="4"
                                          class="form-control @error('product_refs') is-invalid @enderror"
                                          placeholder="Primjer: 8129, 8130&#10;01013812&#10;M7100">{{ old('product_refs') }}</textarea>
                                @error('product_refs')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Zalijepi ID-eve ili SKU-ove, odvojene zarezom, razmakom ili novim redom.
                                </small>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-info">
                                    Sync samo odabrane artikle
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="bg-body-dark p-3 mb-3">
                    <form method="get" action="{{ route('newsletter.subscribers') }}">
                        <div class="form-group row">
                            <div class="col-md-9">
                                <div class="input-group">
                                    <input type="text"
                                           class="form-control"
                                           name="search"
                                           value="{{ request()->input('search') }}"
                                           placeholder="Pretraži email, korisnika ili order ID">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-borderless table-striped table-vcenter">
                        <thead>
                        <tr>
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
                            @endphp
                            <tr>
                                <td>{{ $subscriber->id }}</td>
                                <td>{{ $subscriber->email }}</td>
                                <td>{{ $subscriber->user_id ?: '-' }}</td>
                                <td>
                                    @if ($subscriber->user_id)
                                        {{ $fullName !== '' ? $fullName : (optional($subscriber->user)->name ?? '-') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $subscriber->order_id ?: '-' }}</td>
                                <td>{{ $subscriber->source }}</td>
                                <td>{{ $subscriber->gdpr ? 'DA' : 'NE' }}</td>
                                <td>
                                    @if ($subscriber->mailchimp_synced_at)
                                        <span class="badge badge-success">Syncano {{ $subscriber->mailchimp_synced_at->format('d.m.Y H:i') }}</span>
                                    @elseif ($subscriber->mailchimp_last_error)
                                        <span class="badge badge-danger" title="{{ $subscriber->mailchimp_last_error }}">Greška</span>
                                    @else
                                        <span class="badge badge-secondary">Novo</span>
                                    @endif
                                </td>
                                <td>{{ optional($subscriber->subscribed_at)->format('d.m.Y H:i') ?: optional($subscriber->created_at)->format('d.m.Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">Nema newsletter prijava.</td>
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

@push('js_after')
    <script>
        (function () {
            var forms = document.querySelectorAll('.js-mailchimp-batch-form');
            var statusBox = document.getElementById('mailchimp-batch-status');
            var statusPre = statusBox ? statusBox.querySelector('pre') : null;

            if (!forms.length || !statusBox || !statusPre) {
                return;
            }

            var active = false;

            function setBusyState(isBusy) {
                active = isBusy;

                forms.forEach(function (form) {
                    var button = form.querySelector('button[type="submit"]');

                    if (!button) {
                        return;
                    }

                    button.disabled = isBusy;
                    if (isBusy) {
                        button.dataset.originalText = button.dataset.originalText || button.textContent;
                        button.textContent = 'Sync u tijeku...';
                    } else if (button.dataset.originalText) {
                        button.textContent = button.dataset.originalText;
                    }
                });
            }

            function showStatus(message) {
                statusBox.classList.remove('d-none');
                statusPre.textContent = message;
            }

            function runBatch(form, lastId, totals) {
                var formData = new FormData(form);
                var batch = parseInt(form.dataset.batch || '100', 10);
                var syncType = form.dataset.syncType || 'zapisa';

                formData.append('last_id', String(lastId || 0));
                formData.append('batch', String(batch));

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }

                    return response.json();
                })
                .then(function (data) {
                    totals.processed += Number(data.processed || 0);
                    totals.synced += Number(data.synced || 0);
                    totals.failed += Number(data.failed || 0);
                    totals.total = Number(data.total || totals.total || 0);

                    var headline = 'Mailchimp sync ' + syncType + '\n'
                        + 'Ukupno obradjeno u ovoj sesiji: ' + totals.processed + '\n'
                        + 'Uspjesno: ' + totals.synced + '\n'
                        + 'Greske: ' + totals.failed;

                    if (totals.total > 0) {
                        headline += '\nUkupno za sync: ' + totals.total;
                    }

                    showStatus(headline + '\n\n' + (data.message || ''));

                    if (data.finished) {
                        setBusyState(false);
                        return;
                    }

                    runBatch(form, Number(data.last_id || 0), totals);
                })
                .catch(function (error) {
                    showStatus('Sync je prekinut. ' + error.message);
                    setBusyState(false);
                });
            }

            forms.forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (active) {
                        event.preventDefault();
                        return;
                    }

                    event.preventDefault();

                    setBusyState(true);
                    showStatus('Pokrecem batch sync...');

                    runBatch(form, 0, {
                        processed: 0,
                        synced: 0,
                        failed: 0,
                        total: 0,
                    });
                });
            });
        })();
    </script>
@endpush
