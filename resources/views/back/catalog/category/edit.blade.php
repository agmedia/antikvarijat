@extends('back.layouts.backend')

@push('css_before')
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
@endpush

@section('content')

    @include('back.catalog.partials.editor-hero', [
        'icon' => 'fa-folder-bookmark',
        'title' => isset($category) ? 'Uredi kategoriju' : 'Nova kategorija',
        'description' => isset($category) ? $category->title : 'Dodajte novu cjelinu u strukturu kataloga.',
        'backUrl' => route('categories'),
    ])

    <div class="content content-full content-boxed admin-form-page">
        <!-- END Page Content -->
    @include('back.layouts.partials.session')
        <!-- New Post -->
        <form action="{{ isset($category) ? route('category.update', ['category' => $category]) : route('category.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if (isset($category))
                {{ method_field('PATCH') }}
            @endif
            <div class="block">
                <div class="block-header block-header-default">
                    <h2 class="block-title"><i class="fa-duotone fa-sliders mr-2"></i> Osnovne postavke</h2>
                    <div class="block-options">
                        <div class="custom-control custom-switch custom-control-success">
                            <input type="checkbox" class="custom-control-input" id="category-switch" name="status" {{ (isset($category->status) and $category->status) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="category-switch">Aktiviraj</label>
                        </div>
                    </div>
                </div>
                <div class="block-content">
                    <div class="row justify-content-center push">
                        <div class="col-md-12">

                            <div class="form-group">
                                <label for="group-select">Grupa</label>
                                <select class="js-select2 form-control" id="group-select" name="group" style="width: 100%;">
                                    @foreach ($groups as $group)
                                        <option value="{{ $group }}">{{ $group }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="parent-select">Glavna kategorija</label>
                                <select class="js-select2 form-control" id="parent-select" name="parent" style="width: 100%;">
                                    <option></option>
                                    @foreach ($parents as $id => $name)
                                        <option value="{{ $id }}" {{ (isset($category->parent_id) and $id == $category->parent_id) ? 'selected="selected"' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @include('back.layouts.partials.language-tabs', ['id' => 'category-content-tabs'])
                            <div class="tab-content">
                                <div class="tab-pane active" id="category-content-tabs-hr" role="tabpanel">
                                    <div class="form-group">
                                        <label for="title-input">Naziv kategorije</label>
                                        <input type="text" class="form-control" id="title-input" name="title" placeholder="Upišite naziv" value="{{ isset($category) ? $category->title : old('title') }}" onkeyup="SetSEOPreview()">
                                    </div>
                                    <div class="form-group">
                                        <label for="slug-input">SEO link (url)</label>
                                        <input type="text" class="form-control" id="slug-input" name="slug" value="{{ isset($category) ? $category->slug : old('slug') }}">
                                    </div>
                                    <div class="form-group">
                                        <label for="dm-post-edit-slug">Opis kategorije</label>
                                        <textarea id="description-editor" name="description">{!! isset($category) ? $category->description : old('description') !!}</textarea>
                                    </div>
                                </div>
                                <div class="tab-pane" id="category-content-tabs-en" role="tabpanel">
                                    <div class="form-group">
                                        <label for="title-en-input">Naziv kategorije EN</label>
                                        <input type="text" class="form-control" id="title-en-input" name="title_en" placeholder="Upišite engleski naziv" value="{{ old('title_en', isset($category) ? $category->title_en : '') }}">
                                    </div>
                                    <div class="form-group">
                                        <label for="slug-en-input">SEO link EN</label>
                                        <input type="text" class="form-control" id="slug-en-input" name="slug_en" value="{{ old('slug_en', isset($category) ? $category->slug_en : '') }}" placeholder="Ako je prazno koristi se HR slug">
                                    </div>
                                    <div class="form-group">
                                        <label for="description-en-editor">Opis kategorije EN</label>
                                        <textarea id="description-en-editor" name="description_en">{!! old('description_en', isset($category) ? $category->description_en : '') !!}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Meta Data - SEO</h3>
                </div>
                <div class="block-content">
                    <div class="row justify-content-center">
                        <div class="col-md-12">
                            @include('back.layouts.partials.language-tabs', ['id' => 'category-seo-tabs'])
                            <div class="tab-content">
                                <div class="tab-pane active" id="category-seo-tabs-hr" role="tabpanel">
                                    <div class="form-group">
                                        <label for="meta-title-input">Meta naslov</label>
                                        <input type="text" class="js-maxlength form-control" id="meta-title-input" name="meta_title" value="{{ isset($category) ? $category->meta_title : old('meta_title') }}" maxlength="70" data-always-show="true" data-placement="top">
                                        <small class="form-text text-muted">
                                            70 znakova max
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label for="meta-description-input">Meta opis</label>
                                        <textarea class="js-maxlength form-control" id="meta-description-input" name="meta_description" rows="4" maxlength="160" data-always-show="true" data-placement="top">{{ isset($category) ? $category->meta_description : old('meta_description') }}</textarea>
                                        <small class="form-text text-muted">
                                            160 znakova max
                                        </small>
                                    </div>
                                </div>
                                <div class="tab-pane" id="category-seo-tabs-en" role="tabpanel">
                                    <div class="form-group">
                                        <label for="meta-title-en-input">Meta naslov EN</label>
                                        <input type="text" class="js-maxlength form-control" id="meta-title-en-input" name="meta_title_en" value="{{ old('meta_title_en', isset($category) ? $category->meta_title_en : '') }}" maxlength="70" data-always-show="true" data-placement="top">
                                        <small class="form-text text-muted">
                                            70 znakova max
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label for="meta-description-en-input">Meta opis EN</label>
                                        <textarea class="js-maxlength form-control" id="meta-description-en-input" name="meta_description_en" rows="4" maxlength="160" data-always-show="true" data-placement="top">{{ old('meta_description_en', isset($category) ? $category->meta_description_en : '') }}</textarea>
                                        <small class="form-text text-muted">
                                            160 znakova max
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-xl-6">
                                    <label>Open Graph slika</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="image-input" name="image" data-toggle="custom-file-input" onchange="readURL(this);">
                                        <label class="custom-file-label" for="image-input">Odaberite sliku</label>
                                    </div>
                                    <div class="mt-2">
                                        <img class="img-fluid" id="image-view" src="{{ \App\Support\AdminImage::url(isset($category) ? $category->image : null, 'media/img/lightslider.webp') }}" alt="">
                                    </div>
                                    <div class="form-text text-muted font-size-sm font-italic">Slika koja se pokazuje kada se link dijeli (facebook, twitter, itd.)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="admin-form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Snimi
                            </button>
                        @if (isset($category))
                                <a href="{{ route('category.destroy', ['category' => $category]) }}" class="btn btn-outline-danger js-tooltip-enabled" data-toggle="tooltip" title="" data-original-title="Obriši" onclick="event.preventDefault(); document.getElementById('delete-category-form{{ $category->id }}').submit();">
                                    <i class="fa fa-trash-alt"></i> Obriši
                                </a>
                        @endif
                </div>
            </div>
        </form>
        <!-- END New Post -->
        @if (isset($category))
            <form id="delete-category-form{{ $category->id }}" action="{{ route('category.destroy', ['category' => $category]) }}" method="POST" style="display: none;">
                @csrf
                {{ method_field('DELETE') }}
            </form>
        @endif
    </div>


@endsection

@push('js_after')
    <script src="{{ asset('js/plugins/ckeditor5-classic/build/ckeditor.js') }}"></script>
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>

    <script>
        $(() => {
            $('#group-select').select2({
                placeholder: 'Odaberite ili upišite novu grupu...',
                tags: true
            });

            $('#parent-select').select2({
                placeholder: 'Ostavite prazno ako želite da ovo bude glavna kategorija.'
            });

            ClassicEditor
            .create( document.querySelector('#description-editor'))
            .then( editor => {
                console.log(editor);
            } )
            .catch( error => {
                console.error(error);
            } );

            ClassicEditor
            .create( document.querySelector('#description-en-editor'))
            .then( editor => {
                console.log(editor);
            } )
            .catch( error => {
                console.error(error);
            } );
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
