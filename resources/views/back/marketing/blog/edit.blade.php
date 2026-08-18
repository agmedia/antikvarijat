@extends('back.layouts.backend')

@push('css_before')
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('js/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('js/plugins/flatpickr/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('js/plugins/summernote/summernote-bs4.min.css') }}">
@endpush

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">Blog edit</h1>
            </div>
        </div>
    </div>

    <div class="content content-full content-boxed">
        @include('back.layouts.partials.session')

        @php
            $recommendationType = old('recommendation_type', isset($blog) ? ($blog->recommendation_type ?: 'none') : 'none');
        @endphp

        <form action="{{ isset($blog) ? route('blogs.update', ['blog' => $blog]) : route('blogs.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if (isset($blog))
                {{ method_field('PATCH') }}
            @endif

            <div class="block">
                <div class="block-header block-header-default">
                    <a class="btn btn-light" href="{{ route('blogs') }}">
                        <i class="fa fa-arrow-left mr-1"></i> Povratak
                    </a>
                    <div class="block-options d-flex flex-wrap align-items-center justify-content-end">
                        <div class="custom-control custom-switch custom-control-warning mr-4">
                            <input type="checkbox" class="custom-control-input" id="hide-from-home-widget" name="hide_from_home_widget" {{ (isset($blog) and $blog->hide_from_home_widget) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="hide-from-home-widget" title="Objava ostaje aktivna na Blog stranici.">Sakrij iz widgeta na naslovnici</label>
                        </div>
                        <div class="custom-control custom-switch custom-control-success">
                            <input type="checkbox" class="custom-control-input" id="dm-post-edit-active" name="status" {{ (isset($blog) and $blog->status) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="dm-post-edit-active">Aktiviraj</label>
                        </div>
                    </div>
                </div>
                <div class="block-content">
                    <div class="row push">
                        <div class="col-12">

                            @include('back.layouts.partials.language-tabs', ['id' => 'blog-content-tabs'])
                            <div class="tab-content">
                                <div class="tab-pane active" id="blog-content-tabs-hr" role="tabpanel">
                                    <div class="form-group">
                                        <label for="title-input">Naslov</label>
                                        <input type="text" class="form-control" id="title-input" name="title" placeholder="Upišite naslov..." value="{{ isset($blog) ? $blog->title : old('title') }}" onkeyup="SetSEOPreview()">
                                    </div>

                                    <div class="form-group">
                                        <label for="short-description-input">Sažetak</label>
                                        <textarea class="form-control" id="short-description-input" name="short_description" rows="3" placeholder="Enter an excerpt..">{{ isset($blog) ? $blog->short_description : old('title') }}</textarea>
                                        <div class="form-text text-muted font-size-sm font-italic">Prikazuje se u kartici widgeta ako objava nije skrivena s naslovnice.</div>
                                    </div>
                                    <div class="form-group row mb-4">
                                        <div class="col-md-12">
                                            <label for="description-editor">Opis</label>
                                            <textarea id="description-editor" name="description">{!! isset($blog) ? $blog->description : old('description') !!}</textarea>
                                            <small class="form-text text-muted">Za uređivanje HTML-a, klasa i stilova odaberite “Izvorni kôd” u alatnoj traci.</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="blog-content-tabs-en" role="tabpanel">
                                    <div class="form-group">
                                        <label for="title-en-input">Naslov EN</label>
                                        <input type="text" class="form-control" id="title-en-input" name="title_en" placeholder="Upišite engleski naslov..." value="{{ old('title_en', isset($blog) ? $blog->title_en : '') }}">
                                    </div>

                                    <div class="form-group">
                                        <label for="short-description-en-input">Sažetak EN</label>
                                        <textarea class="form-control" id="short-description-en-input" name="short_description_en" rows="3" placeholder="Enter an excerpt..">{{ old('short_description_en', isset($blog) ? $blog->short_description_en : '') }}</textarea>
                                    </div>
                                    <div class="form-group row mb-4">
                                        <div class="col-md-12">
                                            <label for="description-en-editor">Opis EN</label>
                                            <textarea id="description-en-editor" name="description_en">{!! old('description_en', isset($blog) ? $blog->description_en : '') !!}</textarea>
                                            <small class="form-text text-muted">Za uređivanje HTML-a, klasa i stilova odaberite “Izvorni kôd” u alatnoj traci.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-xl-6">
                                    <label>Glavna slika</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="image-input" name="image" data-toggle="custom-file-input" onchange="readURL(this);">
                                        <label class="custom-file-label" for="image-input">Odaberite sliku</label>
                                    </div>
                                    <div class="mt-2">
                                        <img class="img-fluid" id="image-view" src="{{ \App\Support\AdminImage::url(isset($blog) ? $blog->image : null, 'media/img/lightslider.webp') }}" alt="">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="col-xl-6">
                                    <label for="publish-date-input">Datum objave</label>
                                    <input type="text" class="js-flatpickr form-control bg-white" id="publish-date-input"
                                           value="{{ isset($blog) && $blog->publish_date ? \Illuminate\Support\Carbon::make($blog->publish_date)->format('d.m.Y') : '' }}"
                                           name="publish_date" data-enable-time="true" placeholder="Ili ostavi prazno za odmah...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Carousel knjiga uz članak</h3>
                </div>
                <div class="block-content">
                    <p class="text-muted mb-3">
                        Odaberite autora za automatski prikaz njegovih dostupnih knjiga ili ručno dodajte knjige u carousel.
                    </p>

                    <div class="form-group">
                        <label class="d-block">Sadržaj carousela</label>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" class="custom-control-input" id="recommendation-none" name="recommendation_type" value="none" {{ $recommendationType === 'none' ? 'checked' : '' }}>
                            <label class="custom-control-label" for="recommendation-none">Bez carousela</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" class="custom-control-input" id="recommendation-author" name="recommendation_type" value="author" {{ $recommendationType === 'author' ? 'checked' : '' }}>
                            <label class="custom-control-label" for="recommendation-author">Knjige autora</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" class="custom-control-input" id="recommendation-products" name="recommendation_type" value="products" {{ $recommendationType === 'products' ? 'checked' : '' }}>
                            <label class="custom-control-label" for="recommendation-products">Ručno odabrane knjige</label>
                        </div>
                        @error('recommendation_type')
                            <div class="text-danger font-size-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="recommendation-author-panel" class="form-group" style="{{ $recommendationType !== 'author' ? 'display: none;' : '' }}">
                        <label for="recommendation-author-select">Autor</label>
                        <select class="form-control" id="recommendation-author-select" name="recommendation_author_id" style="width: 100%;" {{ $recommendationType !== 'author' ? 'disabled' : '' }}>
                            <option></option>
                            @if ($selectedRecommendationAuthor)
                                <option value="{{ $selectedRecommendationAuthor->id }}" selected>{{ $selectedRecommendationAuthor->title }}</option>
                            @endif
                        </select>
                        <small class="form-text text-muted">Na stranici će se prikazati naslov “Knjige autora Ime autora”.</small>
                        @error('recommendation_author_id')
                            <div class="text-danger font-size-sm mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="recommendation-products-panel" class="form-group" style="{{ $recommendationType !== 'products' ? 'display: none;' : '' }}">
                        <label for="recommendation-products-select">Knjige</label>
                        <select class="form-control" id="recommendation-products-select" name="recommendation_product_ids[]" multiple style="width: 100%;" {{ $recommendationType !== 'products' ? 'disabled' : '' }}>
                            @foreach ($selectedRecommendationProducts as $selectedProduct)
                                <option value="{{ $selectedProduct->id }}" selected>
                                    {{ collect([$selectedProduct->name, $selectedProduct->sku ? 'Šifra: ' . $selectedProduct->sku : null, optional($selectedProduct->author)->title])->filter()->implode(' — ') }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Možete odabrati do 20 knjiga. Naslov carousela bit će “Pogledajte ponudu”.</small>
                        @error('recommendation_product_ids')
                            <div class="text-danger font-size-sm mt-1">{{ $message }}</div>
                        @enderror
                        @error('recommendation_product_ids.*')
                            <div class="text-danger font-size-sm mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Meta Data - SEO</h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-12">
                            @include('back.layouts.partials.language-tabs', ['id' => 'blog-seo-tabs'])
                            <div class="tab-content">
                                <div class="tab-pane active" id="blog-seo-tabs-hr" role="tabpanel">
                                    <div class="form-group">
                                        <label for="meta-title-input">Meta naslov</label>
                                        <input type="text" class="js-maxlength form-control" id="meta-title-input" name="meta_title" value="{{ isset($blog) ? $blog->meta_title : old('meta_title') }}" maxlength="70" data-always-show="true" data-placement="top">
                                        <small class="form-text text-muted">
                                            70 znakova max
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label for="meta-description-input">Meta opis</label>
                                        <textarea class="js-maxlength form-control" id="meta-description-input" name="meta_description" rows="4" maxlength="160" data-always-show="true" data-placement="top">{{ isset($blog) ? $blog->meta_description : old('meta_description') }}</textarea>
                                        <small class="form-text text-muted">
                                            160 znakova max
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label for="slug-input">SEO link (url)</label>
                                        <input type="text" class="form-control" id="slug-input" name="slug" value="{{ isset($blog) ? $blog->slug : old('slug') }}" disabled>
                                    </div>
                                </div>
                                <div class="tab-pane" id="blog-seo-tabs-en" role="tabpanel">
                                    <div class="form-group">
                                        <label for="meta-title-en-input">Meta naslov EN</label>
                                        <input type="text" class="js-maxlength form-control" id="meta-title-en-input" name="meta_title_en" value="{{ old('meta_title_en', isset($blog) ? $blog->meta_title_en : '') }}" maxlength="70" data-always-show="true" data-placement="top">
                                        <small class="form-text text-muted">
                                            70 znakova max
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label for="meta-description-en-input">Meta opis EN</label>
                                        <textarea class="js-maxlength form-control" id="meta-description-en-input" name="meta_description_en" rows="4" maxlength="160" data-always-show="true" data-placement="top">{{ old('meta_description_en', isset($blog) ? $blog->meta_description_en : '') }}</textarea>
                                        <small class="form-text text-muted">
                                            160 znakova max
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label for="slug-en-input">SEO link EN</label>
                                        <input type="text" class="form-control" id="slug-en-input" name="slug_en" value="{{ old('slug_en', isset($blog) ? $blog->slug_en : '') }}" placeholder="Ako je prazno koristi se HR slug">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="admin-form-actions">
                    @if (isset($blog))
                        <a href="{{ route('blogs.destroy', ['blog' => $blog]) }}" class="btn btn-outline-danger mr-auto js-tooltip-enabled" data-toggle="tooltip" title="" data-original-title="Obriši" onclick="event.preventDefault(); document.getElementById('delete-blog-form{{ $blog->id }}').submit();">
                            <i class="fa-duotone fa-trash-can mr-1" aria-hidden="true"></i> Obriši
                        </a>
                    @endif
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-duotone fa-floppy-disk mr-1" aria-hidden="true"></i> Snimi
                    </button>
                </div>
            </div>
        </form>

        @if (isset($blog))
            <form id="delete-blog-form{{ $blog->id }}" action="{{ route('blogs.destroy', ['blog' => $blog]) }}" method="POST" style="display: none;">
                @csrf
                {{ method_field('DELETE') }}
            </form>
        @endif
    </div>
@endsection

@push('js_after')
    <script src="{{ \App\Helpers\Asset::url('js/plugins/summernote/summernote-bs4.min.js') }}"></script>
    <script src="{{ \App\Helpers\Asset::url('js/plugins/summernote/lang/summernote-hr-HR.min.js') }}"></script>
    <script src="{{ \App\Helpers\Asset::url('js/plugins/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ \App\Helpers\Asset::url('js/plugins/select2/js/select2.full.min.js') }}"></script>

    <script>jQuery(function(){Dashmix.helpers(['flatpickr']);});</script>

    <script>
        $(() => {
            const recommendationEndpoint = @json(route('blogs.recommendations.search'));
            const editorUploadUrl = @json(route('blogs.upload.image'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const blogId = {{ (isset($blog->id) && $blog->id) ? (int) $blog->id : 0 }};
            const recommendationAjax = type => ({
                url: recommendationEndpoint,
                dataType: 'json',
                delay: 250,
                data: params => ({ type, q: params.term || '' }),
                processResults: response => response,
                cache: true,
            });

            $('#recommendation-author-select').select2({
                ajax: recommendationAjax('author'),
                allowClear: true,
                minimumInputLength: 2,
                placeholder: 'Pretražite autora...',
            });

            $('#recommendation-products-select').select2({
                ajax: recommendationAjax('product'),
                closeOnSelect: false,
                maximumSelectionLength: 20,
                minimumInputLength: 2,
                placeholder: 'Pretražite po naslovu, šifri ili autoru...',
            });

            const updateRecommendationPanels = () => {
                const type = $('input[name="recommendation_type"]:checked').val() || 'none';
                const authorActive = type === 'author';
                const productsActive = type === 'products';

                $('#recommendation-author-panel').toggle(authorActive);
                $('#recommendation-author-select').prop('disabled', !authorActive);
                $('#recommendation-products-panel').toggle(productsActive);
                $('#recommendation-products-select').prop('disabled', !productsActive);
            };

            $('input[name="recommendation_type"]').on('change', updateRecommendationPanels);
            updateRecommendationPanels();

            const showEditorUploadError = message => {
                console.error(message);
                window.alert(message);
            };

            const uploadEditorImage = (file, $editor) => {
                const data = new FormData();
                data.append('upload', file);
                data.append('blog_id', blogId);
                data.append('_token', csrfToken);

                return $.ajax({
                    url: editorUploadUrl,
                    method: 'POST',
                    data: data,
                    contentType: false,
                    processData: false,
                    headers: {
                        Accept: 'application/json',
                    },
                }).done(response => {
                    if (!response.uploaded || !response.url) {
                        showEditorUploadError('Slika nije učitana. Pokušajte ponovno.');
                        return;
                    }

                    $editor.summernote('insertImage', response.url, response.fileName || file.name);
                }).fail(xhr => {
                    const validationMessage = xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.upload
                        ? xhr.responseJSON.errors.upload[0]
                        : null;

                    showEditorUploadError(validationMessage || 'Slika nije učitana. Pokušajte ponovno.');
                });
            };

            const editorOptions = {
                height: 520,
                minHeight: 300,
                lang: 'hr-HR',
                dialogsInBody: true,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']],
                ],
                callbacks: {
                    onImageUpload: function (files) {
                        const $editor = $(this);

                        Array.from(files).forEach(file => uploadEditorImage(file, $editor));
                    },
                },
            };

            $('#description-editor, #description-en-editor').summernote(editorOptions);
        })
    </script>

    <script>
        function SetSEOPreview() {
            let title = $('#title-input').val();
            $('#slug-input').val(slugify(title));
        }

        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    $('#image-view')
                    .attr('src', e.target.result);
                };

                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>

@endpush
