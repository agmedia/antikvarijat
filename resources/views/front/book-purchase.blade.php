@extends('front.layouts.app')

@php
    $bookPurchaseMetaTitle = \Illuminate\Support\Str::contains(
        \Illuminate\Support\Str::lower($bookPurchaseContent['title']),
        'antikvarijat biblos'
    ) ? $bookPurchaseContent['title'] : $bookPurchaseContent['title'] . ' - Antikvarijat Biblos';
    $bookPurchaseSchema = \App\Helpers\LandingPageStructuredData::bookPurchaseService(
        \App\Helpers\LocaleHelper::route('otkup.knjiga'),
        $bookPurchaseContent['title'],
        $bookPurchaseContent['meta_description'],
        __('front.book_purchase.service_type'),
        app()->getLocale()
    );
@endphp

@section('title', $bookPurchaseMetaTitle)
@section('description', $bookPurchaseContent['meta_description'])

@push('meta_tags')
    <script type="application/ld+json">{!! \App\Helpers\StructuredData::toJson($bookPurchaseSchema) !!}</script>
@endpush

@push('css_after')
    <link rel="stylesheet" media="screen" href="{{ \App\Helpers\Asset::url('js/simple-lightbox.css') }}">
@endpush

@section('content')
    <main id="main-content">
    <div class="bg-secondary py-4" style="background-image: url({{ asset('media/img/farmer.png') }}); background-repeat: repeat">
        <div class="container d-lg-flex justify-content-between py-2 py-lg-3">
            <div class="order-lg-2 mb-3 mb-lg-0 pt-lg-2">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb flex-lg-nowrap justify-content-center justify-content-lg-start">
                        <li class="breadcrumb-item"><a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}"><i class="fa-solid fa-house"></i> {{ __('front.nav.home') }}</a></li>
                        <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ $bookPurchaseContent['title'] }}</li>
                    </ol>
                </nav>
            </div>
            <div class="order-lg-1 pe-lg-4 text-center text-lg-start">
                <h1 class="h3 mb-0">{{ $bookPurchaseContent['title'] }}</h1>
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
                        <h2 class="h5">{{ $bookPurchaseContent['section_title'] }}</h2>
                        <p class="mb-0 text-muted">
                            {{ $bookPurchaseContent['intro_1'] }}</p>
                        <p class="mb-0 text-muted">
                            {{ $bookPurchaseContent['intro_2'] }}
                        </p>
                    </div>
                </div>
            </div>

        </div>

        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3 book-photos-lightbox">
                            <div class="col-6 col-md-3">
                                <a class="d-block ratio ratio-1x1" href="{{ asset('media/img/widget/11/otkup-knjiga-primjer-1.jpg') }}">
                                    <img class="w-100 h-100 rounded-3 object-fit-cover"
                                         src="{{ asset('media/img/widget/11/otkup-knjiga-primjer-1.jpg') }}"
                                         alt="{{ __('front.book_purchase.photo_alt', ['number' => 1]) }}"
                                         width="1024"
                                         height="697"
                                         loading="eager"
                                         fetchpriority="high"
                                         decoding="async">
                                </a>
                            </div>
                            <div class="col-6 col-md-3">
                                <a class="d-block ratio ratio-1x1" href="{{ asset('media/img/widget/11/otkup-knjiga-primjer-2.jpg') }}">
                                    <img class="w-100 h-100 rounded-3 object-fit-cover"
                                         src="{{ asset('media/img/widget/11/otkup-knjiga-primjer-2.jpg') }}"
                                         alt="{{ __('front.book_purchase.photo_alt', ['number' => 2]) }}"
                                         width="1024"
                                         height="1365"
                                         loading="eager"
                                         decoding="async">
                                </a>
                            </div>
                            <div class="col-6 col-md-3">
                                <a class="d-block ratio ratio-1x1" href="{{ asset('media/img/widget/11/otkup-knjiga-primjer-3.jpg') }}">
                                    <img class="w-100 h-100 rounded-3 object-fit-cover"
                                         src="{{ asset('media/img/widget/11/otkup-knjiga-primjer-3.jpg') }}"
                                         alt="{{ __('front.book_purchase.photo_alt', ['number' => 3]) }}"
                                         width="1024"
                                         height="768"
                                         loading="lazy"
                                         decoding="async">
                                </a>
                            </div>
                            <div class="col-6 col-md-3">
                                <a class="d-block ratio ratio-1x1" href="{{ asset('media/img/widget/11/otkup-knjiga-primjer-4.jpg') }}">
                                    <img class="w-100 h-100 rounded-3 object-fit-cover"
                                         src="{{ asset('media/img/widget/11/otkup-knjiga-primjer-4.jpg') }}"
                                         alt="{{ __('front.book_purchase.photo_alt', ['number' => 4]) }}"
                                         width="1024"
                                         height="768"
                                         loading="lazy"
                                         decoding="async">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-lg-5">
                <h2 class="h4 mb-3">{{ $bookPurchaseContent['form_title'] }}</h2>

                <form id="book-purchase-form" action="{{ \App\Helpers\LocaleHelper::route('otkup.knjiga.posalji') }}" method="POST" enctype="multipart/form-data" novalidate>
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="full-name">{{ __('front.book_purchase.full_name') }} @include('back.layouts.partials.required-star')</label>
                            <input class="form-control" type="text" id="full-name" name="full_name" value="{{ old('full_name', $defaults['full_name'] ?? '') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="postal-code">{{ __('front.book_purchase.postal_code') }} @include('back.layouts.partials.required-star')</label>
                            <input class="form-control" type="text" id="postal-code" name="postal_code" value="{{ old('postal_code', $defaults['postal_code'] ?? '') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Email @include('back.layouts.partials.required-star')</label>
                            <input class="form-control" type="email" id="email" name="email" value="{{ old('email', $defaults['email'] ?? '') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="phone">{{ __('front.book_purchase.phone') }} @include('back.layouts.partials.required-star')</label>
                            <input class="form-control" type="text" id="phone" name="phone" value="{{ old('phone', $defaults['phone'] ?? '') }}" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="photos">{{ __('front.book_purchase.photos') }} @include('back.layouts.partials.required-star')</label>
                            <div class="d-flex flex-wrap align-items-center">
                                <input class="d-none" type="file" id="photos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple>
                                <button type="button" id="photos-trigger" class="btn btn-outline-primary me-3 mb-2 mb-sm-0">{{ __('front.book_purchase.choose_photos') }}</button>
                                <span id="photos-selected-text" class="text-muted">{{ __('front.book_purchase.no_file_selected') }}</span>
                            </div>
                            <div class="form-text mt-2">{{ __('front.book_purchase.photos_help') }}</div>
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
                            <button id="submit-btn" class="btn btn-primary" type="submit">{{ __('front.book_purchase.submit') }}</button>
                            <div id="otkup-success-bottom" class="alert alert-success d-none mt-3 mb-0" role="alert"></div>
                        </div>
                    </div>

                    <input type="hidden" name="recaptcha" id="recaptcha">
                </form>
            </div>
        </div>

    </section>
    </main>
