@extends('back.layouts.backend')

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">Google API</h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">Postavke</li>
                        <li class="breadcrumb-item active" aria-current="page">Google API</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="content content-full">
        @include('back.layouts.partials.session')

        <div class="row">
            <div class="col-xl-8">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">HR -> EN prijevod</h3>
                        <div class="block-options">
                            <button type="button" class="btn btn-sm btn-alt-secondary" id="select-default-fields">
                                Zadano
                            </button>
                            <button type="button" class="btn btn-sm btn-alt-secondary" id="select-all-fields">
                                Sve
                            </button>
                            <button type="button" class="btn btn-sm btn-alt-secondary" id="select-no-fields">
                                Ništa
                            </button>
                        </div>
                    </div>
                    <div class="block-content">
                        <div class="row">
                            @foreach ($targets as $targetKey => $target)
                                <div class="col-md-6 mb-4">
                                    <div class="border rounded p-3 h-100">
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input js-target-toggle" id="target-{{ $targetKey }}" data-target="{{ $targetKey }}">
                                            <label class="custom-control-label font-w600" for="target-{{ $targetKey }}">{{ $target['label'] }}</label>
                                        </div>

                                        @if ($target['description'])
                                            <div class="small text-muted mb-2">{{ $target['description'] }}</div>
                                        @endif

                                        @foreach ($target['fields'] as $field)
                                            <div class="custom-control custom-checkbox mb-1">
                                                <input
                                                    type="checkbox"
                                                    class="custom-control-input js-translate-field"
                                                    id="field-{{ str_replace('.', '-', $field['key']) }}"
                                                    value="{{ $field['key'] }}"
                                                    data-target="{{ $targetKey }}"
                                                    data-default="{{ $field['default'] ? '1' : '0' }}"
                                                    data-available="{{ $field['available'] ? '1' : '0' }}"
                                                    {{ $field['default'] && $field['available'] ? 'checked' : '' }}
                                                    {{ ! $field['available'] ? 'disabled' : '' }}
                                                >
                                                <label class="custom-control-label{{ ! $field['available'] ? ' text-muted' : '' }}" for="field-{{ str_replace('.', '-', $field['key']) }}">
                                                    {{ $field['label'] }}
                                                    @if (! $field['available'])
                                                        <span class="small">(nedostupno)</span>
                                                    @endif
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="block-content bg-body-light">
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="batch-size">Batch veličina</label>
                                    <input type="number" min="1" max="25" step="1" class="form-control" id="batch-size" value="5">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="translation-limit">Limit za probu</label>
                                    <input type="number" min="0" step="1" class="form-control" id="translation-limit" value="0">
                                    <small class="form-text text-muted">0 = bez limita</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="custom-control custom-switch custom-control-warning">
                                        <input type="checkbox" class="custom-control-input" id="overwrite-switch">
                                        <label class="custom-control-label" for="overwrite-switch">Prepiši postojeći EN</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-success" id="start-translation">
                            <i class="fa fa-language mr-1"></i> Pokreni HR -> EN
                        </button>
                        <button type="button" class="btn btn-alt-danger d-none" id="cancel-translation">
                            Prekini
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Status</h3>
                    </div>
                    <div class="block-content">
                        <div class="mb-3">
                            <div class="font-size-sm text-muted">Google Translate</div>
                            @if ($googleTranslate['official_configured'])
                                <span class="badge badge-success">Službeni API ključ je postavljen</span>
                            @elseif ($googleTranslate['public_fallback'])
                                <span class="badge badge-warning">API ključ nije postavljen, fallback je uključen</span>
                            @else
                                <span class="badge badge-danger">Nije konfiguriran</span>
                            @endif
                        </div>

                        <div class="mb-3">
                            <div class="font-size-sm text-muted">Smjer prijevoda</div>
                            <div class="font-w600 text-uppercase">{{ $googleTranslate['source'] }} -> {{ $googleTranslate['target'] }}</div>
                        </div>

                        <div class="progress push" style="height: 1rem;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" id="translation-progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>

                        <dl class="row mb-0">
                            <dt class="col-6">Status</dt>
                            <dd class="col-6 text-right" id="translation-status">Spreman</dd>
                            <dt class="col-6">Obrađeno</dt>
                            <dd class="col-6 text-right" id="translation-processed">0 / 0</dd>
                            <dt class="col-6">Prevedena polja</dt>
                            <dd class="col-6 text-right" id="translation-translated">0</dd>
                            <dt class="col-6">Preskočeno</dt>
                            <dd class="col-6 text-right" id="translation-skipped">0</dd>
                            <dt class="col-6">Greške</dt>
                            <dd class="col-6 text-right" id="translation-errors">0</dd>
                        </dl>
                    </div>
                </div>

                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Sekcije</h3>
                    </div>
                    <div class="block-content p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-vcenter mb-0">
                                <thead>
                                <tr>
                                    <th>Sekcija</th>
                                    <th class="text-right">Progres</th>
                                </tr>
                                </thead>
                                <tbody id="translation-targets">
                                <tr>
                                    <td colspan="2" class="text-muted">Nema aktivnog prijevoda.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Poruke</h3>
                    </div>
                    <div class="block-content">
                        <div class="font-size-sm text-muted" id="translation-messages">Nema poruka.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js_after')
    <script>
        let activeTranslationJob = null;
        let translationRunning = false;

        const processUrlTemplate = "{{ route('google.api.translate.process', ['job' => '__JOB__']) }}";
        const cancelUrlTemplate = "{{ route('google.api.translate.cancel', ['job' => '__JOB__']) }}";

        $(() => {
            refreshTargetToggles();

            $('.js-translate-field').on('change', refreshTargetToggles);

            $('.js-target-toggle').on('change', function () {
                const target = $(this).data('target');
                $('.js-translate-field[data-target="' + target + '"]:not(:disabled)').prop('checked', $(this).is(':checked'));
                refreshTargetToggles();
            });

            $('#select-default-fields').on('click', () => {
                $('.js-translate-field:not(:disabled)').each(function () {
                    $(this).prop('checked', $(this).data('default') === 1 || $(this).data('default') === '1');
                });
                refreshTargetToggles();
            });

            $('#select-all-fields').on('click', () => {
                $('.js-translate-field:not(:disabled)').prop('checked', true);
                refreshTargetToggles();
            });

            $('#select-no-fields').on('click', () => {
                $('.js-translate-field:not(:disabled)').prop('checked', false);
                refreshTargetToggles();
            });

            $('#start-translation').on('click', startTranslation);
            $('#cancel-translation').on('click', cancelTranslation);
        });

        function startTranslation() {
            const targets = $('.js-translate-field:checked').map(function () {
                return $(this).val();
            }).get();

            if (!targets.length) {
                errorToast.fire('Odaberite barem jedno polje za prijevod.');
                return;
            }

            setRunningState(true);
            resetMessages();

            axios.post("{{ route('google.api.translate.start') }}", {
                targets: targets,
                overwrite: $('#overwrite-switch').is(':checked'),
                batch_size: parseInt($('#batch-size').val(), 10) || 5,
                limit: parseInt($('#translation-limit').val(), 10) || 0
            }).then(response => {
                activeTranslationJob = response.data.job;
                renderJob(activeTranslationJob);

                if (activeTranslationJob.status === 'running') {
                    processNextBatch();
                } else {
                    setRunningState(false);
                    successToast.fire();
                }
            }).catch(error => {
                setRunningState(false);
                errorToast.fire(resolveAxiosMessage(error));
            });
        }

        function processNextBatch() {
            if (!translationRunning || !activeTranslationJob) {
                return;
            }

            axios.post(processUrlTemplate.replace('__JOB__', encodeURIComponent(activeTranslationJob.id)))
                .then(response => {
                    activeTranslationJob = response.data.job;
                    renderJob(activeTranslationJob);

                    if (activeTranslationJob.status === 'running') {
                        setTimeout(processNextBatch, 250);
                    } else {
                        setRunningState(false);

                        if (activeTranslationJob.errors > 0) {
                            errorToast.fire('Prijevod je gotov uz greške.');
                        } else {
                            successToast.fire();
                        }
                    }
                }).catch(error => {
                    setRunningState(false);
                    errorToast.fire(resolveAxiosMessage(error));
                });
        }

        function cancelTranslation() {
            if (!activeTranslationJob) {
                return;
            }

            axios.post(cancelUrlTemplate.replace('__JOB__', encodeURIComponent(activeTranslationJob.id)))
                .then(response => {
                    activeTranslationJob = response.data.job;
                    renderJob(activeTranslationJob);
                    setRunningState(false);
                }).catch(error => {
                    errorToast.fire(resolveAxiosMessage(error));
                });
        }

        function renderJob(job) {
            const percent = Number(job.percent || 0).toFixed(1);

            $('#translation-progress-bar')
                .css('width', percent + '%')
                .attr('aria-valuenow', percent)
                .text(percent + '%');

            $('#translation-status').text(statusLabel(job.status));
            $('#translation-processed').text(job.processed + ' / ' + job.total);
            $('#translation-translated').text(job.translated);
            $('#translation-skipped').text(job.skipped);
            $('#translation-errors').text(job.errors);

            renderTargets(job.targets || []);
            renderMessages(job.messages || []);
        }

        function renderTargets(targets) {
            if (!targets.length) {
                $('#translation-targets').html('<tr><td colspan="2" class="text-muted">Nema aktivnog prijevoda.</td></tr>');
                return;
            }

            let html = '';

            targets.forEach(target => {
                html += '<tr>'
                    + '<td>' + escapeHtml(target.label) + '<div class="small text-muted">' + target.processed + ' / ' + target.total + '</div></td>'
                    + '<td class="text-right">' + Number(target.percent || 0).toFixed(1) + '%'
                    + (target.errors > 0 ? '<div class="small text-danger">' + target.errors + ' grešaka</div>' : '')
                    + '</td>'
                    + '</tr>';
            });

            $('#translation-targets').html(html);
        }

        function renderMessages(messages) {
            if (!messages.length) {
                $('#translation-messages').html('Nema poruka.');
                return;
            }

            $('#translation-messages').html(messages.map(message => {
                return '<div class="text-danger mb-2">' + escapeHtml(message) + '</div>';
            }).join(''));
        }

        function refreshTargetToggles() {
            $('.js-target-toggle').each(function () {
                const target = $(this).data('target');
                const fields = $('.js-translate-field[data-target="' + target + '"]:not(:disabled)');
                const checked = fields.filter(':checked');
                $(this).prop('checked', fields.length > 0 && fields.length === checked.length);
                this.indeterminate = checked.length > 0 && fields.length !== checked.length;
            });
        }

        function setRunningState(isRunning) {
            translationRunning = isRunning;
            $('#start-translation').prop('disabled', isRunning);
            $('#cancel-translation').toggleClass('d-none', !isRunning);
            $('.js-translate-field').each(function () {
                $(this).prop('disabled', isRunning || !($(this).data('available') === 1 || $(this).data('available') === '1'));
            });
            $('.js-target-toggle, #overwrite-switch, #batch-size, #translation-limit').prop('disabled', isRunning);
            refreshTargetToggles();
        }

        function resetMessages() {
            $('#translation-messages').html('Nema poruka.');
        }

        function statusLabel(status) {
            const labels = {
                running: 'U tijeku',
                completed: 'Gotovo',
                completed_with_errors: 'Gotovo uz greške',
                cancelled: 'Prekinuto'
            };

            return labels[status] || status;
        }

        function resolveAxiosMessage(error) {
            return error.response && error.response.data && error.response.data.message
                ? error.response.data.message
                : 'Server error! Pokušajte ponovo ili kontaktirajte administratora!';
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    </script>
@endpush
