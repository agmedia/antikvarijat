@extends('front.layouts.app')

@section('title', 'Otkup knjiga')
@section('description', 'Pošaljite prijavu za otkup knjiga i časopisa.')

@section('content')
    <div class="bg-secondary py-4" style="background-image: url({{ asset('media/img/farmer.png') }}); background-repeat: repeat">
        <div class="container d-lg-flex justify-content-between py-2 py-lg-3">
            <div class="order-lg-2 mb-3 mb-lg-0 pt-lg-2">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb flex-lg-nowrap justify-content-center justify-content-lg-start">
                        <li class="breadcrumb-item"><a class="text-nowrap" href="/"><i class="ci-home"></i> Naslovnica</a></li>
                        <li class="breadcrumb-item text-nowrap active" aria-current="page">Otkup knjiga</li>
                    </ol>
                </nav>
            </div>
            <div class="order-lg-1 pe-lg-4 text-center text-lg-start">
                <h1 class="h3 mb-0">Otkup knjiga</h1>
            </div>
        </div>
    </div>

    <section class="container py-5">
        @include('front.layouts.partials.success-session')

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div id="otkup-success" class="alert alert-success d-none" role="alert"></div>
        <div id="otkup-error" class="alert alert-danger d-none" role="alert"></div>

        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h5">Kako pripremiti fotografije</h2>
                        <p class="mb-0 text-muted">
                            Pošaljite nekoliko jasnih fotografija korica i unutrašnjosti knjiga/časopisa. Fotografije trebaju biti snimljene pri dobrom svjetlu,
                            bez zamućenja i s vidljivim oštećenjima ako postoje.
                        </p>
                    </div>
                </div>
            </div>

        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-lg-5">
                <h2 class="h4 mb-3">Pošaljite prijavu za otkup</h2>

                <form id="book-purchase-form" action="{{ route('otkup.knjiga.posalji') }}" method="POST" enctype="multipart/form-data" novalidate>
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="full-name">Ime i prezime @include('back.layouts.partials.required-star')</label>
                            <input class="form-control" type="text" id="full-name" name="full_name" value="{{ old('full_name', $defaults['full_name'] ?? '') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="postal-code">Poštanski broj @include('back.layouts.partials.required-star')</label>
                            <input class="form-control" type="text" id="postal-code" name="postal_code" value="{{ old('postal_code', $defaults['postal_code'] ?? '') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Email @include('back.layouts.partials.required-star')</label>
                            <input class="form-control" type="email" id="email" name="email" value="{{ old('email', $defaults['email'] ?? '') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="phone">Kontakt broj (mobitel) @include('back.layouts.partials.required-star')</label>
                            <input class="form-control" type="text" id="phone" name="phone" value="{{ old('phone', $defaults['phone'] ?? '') }}" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="photos">Fotografije @include('back.layouts.partials.required-star')</label>
                            <div class="d-flex flex-wrap align-items-center">
                                <input class="d-none" type="file" id="photos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple>
                                <button type="button" id="photos-trigger" class="btn btn-outline-primary me-3 mb-2 mb-sm-0">Odaberi fotografije</button>
                                <span id="photos-selected-text" class="text-muted">Nijedna datoteka nije odabrana.</span>
                            </div>
                            <div class="form-text mt-2">Maksimalno 20 fotografija, do 4 MB po fotografiji, ukupno do 40 MB.</div>
                        </div>

                        <div class="col-12">
                            <ul id="file-list" class="list-group"></ul>
                            <div id="file-summary" class="form-text mt-2"></div>
                        </div>

                        <div class="col-12 d-none" id="upload-progress-wrap">
                            <div class="progress" style="height: 12px;">
                                <div id="upload-progress" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">0%</div>
                            </div>
                        </div>

                        <div class="col-12">
                            <button id="submit-btn" class="btn btn-primary" type="submit">Pošalji</button>
                            <div id="otkup-success-bottom" class="alert alert-success d-none mt-3 mb-0" role="alert"></div>
                        </div>
                    </div>

                    <input type="hidden" name="recaptcha" id="recaptcha">
                </form>
            </div>
        </div>
    </section>
@endsection