@endsection

@push('js_after')
    @include('front.layouts.partials.recaptcha-js')
    <script src="{{ \App\Helpers\Asset::url('js/simple-lightbox.js') }}"></script>
    @php
        $bookPurchaseLabels = [
            'noFileSelected' => __('front.book_purchase.no_file_selected'),
            'filesSummary' => __('front.book_purchase.files_summary'),
            'selectedFiles' => __('front.book_purchase.selected_files'),
            'remove' => __('front.book_purchase.remove'),
            'selectAtLeastOne' => __('front.book_purchase.select_at_least_one'),
            'maxFiles' => __('front.book_purchase.max_files'),
            'fileTooLarge' => __('front.book_purchase.file_too_large'),
            'totalTooLarge' => __('front.book_purchase.total_too_large'),
            'sentShort' => __('front.book_purchase.sent_short'),
            'sendError' => __('front.book_purchase.send_error'),
            'loading' => __('front.general.loading'),
        ];
    @endphp

    <script>
        (() => {
            const MAX_FILES = 20;
            const MAX_FILE_SIZE = 4 * 1024 * 1024;
            const MAX_TOTAL_SIZE = 40 * 1024 * 1024;
            const labels = {!! json_encode($bookPurchaseLabels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};

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
                    fileSelectedText.textContent = labels.noFileSelected;
                    fileSummary.textContent = '';
                    return;
                }

                const totalBytes = selectedFiles.reduce((sum, file) => sum + file.size, 0);
                fileSummary.textContent = labels.filesSummary
                    .replace(':count', selectedFiles.length)
                    .replace(':size', bytesToMb(totalBytes));
                fileSelectedText.textContent = selectedFiles.length === 1
                    ? selectedFiles[0].name
                    : labels.selectedFiles.replace(':count', selectedFiles.length);

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
                        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-index="${index}">${labels.remove}</button>
                    `;
                    fileList.appendChild(li);
                });
            }

            fileTrigger.addEventListener('click', () => {
                fileInput.click();
            });

            function validateFiles(files) {
                if (files.length === 0) {
                    return labels.selectAtLeastOne;
                }

                if (files.length > MAX_FILES) {
                    return labels.maxFiles;
                }

                let totalSize = 0;
                for (const file of files) {
                    totalSize += file.size;
                    if (file.size > MAX_FILE_SIZE) {
                        return labels.fileTooLarge.replace(':name', file.name);
                    }
                }

                if (totalSize > MAX_TOTAL_SIZE) {
                    return labels.totalTooLarge;
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

            if (window.SimpleLightbox) {
                new SimpleLightbox('.book-photos-lightbox a', {
                    captionsData: 'alt',
                    captionDelay: 150,
                });
            }

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
                        let message = labels.sentShort;
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

                        let message = labels.sendError;
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
