@extends('back.layouts.backend')

@section('content')

    <div class="admin-page-hero">
        <div class="content content-full">
            <div class="admin-page-heading">
                <div>
                    <div class="admin-page-kicker"><i class="fa-duotone fa-newspaper" aria-hidden="true"></i> Marketing</div>
                    <h1 class="admin-page-title">Blog</h1>
                    <p class="admin-page-description">Uredite članke, naslovne fotografije i datume objave.</p>
                </div>
                <div class="admin-page-actions">
                    <a class="btn btn-primary" href="{{ route('blogs.create') }}">
                        <i class="fa-duotone fa-plus mr-1" aria-hidden="true"></i> Novi post
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="content">
    @include('back.layouts.partials.session')


        <!-- Posts -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <div class="d-flex align-items-center min-width-0">
                    <span class="admin-section-icon mr-3"><i class="fa-duotone fa-memo-pad" aria-hidden="true"></i></span>
                    <div>
                        <h2 class="block-title mb-1">Objave</h2>
                        <span class="admin-count">{{ number_format($blogs->total(), 0, ',', '.') }} objava</span>
                    </div>
                </div>
                <form action="{{ route('blogs') }}" method="GET" class="admin-toolbar-group admin-directory-search">
                    <div class="admin-search">
                        <i class="fa-regular fa-magnifying-glass" aria-hidden="true"></i>
                        <input type="search" class="form-control" id="search-input" name="search" placeholder="Naslov objave" value="{{ request()->query('search') }}">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fa-duotone fa-magnifying-glass mr-1" aria-hidden="true"></i> Pretraži</button>
                    @if(request()->filled('search'))
                        <a href="{{ route('blogs') }}" class="btn btn-secondary"><i class="fa-regular fa-xmark mr-1" aria-hidden="true"></i> Očisti</a>
                    @endif
                </form>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                <table class="table table-striped table-borderless table-vcenter admin-data-table admin-blog-table">
                    <thead>
                    <tr>
                        <th>Slika</th>
                        <th>Naziv</th>
                        <th>Kreirano</th>
                        <th>Objavljeno</th>
                        <th class="text-right">Radnje</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($blogs as $blog)
                        <tr>
                            <td data-label="Slika">
                                <a href="{{ route('blogs.edit', ['blog' => $blog]) }}">
                                    <img class="admin-blog-thumb" src="{{ \App\Support\AdminImage::url($blog->image) }}" alt="{{ $blog->title }}" loading="lazy"/>
                                </a>
                            </td>
                            <td data-label="Naziv">
                                <a class="font-w600" href="{{ route('blogs.edit', ['blog' => $blog]) }}">{{ $blog->title }}</a>
                            </td>
                            <td data-label="Kreirano">
                                {{ \Illuminate\Support\Carbon::make($blog->created_at)->format('d.m.Y.') }}
                            </td>
                            <td data-label="Objavljeno">
                                {{ isset($blog->publish_date) ? \Illuminate\Support\Carbon::make($blog->publish_date)->format('d.m.Y. H:i') : '—' }}
                            </td>
                            <td class="text-right" data-label="Radnje">
                                <span class="admin-row-actions">
                                    <a class="btn btn-sm btn-alt-secondary" href="{{ route('blogs.edit', ['blog' => $blog]) }}" title="Uredi objavu" aria-label="Uredi {{ $blog->title }}">
                                        <i class="fa-duotone fa-pen-to-square" aria-hidden="true"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-alt-danger" onclick="event.preventDefault(); deleteItem({{ $blog->id }}, '{{ route('blogs.destroy.api') }}');" title="Obriši objavu" aria-label="Obriši {{ $blog->title }}"><i class="fa-duotone fa-trash-can" aria-hidden="true"></i></button>
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center text-muted py-5" colspan="5">Nema objava.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
                </div>
                {{ $blogs->links() }}
            </div>
        </div>
        <!-- END Posts -->
    </div>
    <!-- END Page Content -->

@endsection

@push('css_after')
    <style>
        .admin-blog-table th:first-child, .admin-blog-table td:first-child { width: 7.5rem; }
        .admin-blog-thumb { width: 5.7rem; height: 4rem; border: 1px solid #d8d2c8; border-radius: .2rem; object-fit: cover; }
        .admin-blog-table .admin-row-actions { display: inline-flex; gap: .35rem; }
        @media (max-width: 767.98px) {
            .admin-blog-table th:first-child, .admin-blog-table td:first-child { width: 100%; }
            .admin-blog-table .admin-row-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); width: 100%; }
        }
    </style>
@endpush

@push('js_after')

@endpush
