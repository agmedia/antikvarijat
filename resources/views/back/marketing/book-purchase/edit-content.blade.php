@extends('back.layouts.backend')

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <div>
                    <h1 class="font-size-h2 font-w400 mt-2 mb-1">Tekstovi stranice Otkup knjiga</h1>
                    <div class="text-muted">Sadržaj hrvatske i engleske verzije javne stranice</div>
                </div>
                <a class="btn btn-alt-secondary mt-3 mt-sm-0" href="{{ route('book.purchases') }}">
                    <i class="fa fa-list mr-1"></i>Prijave za otkup
                </a>
            </div>
        </div>
    </div>

    <div class="content content-full content-boxed">
        @include('back.layouts.partials.session')

        <form method="POST" action="{{ route('book.purchases.content.update') }}">
            @csrf
            {{ method_field('PATCH') }}

            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Sadržaj stranice</h3>
                </div>
                <div class="block-content">
                    @include('back.layouts.partials.language-tabs', ['id' => 'book-purchase-content-tabs'])

                    <div class="tab-content">
                        @foreach (['hr' => 'Hrvatski', 'en' => 'English'] as $locale => $language)
                            <div class="tab-pane{{ $locale === 'hr' ? ' active' : '' }}" id="book-purchase-content-tabs-{{ $locale }}" role="tabpanel">
                                <div class="form-group">
                                    <label for="book-purchase-title-{{ $locale }}">Naslov ({{ $language }}) *</label>
                                    <input
                                        class="form-control @error($locale . '.title') is-invalid @enderror"
                                        id="book-purchase-title-{{ $locale }}"
                                        name="{{ $locale }}[title]"
                                        value="{{ old($locale . '.title', $content[$locale]['title']) }}"
                                        maxlength="120"
                                        required
                                    >
                                    @error($locale . '.title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="form-group">
                                    <label for="book-purchase-section-title-{{ $locale }}">Naslov uvodnog bloka *</label>
                                    <input
                                        class="form-control @error($locale . '.section_title') is-invalid @enderror"
                                        id="book-purchase-section-title-{{ $locale }}"
                                        name="{{ $locale }}[section_title]"
                                        value="{{ old($locale . '.section_title', $content[$locale]['section_title']) }}"
                                        maxlength="191"
                                        required
                                    >
                                    @error($locale . '.section_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="form-group">
                                    <label for="book-purchase-intro-1-{{ $locale }}">Prvi odlomak *</label>
                                    <textarea
                                        class="form-control @error($locale . '.intro_1') is-invalid @enderror"
                                        id="book-purchase-intro-1-{{ $locale }}"
                                        name="{{ $locale }}[intro_1]"
                                        rows="5"
                                        maxlength="5000"
                                        required
                                    >{{ old($locale . '.intro_1', $content[$locale]['intro_1']) }}</textarea>
                                    @error($locale . '.intro_1') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="form-group">
                                    <label for="book-purchase-intro-2-{{ $locale }}">Drugi odlomak *</label>
                                    <textarea
                                        class="form-control @error($locale . '.intro_2') is-invalid @enderror"
                                        id="book-purchase-intro-2-{{ $locale }}"
                                        name="{{ $locale }}[intro_2]"
                                        rows="5"
                                        maxlength="5000"
                                        required
                                    >{{ old($locale . '.intro_2', $content[$locale]['intro_2']) }}</textarea>
                                    @error($locale . '.intro_2') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="form-group">
                                    <label for="book-purchase-form-title-{{ $locale }}">Naslov obrasca *</label>
                                    <input
                                        class="form-control @error($locale . '.form_title') is-invalid @enderror"
                                        id="book-purchase-form-title-{{ $locale }}"
                                        name="{{ $locale }}[form_title]"
                                        value="{{ old($locale . '.form_title', $content[$locale]['form_title']) }}"
                                        maxlength="191"
                                        required
                                    >
                                    @error($locale . '.form_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="form-group">
                                    <label for="book-purchase-meta-description-{{ $locale }}">Meta opis *</label>
                                    <textarea
                                        class="form-control @error($locale . '.meta_description') is-invalid @enderror"
                                        id="book-purchase-meta-description-{{ $locale }}"
                                        name="{{ $locale }}[meta_description]"
                                        rows="3"
                                        maxlength="255"
                                        required
                                    >{{ old($locale . '.meta_description', $content[$locale]['meta_description']) }}</textarea>
                                    @error($locale . '.meta_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="block-content bg-body-light">
                    <button class="btn btn-hero-success mb-3" type="submit">
                        <i class="fa fa-save mr-1"></i>Spremi tekstove
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
