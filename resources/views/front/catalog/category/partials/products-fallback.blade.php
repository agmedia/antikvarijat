<section class="col-lg-9">
    @if ($initialProductsPaginator)
        <div class="d-flex justify-content-center justify-content-sm-between align-items-center pt-2 pb-4 pb-sm-5">
            <div class="d-flex flex-wrap">
                <div class="dropdown me-2 d-sm-none">
                    <a class="btn btn-primary dropdown-toggle collapsed" href="#shop-sidebar" data-bs-toggle="collapse" aria-expanded="false"><i class="ci-filter-alt"></i></a>
                </div>
                <div class="d-flex align-items-center flex-nowrap me-3 me-sm-4 pb-3">
                    <select class="form-select" aria-label="Sortiranje" disabled>
                        <option value="">Sortiraj</option>
                        @foreach (config('settings.sorting_list') as $item)
                            <option value="{{ $item['value'] }}" @selected(request()->get('sort') == $item['value'])>{{ $item['title'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="d-flex pb-3">
                <span class="fs-sm text-light btn btn-primary btn-sm text-nowrap ms-2 d-none d-sm-block">Ukupno {{ number_format($initialProductsPaginator->total(), 0, ',', '.') }} artikala</span>
            </div>
        </div>

        @if ($initialProductsPaginator->count())
            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-4 mb-3 px-2">
                @foreach ($initialProductsPaginator as $product)
                    <div class="col px-2 mb-4">
                        @include('front.catalog.category.product')
                    </div>
                @endforeach
            </div>

            {{ $initialProductsPaginator->onEachSide(1)->links('vendor.pagination.bootstrap-4') }}
        @else
            <div class="col-md-12 px-2 mb-4">
                @if (Route::currentRouteName() == 'pretrazi')
                    <h2>Nema rezultata pretrage</h2>
                    <p>Vaša pretraga za <mark>"{{ request()->input('pojam') }}"</mark> pronašla je 0 rezultata.</p>
                @elseif (Route::currentRouteName() == 'catalog.route.actions')
                    <h2>Trenutno nema artikala na sniženju</h2>
                    <p>Navratite nek drugi put :-)</p>
                @else
                    <h2>Trenutno nema proizvoda</h2>
                    <p>Pogledajte u nekoj drugoj kategoriji ili probajte sa tražilicom :-)</p>
                @endif
            </div>
        @endif
    @endif
</section>