@push('js_after')
    @include('front.layouts.partials.recaptcha-js')

    <script>
        (() => {
            const MAX_FILES = 20;
            const MAX_FILE_SIZE = 4 * 1024 * 1024;
            const MAX_TOTAL_SIZE = 40 * 1024 * 1024;

            const form = document.getElementById('book-purchase-form');
            const fileInput = document.getElementById('photos');
            const fileTrigger = document.getElementById('photos-trigger');
            const fileSelectedText = document.getElementById('photos-selected-text');
            const fileList = document.getElementById('file-list');
            const progressWrap = document.getElementById('upload-progress-wrap');
            const progressBar = document.getElementById('upload-progress');
            const submitBtn = document.getElementById('submit-btn');
            const successBox = document.getElementById('otkup-success');
            const successBoxBottom = document.getElementById('otkup-success-bottom');
            const errorBox = document.getElementById('otkup-error');
            const fileSummary = document.getElementById('file-summary');

            let selectedFiles = [];
            let previewUrls = [];

            function bytesToMb(bytes) {
                return (bytes / (1024 * 1024)).toFixed(2);
            }

            function showMessage(element, text) {
                element.textContent = text;
                element.classList.remove('d-none');
            }

            function clearMessages() {
                successBox.classList.add('d-none');
                successBoxBottom.classList.add('d-none');
                errorBox.classList.add('d-none');
                successBox.textContent = '';
                successBoxBottom.textContent = '';
                errorBox.textContent = '';
            }

            function setFileInputFromState() {
                const dt = new DataTransfer();
                selectedFiles.forEach(file => dt.items.add(file));
                fileInput.files = dt.files;
            }

            function renderFileList() {
                previewUrls.forEach(url => URL.revokeObjectURL(url));
                previewUrls = [];
                fileList.innerHTML = '';

                if (selectedFiles.length === 0) {
                    fileSelectedText.textContent = 'Nijedna datoteka nije odabrana.';
                    fileSummary.textContent = '';
                    return;
                }

                const totalBytes = selectedFiles.reduce((sum, file) => sum + file.size, 0);
                fileSummary.textContent = `${selectedFiles.length} datoteka(e), ukupno ${bytesToMb(totalBytes)} MB`;
                fileSelectedText.textContent = selectedFiles.length === 1
                    ? selectedFiles[0].name
                    : `Odabrano datoteka: ${selectedFiles.length}`;

                selectedFiles.forEach((file, index) => {
                    const previewUrl = URL.createObjectURL(file);
                    previewUrls.push(previewUrl);
                    const li = document.createElement('li');
                    li.className = 'list-group-item d-flex justify-content-between align-items-center flex-wrap';
                    li.innerHTML = `
                        <div class="d-flex align-items-center me-3 mb-2 mb-md-0">
                            <img src="${previewUrl}" alt="${file.name}" style="width:64px;height:64px;object-fit:cover;border-radius:6px;border:1px solid #e2e2e2;" class="me-3">
                            <span class="text-break">${file.name} <small class="text-muted">(${bytesToMb(file.size)} MB)</small></span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-index="${index}">Obriši</button>
                    `;
                    fileList.appendChild(li);
                });
            }

            fileTrigger.addEventListener('click', () => {
                fileInput.click();
            });

            function validateFiles(files) {
                if (files.length === 0) {
                    return 'Odaberite barem jednu fotografiju.';
                }

                if (files.length > MAX_FILES) {
                    return 'Maksimalno je dozvoljeno 20 fotografija.';
                }

                let totalSize = 0;
                for (const file of files) {
                    totalSize += file.size;
                    if (file.size > MAX_FILE_SIZE) {
                        return `Datoteka ${file.name} je veća od 4 MB.`;
                    }
                }

                if (totalSize > MAX_TOTAL_SIZE) {
                    return 'Ukupna veličina svih fotografija može biti najviše 40 MB.';
                }

                return null;
            }

            fileInput.addEventListener('change', () => {
                clearMessages();

                selectedFiles = Array.from(fileInput.files || []);
                const validationError = validateFiles(selectedFiles);

                if (validationError) {
                    showMessage(errorBox, validationError);
                    selectedFiles = [];
                    setFileInputFromState();
                    renderFileList();
                    return;
                }

                renderFileList();
            });

            fileList.addEventListener('click', (event) => {
                const btn = event.target.closest('[data-remove-index]');
                if (!btn) {
                    return;
                }

                const index = Number(btn.getAttribute('data-remove-index'));
                selectedFiles = selectedFiles.filter((_, i) => i !== index);
                setFileInputFromState();
                renderFileList();
            });

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                clearMessages();

                selectedFiles = Array.from(fileInput.files || []);
                const validationError = validateFiles(selectedFiles);
                if (validationError) {
                    showMessage(errorBox, validationError);
                    return;
                }

                const formData = new FormData(form);

                progressWrap.classList.remove('d-none');
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';
                submitBtn.disabled = true;

                const xhr = new XMLHttpRequest();
                xhr.open('POST', form.action, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Accept', 'application/json');

                xhr.upload.addEventListener('progress', (e) => {
                    if (!e.lengthComputable) {
                        return;
                    }

                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = `${percent}%`;
                    progressBar.textContent = `${percent}%`;
                });

                xhr.onreadystatechange = () => {
                    if (xhr.readyState !== XMLHttpRequest.DONE) {
                        return;
                    }

                    submitBtn.disabled = false;

                    if (xhr.status >= 200 && xhr.status < 300) {
                        let message = 'Hvala! Vaša prijava je uspješno poslana.';
                        try {
                            const json = JSON.parse(xhr.responseText);
                            if (json.message) {
                                message = json.message;
                            }
                        } catch (e) {
                        }

                        form.reset();
                        selectedFiles = [];
                        renderFileList();
                        progressBar.style.width = '100%';
                        progressBar.textContent = '100%';
                        showMessage(successBox, message);
                        showMessage(successBoxBottom, message);
                        successBoxBottom.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return;
                    }

                    let message = 'Došlo je do greške prilikom slanja prijave. Pokušajte ponovno.';
                    try {
                        const json = JSON.parse(xhr.responseText);
                        if (json.errors) {
                            const firstField = Object.keys(json.errors)[0];
                            if (firstField && json.errors[firstField] && json.errors[firstField][0]) {
                                message = json.errors[firstField][0];
                            }
                        } else if (json.message) {
                            message = json.message;
                        }
                    } catch (e) {
                    }

                    showMessage(errorBox, message);
                };

                xhr.send(formData);
            });
        })();
    </script>
@endpush
