@extends('back.layouts.backend')

@section('content')
    <div class="admin-page-hero">
        <div class="content content-full">
            <div class="admin-page-heading">
                <div>
                    <div class="admin-page-kicker"><i class="fa-duotone fa-plug"></i> Integracije</div>
                    <h1 class="admin-page-title">API i uvoz podataka</h1>
                    <p class="admin-page-description">Uvezite artikle iz pripremljene Excel datoteke i odmah provjerite rezultat obrade.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        @include('back.layouts.partials.session')

        <div class="row">
            <div class="col-xl-8">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <div class="d-flex align-items-center min-width-0">
                            <span class="admin-section-icon mr-3"><i class="fa-duotone fa-file-excel"></i></span>
                            <div>
                                <h2 class="block-title mb-1">Uvoz artikala</h2>
                                <span class="admin-count">Excel · .xlsx ili .xls</span>
                            </div>
                        </div>
                    </div>
                    <div class="block-content">
                        <div class="admin-import-zone">
                            <span class="admin-import-icon"><i class="fa-duotone fa-cloud-arrow-up"></i></span>
                            <div>
                                <h3>Odaberite Excel datoteku</h3>
                                <p>Datoteka će se poslati na obradu, a novi artikli bit će uvezeni prema postojećim pravilima kataloga.</p>
                            </div>
                            <input class="sr-only" type="file" id="excel-file" name="file" accept=".xlsx,.xls" onchange="uploadFile(event)">
                            <label class="btn btn-primary mb-0" for="excel-file"><i class="fa-duotone fa-folder-open mr-1"></i> Odaberi i uvezi</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="block block-rounded" id="my-block">
                    <div class="block-header block-header-default">
                        <div class="d-flex align-items-center min-width-0">
                            <span class="admin-section-icon mr-3"><i class="fa-duotone fa-terminal"></i></span>
                            <h2 class="block-title">Rezultat obrade</h2>
                        </div>
                    </div>
                    <div class="block-content">
                        <p class="admin-import-result" id="api-result">Nakon uvoza ovdje će se prikazati rezultat ili opis greške.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css_after')
    <style>
        .admin-import-zone { display: flex; min-height: 13rem; align-items: center; gap: 1rem; padding: 1.25rem; border: 1px dashed #aeb9b2; border-radius: .35rem; background: #fafaf8; }
        .admin-import-icon { display: inline-flex; width: 3.25rem; height: 3.25rem; flex: 0 0 auto; align-items: center; justify-content: center; border-radius: .35rem; color: #2f5d49; background: #e9f0eb; font-size: 1.3rem; }
        .admin-import-zone > div { min-width: 0; flex: 1; }
        .admin-import-zone h3 { margin: 0 0 .35rem; color: var(--admin-ink); font-size: 1rem; }
        .admin-import-zone p, .admin-import-result { margin: 0; color: #66736b; line-height: 1.55; }
        .admin-import-result { min-height: 8rem; overflow-wrap: anywhere; }
        @media (max-width: 575.98px) {
            .admin-import-zone { align-items: flex-start; flex-direction: column; }
            .admin-import-zone .btn { width: 100%; }
        }
    </style>
@endpush

@push('js_after')
    <script>
        function uploadFile(event) {
            const block = $('#my-block');
            const file = event.target.files[0];

            if (!file) {
                return errorToast.fire('Molimo odaberite Excel datoteku.');
            }

            const formData = new FormData();
            formData.append('file', file);
            formData.append('target', 'plava-krava');
            formData.append('method', 'upload-excel');

            block.addClass('block-mode-loading');
            axios.post('{{ route('api.api.upload') }}', formData)
                .then(response => {
                    showToast(response.data);
                    $('#api-result').html(response.data.success || response.data.error || response.data.message);
                })
                .catch(() => errorToast.fire('Uvoz nije uspio. Provjerite datoteku i pokušajte ponovno.'))
                .finally(() => {
                    block.removeClass('block-mode-loading');
                    event.target.value = '';
                });
        }

        function showToast(result) {
            if (result.success) {
                successToast.fire();
            } else {
                errorToast.fire(result.message || result.error);
            }
        }
    </script>
@endpush
