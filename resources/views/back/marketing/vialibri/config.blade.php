@extends('back.layouts.backend')

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">ViaLibri Config</h1>
                <a class="btn btn-light" href="{{ route('vialibri.index') }}">
                    <i class="fa fa-arrow-left mr-1"></i> Povratak
                </a>
            </div>
        </div>
    </div>

    <div class="content">
        @include('back.layouts.partials.session')

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">XML feedovi</h3>
            </div>
            <div class="block-content">
                <div class="alert alert-info">
                    Aktivno odabranih i trenutno prodajnih naslova za export: <strong>{{ $exportableCount }}</strong>
                </div>

                <div class="form-group">
                    <label for="vialibri-sync-url">Sync XML</label>
                    <input id="vialibri-sync-url" type="text" class="form-control" value="{{ $syncUrl }}" readonly>
                </div>

                <div class="form-group">
                    <label for="vialibri-data-url">Data XML</label>
                    <input id="vialibri-data-url" type="text" class="form-control" value="{{ $dataUrl }}" readonly>
                </div>

                @if ($accessCode)
                    <p class="text-muted small mb-0">
                        Feed je zaštićen access codeom preko query stringa ili `Authorization` headera.
                    </p>
                @endif
            </div>
        </div>
    </div>
@endsection
