@extends('back.layouts.backend')

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">ViaLibri naslov</h1>
                <div class="d-flex flex-column flex-sm-row align-items-sm-center">
                    <a href="{{ route('vialibri.config') }}" class="btn btn-primary my-2">
                        <i class="si si-settings mr-1"></i> Config
                    </a>
                    <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('vialibri.index') }}">ViaLibri</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Uredi prijevod</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        @include('back.layouts.partials.session')

        <div class="block block-rounded mb-4">
            <div class="block-header block-header-default">
                <a class="btn btn-light" href="{{ route('vialibri.index') }}">
                    <i class="fa fa-arrow-left mr-1"></i> Povratak na listu
                </a>
                <div class="block-options">
                    @if ($translationIsOutdated)
                        <span class="badge badge-warning mr-2">Artikl je mijenjan nakon prijevoda</span>
                    @endif

                    @if ($product && (! $product->status || $product->quantity <= 0 || $product->price <= 0))
                        <span class="badge badge-danger mr-2">Ne ide u feed dok nije prodajan</span>
                    @endif

                    @if ($product)
                        <a href="{{ route('products.edit', ['product' => $product]) }}" class="btn btn-sm btn-alt-secondary">
                            Otvori artikl
                        </a>
                    @endif
                </div>
            </div>
            <div class="block-content">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Izvorni naslov</label>
                            <input type="text" class="form-control" value="{{ optional($product)->name }}" readonly>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-4">
                                <label>Autor</label>
                                <input type="text" class="form-control" value="{{ optional(optional($product)->author)->title }}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label>Šifra</label>
                                <input type="text" class="form-control" value="{{ optional($product)->sku }}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label>Cijena</label>
                                <input type="text" class="form-control" value="{{ $product ? number_format((float) $product->price, 2, ',', '.') . ' EUR' : '' }}" readonly>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label>Izvorni opis za prijevod / fallback export</label>
                            <textarea class="form-control" rows="18" readonly>{{ $sourceDescription }}</textarea>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <form method="post" action="{{ route('vialibri.update', ['vialibriBook' => $vialibriBook]) }}">
                            @csrf
                            @method('PATCH')

                            <div class="form-group">
                                <label for="translated_title">Translated title</label>
                                <input id="translated_title" type="text" name="translated_title" class="form-control" value="{{ old('translated_title', $vialibriBook->translated_title) }}" placeholder="English title">
                            </div>

                            <div class="form-group">
                                <label for="translated_description">Translated description</label>
                                <textarea id="translated_description" name="translated_description" rows="18" class="form-control" placeholder="English description">{{ old('translated_description', $vialibriBook->translated_description) }}</textarea>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label for="edition">Edition</label>
                                    <input id="edition" type="text" name="edition" class="form-control" value="{{ old('edition', $vialibriBook->edition) }}" placeholder="First edition">
                                </div>
                                <div class="col-md-6">
                                    <label for="keywords">Keywords</label>
                                    <input id="keywords" type="text" name="keywords" class="form-control" value="{{ old('keywords', $vialibriBook->keywords) }}" placeholder="rare, philosophy, signed">
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-4">
                                    <label for="first_edition">First edition</label>
                                    <select id="first_edition" name="first_edition" class="form-control">
                                        <option value="">—</option>
                                        <option value="1" {{ (string) old('first_edition', $vialibriBook->getRawOriginal('first_edition')) === '1' ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ (string) old('first_edition', $vialibriBook->getRawOriginal('first_edition')) === '0' ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="signed">Signed</label>
                                    <select id="signed" name="signed" class="form-control">
                                        <option value="">—</option>
                                        <option value="1" {{ (string) old('signed', $vialibriBook->getRawOriginal('signed')) === '1' ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ (string) old('signed', $vialibriBook->getRawOriginal('signed')) === '0' ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="dust_jacket">Dust jacket</label>
                                    <select id="dust_jacket" name="dust_jacket" class="form-control">
                                        <option value="">—</option>
                                        <option value="1" {{ (string) old('dust_jacket', $vialibriBook->getRawOriginal('dust_jacket')) === '1' ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ (string) old('dust_jacket', $vialibriBook->getRawOriginal('dust_jacket')) === '0' ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <button type="submit" class="btn btn-success mr-2">
                                    Snimi
                                </button>
                            </div>
                        </form>

                        <form method="post" action="{{ route('vialibri.translate', ['vialibriBook' => $vialibriBook]) }}" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-info">
                                Google Translate HR -> EN
                            </button>
                        </form>

                        <form method="post" action="{{ route('vialibri.destroy', ['vialibriBook' => $vialibriBook]) }}" class="mt-3">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                Makni iz ViaLibri liste
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
