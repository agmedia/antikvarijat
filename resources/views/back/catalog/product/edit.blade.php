@extends('back.layouts.backend')

@push('css_before')
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('js/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('js/plugins/dropzone/min/dropzone.min.css') }}">
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('js/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.min.css') }}">
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('css/plugins/slim/slim.css') }}">

    @stack('product_css')
@endpush

@section('content')
    @php
        $selectedCategoryIds = $selectedCategoryIds ?? [];
        $selectedSubcategoryId = $selectedSubcategoryId ?? null;
        $hasActiveSpecial = isset($product) && (float) $product->special > 0 && $product->special() !== false;
        $productPhotoCount = ($existingImagesCount ?? 0) + ((isset($product) && ! empty($product->image)) ? 1 : 0);
    @endphp

    @include('back.catalog.partials.editor-hero', [
        'icon' => 'fa-book-open-cover',
        'title' => isset($product) ? 'Uredi artikl' : 'Novi artikl',
        'description' => isset($product) ? $product->name : 'Unesite bibliografske podatke, fotografije, zalihe i SEO sadržaj.',
        'backUrl' => route('products'),
    ])
    <!-- Page Content -->
    <div class="content content-full admin-form-page">
        @include('back.layouts.partials.session')

        <form action="{{ isset($product) ? route('products.update', ['product' => $product]) : route('products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if (isset($product))
                {{ method_field('PATCH') }}
            @endif

            <div class="block block-rounded product-editor-shell">
                <div class="product-editor-overview">
                    <div class="product-editor-identity">
                        <div class="product-editor-cover">
                            @if (isset($product) && ! empty($product->image))
                                <img src="{{ \App\Support\AdminImage::url($product->image) }}" alt="{{ $product->name }}">
                            @else
                                <i class="fa-duotone fa-book-open-cover"></i>
                            @endif
                        </div>
                        <div class="product-editor-summary">
                            <span class="product-editor-eyebrow">{{ isset($product) ? 'Artikl #' . $product->id : 'Novi zapis' }}</span>
                            <strong>{{ isset($product) ? $product->name : 'Novi artikl' }}</strong>
                            <div class="product-editor-meta">
                                <span><i class="fa-duotone fa-barcode"></i> Šifra: {{ isset($product) && $product->sku ? $product->sku : '—' }}</span>
                                <span><i class="fa-duotone fa-boxes-stacked"></i> Zaliha: {{ isset($product) ? $product->quantity : 1 }}</span>
                                <span><i class="fa-duotone fa-tag"></i> {{ isset($product) && $product->price !== null ? number_format((float) $product->price, 2, ',', '.') . ' €' : 'Cijena nije unesena' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="product-editor-status">
                        <div>
                            <span class="product-editor-eyebrow">Vidljivost artikla</span>
                            <strong id="product-status-label">{{ (isset($product->status) && $product->status) ? 'Aktivan' : 'Neaktivan' }}</strong>
                        </div>
                        <div class="custom-control custom-switch custom-control-success">
                            <input type="checkbox" class="custom-control-input" id="product-switch" name="status"{{ (isset($product->status) and $product->status) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="product-switch"><span class="sr-only">Promijeni vidljivost artikla</span></label>
                        </div>
                    </div>
                </div>

                <ul class="nav nav-tabs nav-tabs-block product-editor-tabs" data-toggle="tabs" role="tablist" aria-label="Uređivanje artikla">
                    <li class="nav-item">
                        <a class="nav-link active" href="#osnovno"><i class="fa-duotone fa-book-open-cover"></i> {{ __('Sadržaj i podaci') }}</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#slike"><i class="fa-duotone fa-images"></i> {{ __('Fotografije') }} <span class="product-tab-count">{{ $productPhotoCount }}</span></a>
                    </li>

                    <li class="nav-item ">
                        <a class="nav-link" href="#seo">
                            <i class="fa-duotone fa-magnifying-glass-chart"></i> {{ __('SEO') }}
                        </a>
                    </li>

                    <li class="nav-item ">
                        <a class="nav-link" href="#promjene">
                            <i class="fa-duotone fa-clock-rotate-left"></i> {{ __('Promjene') }} <span class="product-tab-count">{{ isset($logs) ? $logs->count() : 0 }}</span>
                        </a>
                    </li>
                </ul>

                <div class="block-content tab-content product-editor-content">
                    <div class="tab-pane active product-editor-pane" id="osnovno" role="tabpanel">
                        <section class="product-edit-section">
                            <div class="product-edit-section-heading">
                                <div class="product-edit-section-icon"><i class="fa-duotone fa-align-left"></i></div>
                                <div>
                                    <h3>Naziv i opis</h3>
                                    <p>Glavni sadržaj koji kupac vidi na stranici artikla.</p>
                                </div>
                            </div>
                            <div class="product-edit-section-body">
                                @include('back.layouts.partials.language-tabs', ['id' => 'product-content-tabs'])
                                <div class="tab-content">
                                    <div class="tab-pane active" id="product-content-tabs-hr" role="tabpanel">
                                        <div class="form-group row items-push mb-3">
                                            <div class="col-md-12">
                                                <label for="dm-post-edit-title">Naziv <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="name-input" name="name" placeholder="Upišite naziv artikla" value="{{ isset($product) ? $product->name : old('name') }}" onkeyup="SetSEOPreview()">
                                                @error('name')
                                                <span class="text-danger font-italic">Naziv je potreban...</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="form-group row mb-4">
                                            <div class="col-md-12">
                                                <label for="description-editor">Opis</label>
                                                <textarea id="description-editor" name="description">{!! isset($product) ? $product->description : old('description') !!}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane" id="product-content-tabs-en" role="tabpanel">
                                        <div class="form-group row items-push mb-3">
                                            <div class="col-md-12">
                                                <label for="name-en-input">Naziv EN <span class="small text-muted">(neobavezno, ako je prazno prikazuje se HR naziv)</span></label>
                                                <input type="text" class="form-control" id="name-en-input" name="name_en" placeholder="Upišite engleski naziv artikla" value="{{ old('name_en', isset($product) ? $product->name_en : '') }}">
                                            </div>
                                        </div>
                                        <div class="form-group row mb-4">
                                            <div class="col-md-12">
                                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                                                    <label for="description-en-editor" class="mb-0">Opis EN</label>
                                                    <button type="button" class="btn btn-sm btn-alt-primary" id="translate-description-en">
                                                        <i class="fa fa-language mr-1"></i> Prevedi iz HR
                                                    </button>
                                                </div>
                                                <textarea id="description-en-editor" name="description_en">{!! old('description_en', isset($product) ? $product->description_en : '') !!}</textarea>
                                                <div class="font-size-sm text-muted mt-2" id="translate-description-status"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </section>

                        <section class="product-edit-section">
                            <div class="product-edit-section-heading">
                                <div class="product-edit-section-icon"><i class="fa-duotone fa-cash-register"></i></div>
                                <div>
                                    <h3>Prodaja i zaliha</h3>
                                    <p>Cijena, dostupnost, lokacija i vremenski ograničena akcija.</p>
                                </div>
                            </div>
                            <div class="product-edit-section-body">

                                <div class="form-group row items-push mb-3">
                                    <div class="col-md-2">
                                        <label for="quantity-input">Količina <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="quantity-input" name="quantity" placeholder="Upišite količinu artikla" value="{{ isset($product) ? $product->quantity : ( ! isset($product) ? 1 : old('quantity')) }}">
                                        @error('quantity ')
                                        <span class="text-danger font-italic">Količina je potrebna...</span>
                                        @enderror
                                    </div>

                                    <div class="col-md-2">
                                        <label for="skl-input">Skladište</label>
                                        <input type="text" class="form-control" id="skl-input" name="skl" placeholder="Upišite skladište" value="{{ old('skl', isset($product) ? $product->skl : '') }}">
                                        @error('skl')
                                        <span class="text-danger font-italic">Skladište mora biti cijeli broj.</span>
                                        @enderror
                                    </div>

                                    <div class="col-md-2">
                                        <label for="sku-input">Šifra <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="sku-input" name="sku" placeholder="Upišite šifru artikla" value="{{ isset($product) ? $product->sku : old('sku') }}">
                                        @error('sku')
                                        <span class="text-danger font-italic">Šifra je potrebna...</span>
                                        @enderror
                                        @error('sku_dupl')
                                        <span class="text-danger small font-italic">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="col-md-2">
                                        <label for="polica-input">Šifra police </label>
                                        <input type="text" class="form-control" id="polica-input" name="polica" placeholder="Upišite šifru police" value="{{ isset($product) ? $product->polica : old('polica') }}" >
                                    </div>

                                    <div class="col-md-2">
                                        <label for="price-input">Cijena <span class="text-danger">*</span> <span class="small text-gray">(S PDV-om)</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="price-input" name="price" placeholder="00.00" value="{{ isset($product) ? $product->price : old('price') }}">
                                            <div class="input-group-append">
                                                <span class="input-group-text">EUR</span>
                                            </div>
                                        </div>
                                        @error('price')
                                        <span class="text-danger font-italic">Cijena je potrebna...</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-2">
                                        <label for="tax-select">Porez</label>
                                        <select class="js-select2 form-control" id="tax-select" name="tax_id" style="width: 100%;" data-placeholder="Odaberite porez...">
                                            <option></option>
                                            @foreach ($data['taxes'] as $tax)
                                                <option value="{{ $tax->id }}" {{ ((isset($product)) and ($tax->id == $product->tax_id)) ? 'selected' : (( ! isset($product) and ($tax->id == 1)) ? 'selected' : '') }}>{{ $tax->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group mb-4">
                                    <input type="hidden" name="delivery_in_7_days" value="0">
                                    <div class="custom-control custom-checkbox custom-control-success">
                                        <input type="checkbox" class="custom-control-input" id="delivery-in-7-days" name="delivery_in_7_days" value="1" {{ old('delivery_in_7_days', isset($product) ? $product->delivery_in_7_days : false) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="delivery-in-7-days">Isporuka za 7 dana</label>
                                    </div>
                                    <small class="form-text text-muted">Na stranici artikla prikazuje se obavijest da je rok isporuke 7 dana.</small>
                                    @error('delivery_in_7_days')
                                    <span class="text-danger small font-italic">Vrijednost roka isporuke nije ispravna.</span>
                                    @enderror
                                </div>

                                {{--                            @if( ! isset($product) && $active_actions->count())--}}
                                {{--                                <div class="alert alert-secondary d-flex align-items-center justify-content-between" role="alert">--}}
                                {{--                                    <div class="flex-fill mr-3">--}}
                                {{--                                        <p class="mb-0">Upozorenje..! Postoje aktivne akcije u trgovini!</p>--}}
                                {{--                                    </div>--}}
                                {{--                                    <div class="flex-00-auto">--}}
                                {{--                                        <select class="js-select2 form-control" id="action-select" style="width: 100%;" data-placeholder="Odaberite akciju...">--}}
                                {{--                                            <option></option>--}}
                                {{--                                            @foreach ($active_actions as $action)--}}
                                {{--                                                <option value="{{ $action->id }}">{{ $action->title }}</option>--}}
                                {{--                                            @endforeach--}}
                                {{--                                        </select>--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}
                                {{--                            @endif--}}

                                @if ($hasActiveSpecial)
                                    <input type="hidden" name="action" value="{{ (int) $product->action_id }}">
                                    <div class="product-special-notice is-active">
                                        <i class="fa-duotone fa-badge-percent"></i>
                                        <div>
                                            <strong>Akcijska cijena je aktivna</strong>
                                            <span>Webshop trenutačno koristi ovu cijenu u navedenom razdoblju.</span>
                                        </div>
                                    </div>

                                <div class="form-group row items-push mb-3 product-active-special-fields">
                                    <div class="col-md-4">
                                        <label for="special-input">Akcijska cijena</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="special-input" name="special" placeholder="00.00" value="{{ old('special', $hasActiveSpecial ? $product->special : '') }}">
                                            <div class="input-group-append">
                                                <span class="input-group-text">EUR</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="special-from-input">Razdoblje primjene</label>
                                        <div class="input-daterange input-group" data-date-format="mm/dd/yyyy" data-week-start="1" data-autoclose="true" data-today-highlight="true">
                                            <input type="text" class="form-control" id="special-from-input" name="special_from" placeholder="od" value="{{ old('special_from', $hasActiveSpecial && isset($product->special_from) ? \Carbon\Carbon::make($product->special_from)->format('d.m.Y') : '') }}" data-week-start="1" data-autoclose="true" data-today-highlight="true">
                                            <div class="input-group-prepend input-group-append">
                                                <span class="input-group-text font-w600"><i class="fa fa-fw fa-arrow-right"></i></span>
                                            </div>
                                            <input type="text" class="form-control" id="special-to-input" name="special_to" placeholder="do" value="{{ old('special_to', $hasActiveSpecial && isset($product->special_to) ? \Carbon\Carbon::make($product->special_to)->format('d.m.Y') : '') }}" data-week-start="1" data-autoclose="true" data-today-highlight="true">
                                        </div>
                                    </div>
                                </div>
                                @endif

                            </div>
                        </section>

                        <section class="product-edit-section">
                            <div class="product-edit-section-heading">
                                <div class="product-edit-section-icon"><i class="fa-duotone fa-books"></i></div>
                                <div>
                                    <h3>Klasifikacija</h3>
                                    <p>Kategorija, autor, izdavač i podaci koji olakšavaju pretraživanje kataloga.</p>
                                </div>
                            </div>
                            <div class="product-edit-section-body">
                                <div class="form-group row mb-4">
                                    <div class="col-md-12">
                                        <label for="tagInput">Tagovi</label>

                                        {{-- Skriveni input koji šaljemo u formi (comma-separated) --}}
                                        <input type="hidden" name="tags" id="tagsHidden"
                                               value="{{ old('tags', isset($product) ? $product->tags_string : '') }}">

                                        {{-- Vidljivi input za unos tagova (nema name, da ne pregazi hidden) --}}
                                        <input type="text" id="tagInput" class="form-control" placeholder="Upiši tag i pritisni Enter ili odaberi postojeći ,">

                                        {{-- Prikaz odabranih tagova kao badge-ova --}}

                                        <div id="tagBadges" class="mt-2"></div>

                                        {{-- Ponuda/sugestije postojećih tagova --}}
                                        <small>Ponuda/sugestije postojećih tagova</small>
                                        <div id="tagSuggestions" class="mt-2"></div>

                                        @error('tags')
                                        <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>






                                <div class="form-group row items-push mb-4">
                                    <div class="col-md-4">
                                        <label for="dm-post-edit-slug">Kategorija <span class="text-danger">*</span></label>
                                        <select class="js-select2 form-control admin-category-select" id="category-select" name="category" style="width: 100%;" data-placeholder="Odaberite kategoriju">
                                            <option></option>
                                            @foreach ($data['categories'] as $group => $cats)
                                                <optgroup label="{{ $group }}">
                                                    @foreach ($cats as $id => $category)
                                                        <option value="{{ $id }}"
                                                                data-level="0"
                                                                data-group="{{ $group }}"
                                                                {{ in_array((int) $id, $selectedCategoryIds, true) ? 'selected' : '' }}>{{ $category['title'] }}</option>
                                                        @if ( ! empty($category['subs']))
                                                            @foreach ($category['subs'] as $sub_id => $subcategory)
                                                                <option value="{{ $sub_id }}"
                                                                        data-level="1"
                                                                        data-group="{{ $group }}"
                                                                        data-parent="{{ $category['title'] }}"
                                                                        {{ (int) $sub_id === (int) $selectedSubcategoryId ? 'selected' : '' }}>{{ $subcategory['title'] }}</option>
                                                            @endforeach
                                                        @endif
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                        @error('category')
                                        <span class="text-danger font-italic">Kategorija je potrebna...</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="dm-post-edit-slug">Autor</label>
                                        @livewire('back.layout.search.author-search', ['author_id' => isset($product) ? $product->author_id : 0])
                                    </div>
                                    <div class="col-md-4">
                                        <label for="dm-post-edit-slug">Izdavač</label>
                                        @livewire('back.layout.search.publisher-search', ['publisher_id' => isset($product) ? $product->publisher_id : 0])
                                    </div>
                                </div>

                                <div class="form-group row items-push mb-4">
                                    <div class="col-md-4">
                                        <label for="letter-select">Pismo</label>
                                        <select class="js-select2 form-control" id="letter-select" name="letter" style="width: 100%;" data-placeholder="Odaberite ili upišite pismo">
                                            <option></option>
                                            @if ($data['letters'])
                                                @foreach ($data['letters'] as $letter)
                                                    <option value="{{ $letter }}" {{ ((isset($product)) and ($letter == $product->letter)) ? 'selected' : '' }}>{{ $letter }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="dm-post-edit-slug">Stanje</label>
                                        <select class="js-select2 form-control" id="condition-select" name="condition" style="width: 100%;" data-placeholder="Odaberite ili upišite stanje">
                                            <option></option>
                                            @if ($data['conditions'])
                                                @foreach ($data['conditions'] as $condition)
                                                    <option value="{{ $condition }}" {{ ((isset($product)) and ($condition == $product->condition)) ? 'selected' : '' }}>{{ $condition }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="dm-post-edit-slug">Uvez</label>
                                        <select class="js-select2 form-control" id="binding-select" name="binding" style="width: 100%;" data-placeholder="Odaberite ili upišite uvez">
                                            <option></option>
                                            @if ($data['bindings'])
                                                @foreach ($data['bindings'] as $binding)
                                                    <option value="{{ $binding }}" {{ ((isset($product)) and ($binding == $product->binding)) ? 'selected' : '' }}>{{ $binding }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>

                            </div>
                        </section>

                        <section class="product-edit-section">
                            <div class="product-edit-section-heading">
                                <div class="product-edit-section-icon"><i class="fa-duotone fa-book-bookmark"></i></div>
                                <div>
                                    <h3>Bibliografski podaci</h3>
                                    <p>Podaci o izdanju i fizičkim karakteristikama knjige.</p>
                                </div>
                            </div>
                            <div class="product-edit-section-body">
                                <div class="form-group row items-push mb-3">
                                    <div class="col-md-4">
                                        <label for="isbn-input">ISBN <span class="small text-muted">(neobavezno)</span></label>
                                        <input type="text" class="form-control" id="isbn-input" name="isbn" maxlength="20" placeholder="ISBN-10 ili ISBN-13" value="{{ old('isbn', isset($product) ? $product->isbn : '') }}">
                                        @error('isbn')
                                        <span class="text-danger small font-italic">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-2">
                                        <label for="origin-input">Mjesto izdavanja</label>
                                        <input type="text" class="form-control" id="origin-input" name="origin" placeholder="Upišite mjesto izdavanja" value="{{ isset($product) ? $product->origin : old('origin') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="year-input">Godina izdavanja</label>
                                        <input type="text" class="form-control" id="year-input" name="year" placeholder="Upišite godinu izdavanja" value="{{ isset($product) ? $product->year : old('year') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="pages-input">Broj stranica</label>
                                        <input type="text" class="form-control" id="pages-input" name="pages" placeholder="Upišite broj stranica" value="{{ isset($product) ? $product->pages : old('pages') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="dimensions-input">Dimenzije</label>
                                        <input type="text" class="form-control" id="dimensions-input" name="dimensions" placeholder="Upišite dimenzije" value="{{ isset($product) ? $product->dimensions : old('dimensions') }}">
                                    </div>
                                </div>

                            </div>
                        </section>
                    </div>
                    <div class="tab-pane product-editor-pane" id="slike" role="tabpanel">
                        <section class="product-edit-section">
                            <div class="product-edit-section-heading">
                                <div class="product-edit-section-icon"><i class="fa-duotone fa-images"></i></div>
                                <div>
                                    <h3>Fotografije artikla</h3>
                                    <p>Dodajte više prikaza, odaberite glavnu fotografiju i uredite opis slike.</p>
                                </div>
                            </div>
                            <div class="product-edit-section-body">
                                @include('back.catalog.product.edit-photos')
                            </div>
                        </section>
                    </div>
                    <div class="tab-pane product-editor-pane" id="promjene" role="tabpanel">
                        <section class="product-edit-section">
                            <div class="product-edit-section-heading">
                                <div class="product-edit-section-icon"><i class="fa-duotone fa-clock-rotate-left"></i></div>
                                <div>
                                    <h3>Povijest promjena</h3>
                                    <p>Zabilježene izmjene artikla, korisnik i vrijeme promjene.</p>
                                </div>
                            </div>
                            <div class="product-edit-section-body">
                            @if($logs->isEmpty())
                                <div class="admin-empty-state py-5">
                                    <i class="fa-duotone fa-clock-rotate-left"></i>
                                    <h3>Nema zabilježenih promjena</h3>
                                    <p class="text-muted mb-0">Za ovaj artikl još nema zapisa u povijesti.</p>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm product-history-table">
                                        <thead>
                                        <tr>
                                            <th>Korisnik</th>
                                            <th>Promjena</th>
                                            <th>Datum</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($logs as $log)
                                            <tr>
                                                <td>{{ optional($log->user)->name ?? 'N/A' }}</td>
                                                <td>
                                                    {!! $log->title !!}<br>
                                                    {!! $log->changes !!}
                                                </td>
                                                <td>{{ $log->created_at->format('d.m.Y H:i') }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                            </div>
                        </section>
                    </div>

                    <div class="tab-pane product-editor-pane" id="seo" role="tabpanel">
                        <section class="product-edit-section">
                            <div class="product-edit-section-heading">
                                <div class="product-edit-section-icon"><i class="fa-duotone fa-magnifying-glass-chart"></i></div>
                                <div>
                                    <h3>SEO podaci</h3>
                                    <p>Naslov, opis i adresa koji se prikazuju u tražilicama.</p>
                                </div>
                            </div>
                            <div class="product-edit-section-body product-seo-fields">
                            @include('back.layouts.partials.language-tabs', ['id' => 'product-seo-tabs'])
                            <div class="tab-content">
                                <div class="tab-pane active" id="product-seo-tabs-hr" role="tabpanel">
                                    <div class="form-group">
                                        <label for="meta-title-input">Meta naslov</label>
                                        <input type="text" class="js-maxlength form-control" id="meta-title-input" name="meta_title" value="{{ isset($product) ? $product->meta_title : old('meta_title') }}" maxlength="70" data-always-show="true" data-placement="bottom-right" placeholder="Naslov rezultata u tražilici">
                                        <small class="form-text text-muted">Preporučeno do 70 znakova.</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="meta-description-input">Meta opis</label>
                                        <textarea class="js-maxlength form-control" id="meta-description-input" name="meta_description" rows="4" maxlength="160" data-always-show="true" data-placement="bottom-right" placeholder="Kratak opis koji potiče korisnika na klik">{{ isset($product) ? $product->meta_description : old('meta_description') }}</textarea>
                                        <small class="form-text text-muted">Preporučeno do 160 znakova.</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="slug-input">SEO link (url)</label>
                                        <input type="text" class="form-control" id="slug-input" name="slug" value="{{ isset($product) ? $product->slug : old('slug') }}" disabled>
                                        <small class="form-text text-muted">Automatski se izrađuje iz naziva artikla.</small>
                                    </div>
                                </div>
                                <div class="tab-pane" id="product-seo-tabs-en" role="tabpanel">
                                    <div class="form-group">
                                        <label for="meta-title-en-input">Meta naslov EN</label>
                                        <input type="text" class="js-maxlength form-control" id="meta-title-en-input" name="meta_title_en" value="{{ old('meta_title_en', isset($product) ? $product->meta_title_en : '') }}" maxlength="70" data-always-show="true" data-placement="bottom-right" placeholder="English search result title">
                                        <small class="form-text text-muted">Preporučeno do 70 znakova.</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="meta-description-en-input">Meta opis EN</label>
                                        <textarea class="js-maxlength form-control" id="meta-description-en-input" name="meta_description_en" rows="4" maxlength="160" data-always-show="true" data-placement="bottom-right" placeholder="English description shown in search results">{{ old('meta_description_en', isset($product) ? $product->meta_description_en : '') }}</textarea>
                                        <small class="form-text text-muted">Preporučeno do 160 znakova.</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="slug-en-input">SEO link EN</label>
                                        <input type="text" class="form-control" id="slug-en-input" name="slug_en" value="{{ old('slug_en', isset($product) ? $product->slug_en : '') }}" placeholder="Ako je prazno koristi se HR slug">
                                        @if (isset($product) && $product->url_en)
                                            <small class="form-text text-muted">EN URL: /{{ $product->url_en }}</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="admin-form-actions product-editor-actions">
                            <span class="product-save-note"><i class="fa-duotone fa-circle-info"></i> Promjene se primjenjuju tek nakon spremanja.</span>
                            @if (isset($product))
                                <a href="{{ route('products.destroy', ['product' => $product]) }}" class="btn btn-outline-danger product-delete-action js-tooltip-enabled" data-toggle="tooltip" title="" data-original-title="Obriši artikl" onclick="event.preventDefault(); document.getElementById('delete-product-form{{ $product->id }}').submit();">
                                    <i class="fa-duotone fa-trash-can mr-1"></i> Obriši
                                </a>
                            @endif
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-duotone fa-floppy-disk mr-1"></i> Spremi artikl
                            </button>
                </div>
            </div>

        </form>

        @if (isset($product))
            <form id="delete-product-form{{ $product->id }}" action="{{ route('products.destroy', ['product' => $product]) }}" method="POST" style="display: none;">
                @csrf
                {{ method_field('DELETE') }}
            </form>
        @endif
    </div>
@endsection

@push('js_after')
    <!-- Page JS Plugins -->
    <script src="{{ \App\Helpers\Asset::url('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ \App\Helpers\Asset::url('js/plugins/ckeditor5-classic/build/ckeditor.js') }}"></script>
    <script src="{{ \App\Helpers\Asset::url('js/plugins/dropzone/min/dropzone.min.js') }}"></script>
    <script src="{{ \App\Helpers\Asset::url('js/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ \App\Helpers\Asset::url('js/plugins/jquery.maskedinput/jquery.maskedinput.min.js') }}"></script>
    <script src="{{ \App\Helpers\Asset::url('js/plugins/slim/slim.kickstart.js') }}"></script>

    <!-- Page JS Helpers (CKEditor 5 plugins) -->
    <script>jQuery(function(){Dashmix.helpers(['datepicker']);});</script>

    <script>
        $(() => {
            let descriptionEditor = null;
            let descriptionEnEditor = null;
            const translateButton = $('#translate-description-en');
            const translateStatus = $('#translate-description-status');

            translateButton.prop('disabled', true);

            ClassicEditor
            .create(document.querySelector('#description-editor'))
            .then(editor => {
                descriptionEditor = editor;

                if (descriptionEnEditor) {
                    translateButton.prop('disabled', false);
                }
            })
            .catch(error => {
                console.error(error);
            });

            ClassicEditor
            .create(document.querySelector('#description-en-editor'))
            .then(editor => {
                descriptionEnEditor = editor;

                if (descriptionEditor) {
                    translateButton.prop('disabled', false);
                }
            })
            .catch(error => {
                console.error(error);
            });

            translateButton.on('click', function () {
                const source = descriptionEditor ? descriptionEditor.getData() : $('#description-editor').val();
                const plainSource = $('<div>').html(source || '').text().replace(/\u00a0/g, ' ').trim();

                if (!plainSource) {
                    translateStatus.removeClass('text-success').addClass('text-danger').text('HR opis je prazan.');
                    return;
                }

                const button = $(this);
                const originalHtml = button.html();

                button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Prevodim...');
                translateStatus.removeClass('text-danger text-success').addClass('text-muted').text('Prijevod je u tijeku...');

                axios.post("{{ url('admin/catalog/product/translate-description') }}", {
                    description: source
                }).then(response => {
                    const translated = response.data && response.data.text ? response.data.text : '';

                    if (descriptionEnEditor) {
                        descriptionEnEditor.setData(translated);
                    } else {
                        $('#description-en-editor').val(translated);
                    }

                    translateStatus.removeClass('text-danger text-muted').addClass('text-success').text('Prijevod je upisan u Opis EN. Snimi artikl za spremanje.');
                }).catch(error => {
                    const message = error.response && error.response.data && error.response.data.message
                        ? error.response.data.message
                        : 'Prijevod nije uspio.';

                    translateStatus.removeClass('text-success text-muted').addClass('text-danger').text(message);
                }).finally(() => {
                    button.prop('disabled', false).html(originalHtml);
                });
            });

            const categorySelectOptions = {
                placeholder: 'Odaberite kategoriju',
                width: '100%',
                dropdownCssClass: 'admin-category-dropdown',
                templateResult: function (item) {
                    if (!item.id) {
                        return item.text;
                    }

                    const element = $(item.element);
                    const level = Number(element.data('level') || 0);
                    const parent = element.data('parent') || '';
                    const icon = level > 0 ? 'fa-turn-down-right' : 'fa-folder';
                    const row = $('<span class="admin-category-option"></span>');

                    row.toggleClass('is-subcategory', level > 0);
                    row.append($('<i class="fa-solid ' + icon + ' admin-category-option-icon" aria-hidden="true"></i>'));
                    row.append($('<span class="admin-category-option-copy"></span>')
                        .append($('<strong></strong>').text(item.text))
                        .append(parent ? $('<small></small>').text(parent) : $('<small></small>').text('Glavna')));

                    return row;
                },
                templateSelection: function (item) {
                    if (!item.id) {
                        return item.text;
                    }

                    const element = $(item.element);
                    const group = element.data('group') || '';
                    const parent = element.data('parent') || '';
                    const path = [group, parent, item.text].filter(Boolean).join(' / ');

                    return $('<span class="admin-category-selection"></span>')
                        .append($('<i class="fa-solid fa-folder-tree" aria-hidden="true"></i>'))
                        .append($('<span></span>').text(path));
                }
            };

            $('#category-select').select2(categorySelectOptions);
            $('#tax-select').select2({});
            $('#action-select').select2({
                placeholder: 'Odaberite...',
                minimumResultsForSearch: Infinity
            });
            $('#author-select').select2({
                tags: true
            });
            $('#publisher-select').select2({
                tags: true
            });
            $('#letter-select').select2({
                tags: true
            });
            $('#binding-select').select2({
                tags: true
            });
            $('#condition-select').select2({
                tags: true
            });

            const productStatusSwitch = document.getElementById('product-switch');
            const productStatusLabel = document.getElementById('product-status-label');
            if (productStatusSwitch && productStatusLabel) {
                productStatusSwitch.addEventListener('change', function () {
                    productStatusLabel.textContent = this.checked ? 'Aktivan' : 'Neaktivan';
                });
            }

            const productTabLinks = $('.product-editor-tabs a[data-toggle="tab"], .product-editor-tabs a[href^="#"]');
            const defaultProductTab = productTabLinks.filter('[href="#osnovno"]');

            // Uređivanje svakog artikla uvijek počinje na osnovnim podacima.
            // Aktivni tab ne smije se prenositi s prethodno otvorenog artikla.
            if (defaultProductTab.length) {
                defaultProductTab.tab('show');
            }
            if (window.location.hash && productTabLinks.filter('[href="' + window.location.hash + '"]').length && window.history && window.history.replaceState) {
                window.history.replaceState(null, document.title, window.location.pathname + window.location.search);
            }

            Livewire.on('success_alert', () => {

            });

            Livewire.on('error_alert', (e) => {

            });
        })
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input   = document.getElementById('tagInput');
            const hidden  = document.getElementById('tagsHidden');
            const badges  = document.getElementById('tagBadges');
            const sugg    = document.getElementById('tagSuggestions');

            // Svi postojeći tagovi iz baze (controller)
            const ALL_TAGS = {!! json_encode($allTags ?? []) !!};

            // --- Guard protiv blur-dodavanja dok biramo sugestiju ---
            let suppressBlur = false;

            // --- Helpers ---
            const parseHidden = () => (hidden.value ? hidden.value.split(',').map(t => t.trim()).filter(Boolean) : []);
            const saveHidden  = (arr) => hidden.value = arr.join(',');
            const uniqLower   = (arr) => Array.from(new Set(arr.map(t => t.toLowerCase())));
            const escapeHtml  = (s) => s.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
            const escapeAttr  = escapeHtml;

            const normalized = (s) => s.trim().toLowerCase();

            const addTags = (parts) => {
                const current = parseHidden();
                const merged = uniqLower([...current, ...parts.map(normalized)]).sort((a,b)=>a.localeCompare(b));
                saveHidden(merged);
                input.value = '';
                renderBadges();
                renderSuggestions(''); // refresh ponude
            };

            const removeTag = (t) => {
                const next = parseHidden().filter(x => normalized(x) !== normalized(t));
                saveHidden(next);
                renderBadges();
                renderSuggestions(input.value); // refresh
            };

            const renderBadges = () => {
                badges.innerHTML = '';
                parseHidden().forEach(tag => {
                    const badge = document.createElement('span');
                    badge.className = 'badge badge-pill badge-secondary mr-1 mb-1 d-inline-flex align-items-center';
                    badge.style.fontSize = '90%';
                    badge.innerHTML = `
                    <span class="pr-1">${escapeHtml(tag)}</span>
                    <button type="button" class="close ml-1" aria-label="Remove" data-tag="${escapeAttr(tag)}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                `;
                    badges.appendChild(badge);
                });
            };

            // Sugestije: prikaz postojećih tagova koje još nismo odabrali, filtrirano prema inputu
            const renderSuggestions = (filterText) => {
                const selected = parseHidden().map(normalized);
                const f = normalized(filterText || '');
                let list = ALL_TAGS
                    .map(normalized)
                    .filter(t => !selected.includes(t))
                    .filter(t => f ? t.includes(f) : true);

                // ograniči na 30 da ne zatrpamo
                list = Array.from(new Set(list)).slice(0, 30);

                if (!list.length) {
                    sugg.innerHTML = '';
                    return;
                }

                // male "btn" badge-ove koji se mogu kliknuti
                sugg.innerHTML = '';
                const wrap = document.createElement('div');
                list.forEach(t => {
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'btn btn-sm btn-outline-secondary mr-1 mb-1';
                    b.setAttribute('data-add', t);
                    b.textContent = t;
                    wrap.appendChild(b);
                });
                sugg.appendChild(wrap);
            };

            const addFromInput = () => {
                let val = input.value.trim().replace(/\s+/g, ' ');
                if (!val) return;
                // podrži paste "laravel, php, vue"
                const parts = val.split(',').map(s => s.trim()).filter(Boolean);
                addTags(parts);
            };

            // --- Init ---
            renderBadges();
            renderSuggestions(''); // prikaži sve dostupne tagove odmah

            // --- Events ---
            // Enter ili , potvrđuju tag
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    addFromInput();
                } else if (e.key === 'Backspace' && input.value === '') {
                    // Backspace na prazno briše zadnji tag
                    const arr = parseHidden();
                    if (arr.length) {
                        arr.pop();
                        saveHidden(arr);
                        renderBadges();
                        renderSuggestions(input.value);
                    }
                }
            });

            // Dok tipkaš, filtriraj sugestije
            input.addEventListener('input', function () {
                renderSuggestions(input.value);
            });

            // Blur potvrđuje ono što je upisano, osim ako biramo sugestiju
            input.addEventListener('blur', function () {
                if (suppressBlur) return;
                addFromInput();
            });

            // Klik na X uklanja tag
            badges.addEventListener('click', function (e) {
                const btn = e.target.closest('button[data-tag]');
                if (!btn) return;
                removeTag(btn.getAttribute('data-tag'));
            });

            // Odabir sugestije: koristimo mousedown da spriječimo blur i dodamo puni tag
            sugg.addEventListener('mousedown', function (e) {
                const btn = e.target.closest('button[data-add]');
                if (!btn) return;
                e.preventDefault();       // spriječi gubitak fokusa inputa
                suppressBlur = true;      // blokiraj blur-add dok ne završimo
                addTags([btn.getAttribute('data-add')]);
                // vrati fokus i makni guard nakon ciklusa event-loopa
                setTimeout(() => {
                    suppressBlur = false;
                    input.focus();
                }, 0);
            });
        });
    </script>

    <script>
        function SetSEOPreview() {
            let title = $('#name-input').val();
            $('#slug-input').val(slugify(title));
        }
    </script>

    @stack('product_scripts')

@endpush
