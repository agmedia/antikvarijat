@extends('back.layouts.backend')

@section('content')
    <div class="admin-page-hero">
        <div class="content content-full">
            <div class="admin-page-heading">
                <div>
                    <div class="admin-page-kicker"><i class="fa-duotone fa-books"></i> Katalog</div>
                    <h1 class="admin-page-title">Kategorije</h1>
                    <p class="admin-page-description">Organizirajte knjige po područjima i podkategorijama kako bi katalog ostao uredan i lako pretraživ.</p>
                </div>
                <div class="admin-page-actions">
                    <a class="btn btn-primary" href="{{ route('category.create') }}">
                        <i class="fa-duotone fa-folder-plus mr-1"></i> Nova kategorija
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="content content-full">
        @include('back.layouts.partials.session')

        @forelse($categoriess as $group => $categories)
            <section class="block block-rounded mb-4">
                <div class="block-header block-header-default">
                    <div class="d-flex align-items-center min-width-0">
                        <span class="admin-section-icon mr-3"><i class="fa-duotone fa-book-open-cover"></i></span>
                        <div class="min-width-0">
                            <h2 class="block-title mb-1">{{ $group }}</h2>
                            <div class="admin-inline-meta">
                                <span><i class="fa-regular fa-folder-tree"></i> {{ $categories->count() }} kategorija</span>
                                <span><i class="fa-regular fa-books"></i> {{ $categories->sum('products_count') }} artikala</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="category-library-list">
                    @foreach($categories as $category)
                        @php($subcategories = $category->subcategories()->get())
                        <article class="category-library-row">
                            <a class="category-library-main" href="{{ route('category.edit', ['category' => $category]) }}">
                                <span class="category-library-mark"><i class="fa-duotone fa-folder-bookmark"></i></span>
                                <span class="category-library-copy">
                                    <strong>{{ $category->title }}</strong>
                                    <small>
                                        {{ $category->products_count }} artikala
                                        @if($subcategories->isNotEmpty())
                                            · {{ $subcategories->count() }} podkategorija
                                        @endif
                                    </small>
                                </span>
                            </a>

                            <div class="category-library-subs" aria-label="Podkategorije">
                                @forelse($subcategories as $subcategory)
                                    <a href="{{ route('category.edit', ['category' => $subcategory]) }}">
                                        {{ $subcategory->title }}
                                    </a>
                                @empty
                                    <span class="category-library-empty">Nema podkategorija</span>
                                @endforelse
                            </div>

                            <a href="{{ route('category.edit', ['category' => $category]) }}"
                               class="btn btn-sm btn-alt-secondary category-library-action"
                               data-toggle="tooltip" title="Uredi kategoriju" aria-label="Uredi {{ $category->title }}">
                                <i class="fa-duotone fa-pen-to-square"></i>
                            </a>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="block block-rounded">
                <div class="admin-empty-state">
                    <i class="fa-duotone fa-folder-open"></i>
                    <h3>Katalog još nema kategorija</h3>
                    <p class="text-muted mb-3">Dodajte prvu grupu i kategoriju kako biste počeli slagati katalog.</p>
                    <a href="{{ route('category.create') }}" class="btn btn-primary"><i class="fa fa-plus mr-1"></i> Nova kategorija</a>
                </div>
            </div>
        @endforelse
    </div>
@endsection

@push('css_after')
    <style>
        .category-library-list { overflow: hidden; border-radius: 0 0 var(--admin-radius) var(--admin-radius); }
        .category-library-row { display: grid; grid-template-columns: minmax(15rem, .85fr) minmax(18rem, 1.3fr) auto; gap: 1rem; align-items: center; min-height: 5rem; padding: .8rem 1.1rem; border-bottom: 1px solid #ebe7df; transition: background .15s ease; }
        .category-library-row:last-child { border-bottom: 0; }
        .category-library-row:hover { background: #f5f8f6; }
        .category-library-main { display: flex; min-width: 0; gap: .8rem; align-items: center; color: var(--admin-ink); }
        .category-library-main:hover { color: var(--admin-forest); text-decoration: none; }
        .category-library-mark { display: inline-flex; width: 2.35rem; height: 2.35rem; flex: 0 0 2.35rem; align-items: center; justify-content: center; border-radius: .55rem; color: var(--admin-brass); background: #f4ecdf; font-size: 1rem; --fa-primary-color: var(--admin-forest); --fa-secondary-color: var(--admin-brass); --fa-secondary-opacity: .75; }
        .category-library-copy { min-width: 0; }
        .category-library-copy strong { display: block; overflow: hidden; color: #2d4035; font-size: var(--admin-type-body); font-weight: 750; text-overflow: ellipsis; white-space: nowrap; }
        .category-library-copy small { display: block; margin-top: .2rem; color: var(--admin-muted); font-size: var(--admin-type-xs); }
        .category-library-subs { display: flex; min-width: 0; flex-wrap: wrap; gap: .35rem; }
        .category-library-subs a { display: inline-flex; max-width: 15rem; align-items: center; padding: .28rem .55rem; border: 1px solid #dedbd2; border-radius: 99rem; overflow: hidden; color: #536159; background: #fff; font-size: var(--admin-type-xs); font-weight: 650; text-overflow: ellipsis; white-space: nowrap; }
        .category-library-subs a:hover { border-color: #9db0a5; color: var(--admin-forest); background: #edf3ef; text-decoration: none; }
        .category-library-empty { color: #99a29c; font-size: var(--admin-type-xs); font-style: italic; }
        @media (max-width: 991.98px) { .category-library-row { grid-template-columns: minmax(13rem, .8fr) minmax(12rem, 1.2fr) auto; } }
        @media (max-width: 767.98px) { .category-library-row { grid-template-columns: minmax(0, 1fr) auto; gap: .65rem; align-items: start; padding: .85rem; } .category-library-subs { grid-column: 1 / -1; padding-left: 3.15rem; } .category-library-action { grid-row: 1; grid-column: 2; } }
    </style>
@endpush
