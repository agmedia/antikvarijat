@extends('back.layouts.backend')

@push('css_after')
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('vendor/lightgallery/css/lightgallery-bundle.min.css') }}">
@endpush

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">Detalj prijave: {{ $purchase->submission_id }}</h1>
                <div class="d-flex align-items-center">
                    <form method="post"
                          action="{{ route('book.purchases.destroy', ['purchase' => $purchase->id]) }}"
                          class="mr-2"
                          onsubmit="return confirm('Jeste li sigurni da želite obrisati prijavu i sve fotografije?');">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="redirect_to" value="{{ route('book.purchases') }}">
                        <button type="submit" class="btn btn-danger">Obriši prijavu</button>
                    </form>
                    @if($previous)
                        <a href="{{ route('book.purchases.show', ['purchase' => $previous->id]) }}" class="btn btn-alt-info mr-2">Prethodna</a>
                    @endif
                    @if($next)
                        <a href="{{ route('book.purchases.show', ['purchase' => $next->id]) }}" class="btn btn-alt-info mr-2">Sljedeća</a>
                    @endif
                    <a href="{{ route('book.purchases') }}" class="btn btn-alt-secondary">Natrag na listu</a>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="block block-rounded mb-3">
            <div class="block-content p-4">
                <div><strong>Vrijeme:</strong> {{ optional($purchase->submitted_at)->format('d.m.Y H:i') ?: '-' }}</div>
                <div><strong>Ime i prezime:</strong> {{ $purchase->full_name }}</div>
                <div><strong>Poštanski broj:</strong> {{ $purchase->postal_code }}</div>
                <div><strong>Email:</strong> <a href="mailto:{{ $purchase->email }}">{{ $purchase->email }}</a></div>
                <div><strong>Mobitel:</strong> {{ $purchase->phone }}</div>
                <div><strong>Broj fotografija:</strong> {{ collect($purchase->photos ?? [])->count() }}</div>
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Fotografije</h3>
            </div>
            <div class="block-content p-4">
                @php($photos = collect($purchase->photos ?? []))
                @if($photos->isNotEmpty())
                    <div id="purchase-gallery" class="d-flex flex-wrap">
                        @foreach($photos as $photo)
                            <a href="{{ $photo['url'] ?? '#' }}" class="gallery-item d-inline-block mr-2 mb-2" data-sub-html="{{ $photo['name'] ?? '' }}">
                                <img src="{{ $photo['url'] ?? '' }}" alt="{{ $photo['name'] ?? '' }}" style="width:140px; height:140px; object-fit:cover; border-radius:8px; border:1px solid #e2e2e2;">
                            </a>
                        @endforeach
                    </div>
                @else
                    <span class="text-muted">Nema fotografija za ovu prijavu.</span>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('js_after')
    <script src="{{ \App\Helpers\Asset::url('vendor/lightgallery/lightgallery.min.js') }}"></script>
    <script>
        (function () {
            const gallery = document.getElementById('purchase-gallery');
            if (!gallery || typeof lightGallery !== 'function') {
                return;
            }

            lightGallery(gallery, {
                selector: '.gallery-item',
                download: false,
                speed: 400,
            });
        })();
    </script>
@endpush
