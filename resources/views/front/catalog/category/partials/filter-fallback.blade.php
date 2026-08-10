<aside class="col-lg-3">
    <div class="offcanvas offcanvas-collapse bg-white w-100 rounded-3 shadow-lg py-1 catalog-shop-sidebar" id="shop-sidebar">
        <div class="offcanvas-cap align-items-center shadow-sm">
            <h2 class="h5 mb-0">{{ __('front.js.filter.filter') }}</h2>
            <button class="btn-close ms-auto" type="button" data-bs-dismiss="offcanvas" aria-label="{{ __('front.js.filter.filter') }}"></button>
        </div>
        <div class="offcanvas-body py-grid-gutter px-lg-grid-gutter">
            @if (! empty($initialCategories))
                <div class="widget widget-categories mb-3 pb-4">
                    @if (! $cat && ! $subcat)
                        <h3 class="widget-title">{{ __('front.js.filter.categories') }}</h3>
                    @elseif ($cat && ! $subcat)
                        <h3 class="widget-title">{{ $cat->title }}<span class="badge bg-secondary float-end">{{ number_format((int) ($cat->count ?? 0), 0, ',', '.') }}</span></h3>
                    @elseif ($cat && $subcat)
                        <h3 class="widget-title">{{ $subcat->title }}<span class="badge bg-secondary float-end">{{ number_format((int) ($subcat->count ?? 0), 0, ',', '.') }}</span></h3>
                    @endif

                    <div class="accordion mt-n1 catalog-category-list">
                        @foreach ($initialCategories as $categoryItem)
                            <h3 class="accordion-header">
                                <a href="{{ $categoryItem['url'] }}" class="accordion-button py-2 border-bottom none collapsed" role="link">
                                    {{ $categoryItem['title'] }}
                                    <span class="badge bg-secondary ms-2 position-absolute end-0">
                                        {{ number_format((int) $categoryItem['count'], 0, ',', '.') }}
                                    </span>
                                </a>
                            </h3>
                        @endforeach
                    </div>

                    @if ($cat || $subcat)
                        @php
                            if ($subcat) {
                                $parentCategoryUrl = $author
                                    ? \App\Helpers\LocaleHelper::route('catalog.route.author', ['author' => $author, 'cat' => $cat])
                                    : ($publisher
                                        ? \App\Helpers\LocaleHelper::route('catalog.route.publisher', ['publisher' => $publisher, 'cat' => $cat])
                                        : \App\Helpers\LocaleHelper::route('catalog.route', ['group' => $group, 'cat' => $cat]));
                            } else {
                                $parentCategoryUrl = $author
                                    ? \App\Helpers\LocaleHelper::route('catalog.route.author', ['author' => $author])
                                    : ($publisher
                                        ? \App\Helpers\LocaleHelper::route('catalog.route.publisher', ['publisher' => $publisher])
                                        : \App\Helpers\LocaleHelper::route('catalog.route', ['group' => $group]));
                            }
                        @endphp
                        <a class="btn btn-sm btn-outline-primary mt-3" href="{{ $parentCategoryUrl }}">
                            <i class="fa-solid fa-arrow-left fs-xs me-1" aria-hidden="true"></i> {{ __('front.js.filter.back') }}
                        </a>
                    @endif
                </div>
            @endif

            <div class="widget mb-3 pb-4">
                <h3 class="widget-title">{{ __('front.js.filter.publication_year') }}</h3>
                <div class="d-flex pb-1">
                    <div class="w-50 pe-2 me-2">
                        <div class="input-group input-group-sm">
                            <input class="form-control" value="{{ request('start') }}" placeholder="{{ __('front.js.filter.from') }}" type="text">
                            <span class="input-group-text">{{ __('front.js.filter.year_short') }}</span>
                        </div>
                    </div>
                    <div class="w-50 ps-2">
                        <div class="input-group input-group-sm">
                            <input class="form-control" value="{{ request('end') }}" placeholder="{{ __('front.js.filter.to') }}" type="text">
                            <span class="input-group-text">{{ __('front.js.filter.year_short') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @unless ($author)
                <div class="widget widget-filter mb-3 pb-4">
                    <h3 class="widget-title">{{ __('front.js.filter.authors') }}<span class="spinner-border spinner-border-sm float-end"></span></h3>
                    <div class="input-group input-group-sm mb-2 autocomplete">
                        <input type="search" class="form-control rounded-end pe-5" placeholder="{{ __('front.js.filter.search_author') }}">
                        <i class="fa-solid fa-magnifying-glass position-absolute top-50 end-0 translate-middle-y fs-sm me-3" aria-hidden="true"></i>
                    </div>
                    <ul class="widget-list widget-filter-list list-unstyled pt-1 catalog-filter-options" aria-hidden="true"></ul>
                </div>
            @endunless

            @unless ($publisher)
                <div class="widget widget-filter mb-3 pb-4">
                    <h3 class="widget-title">{{ __('front.js.filter.publishers') }}<span class="spinner-border spinner-border-sm float-end"></span></h3>
                    <div class="input-group input-group-sm mb-2 autocomplete">
                        <input type="search" class="form-control rounded-end pe-5" placeholder="{{ __('front.js.filter.search_publisher') }}">
                        <i class="fa-solid fa-magnifying-glass position-absolute top-50 end-0 translate-middle-y fs-sm me-3" aria-hidden="true"></i>
                    </div>
                    <ul class="widget-list widget-filter-list list-unstyled pt-1 catalog-filter-options" aria-hidden="true"></ul>
                </div>
            @endunless

            <a class="btn btn-primary mt-4" href="{{ url()->current() }}"><i class="fa-solid fa-trash-can" aria-hidden="true"></i> {{ __('front.js.filter.clear_all') }}</a>
        </div>
    </div>
</aside>
