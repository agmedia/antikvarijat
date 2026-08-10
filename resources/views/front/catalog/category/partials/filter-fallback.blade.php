@php
    $selectedAuthors = array_filter(explode('+', (string) request('autor')));
    $selectedPublishers = array_filter(explode('+', (string) request('nakladnik')));
    $activeFilterCount = count($selectedAuthors)
        + count($selectedPublishers)
        + (request()->filled('start') ? 1 : 0)
        + (request()->filled('end') ? 1 : 0)
        + (request()->filled('pismo') ? 1 : 0)
        + (request()->filled('stanje') ? 1 : 0)
        + (request()->filled('uvez') ? 1 : 0);
    $attributeValueLabels = __('front.js.filter.attribute_values');
    $clearFilterQuery = array_filter([
        'sort' => request('sort'),
        'pojam' => request('pojam'),
    ]);
    $clearFilterUrl = url()->current().($clearFilterQuery ? '?'.http_build_query($clearFilterQuery) : '');
@endphp

<aside class="col-lg-3 catalog-filter-column">
    <div class="offcanvas offcanvas-collapse bg-white w-100 catalog-shop-sidebar catalog-filter-panel" id="shop-sidebar">
        <div class="offcanvas-cap catalog-filter-header align-items-center">
            <div class="d-flex align-items-center gap-2">
                <h2 class="mb-0"><i class="fa-solid fa-filter" aria-hidden="true"></i><span>{{ __('front.js.filter.filter') }}</span></h2>
                @if ($activeFilterCount)
                    <span class="catalog-filter-active-count">{{ $activeFilterCount }}</span>
                @endif
            </div>
            <button class="catalog-filter-close ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#shop-sidebar" aria-label="{{ __('front.js.filter.close') }}">
                <i class="fa-regular fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        <div class="offcanvas-body catalog-filter-body py-grid-gutter px-lg-grid-gutter">
            @if ($activeFilterCount)
                <div class="catalog-filter-desktop-summary d-none d-lg-flex">
                    <span class="catalog-filter-desktop-summary__label">{{ __('front.js.filter.filters') }} <span class="catalog-filter-active-count">{{ $activeFilterCount }}</span></span>
                    <a class="catalog-filter-desktop-summary__clear" href="{{ $clearFilterUrl }}"><i class="fa-solid fa-trash-can" aria-hidden="true"></i> {{ __('front.js.filter.clear_all') }}</a>
                </div>
            @endif
            @if (! empty($initialCategories))
                <div class="widget widget-categories catalog-filter-section">
                    @if (! $cat && ! $subcat)
                        <h3 class="widget-title"><i class="fa-regular fa-books" aria-hidden="true"></i>{{ __('front.js.filter.categories') }}</h3>
                    @elseif ($cat && ! $subcat)
                        <div class="catalog-filter-current">
                            <h3 class="widget-title mb-0"><i class="fa-regular fa-books" aria-hidden="true"></i>{{ $cat->title }}</h3>
                            <span class="catalog-filter-result-count">{{ number_format((int) ($cat->count ?? 0), 0, ',', '.') }}</span>
                        </div>
                    @elseif ($cat && $subcat)
                        <div class="catalog-filter-current">
                            <h3 class="widget-title mb-0"><i class="fa-regular fa-books" aria-hidden="true"></i>{{ $subcat->title }}</h3>
                            <span class="catalog-filter-result-count">{{ number_format((int) ($subcat->count ?? 0), 0, ',', '.') }}</span>
                        </div>
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
                        <a class="catalog-filter-back mt-3" href="{{ $parentCategoryUrl }}">
                            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> {{ __('front.js.filter.back') }}
                        </a>
                    @endif
                </div>
            @endif

            <div class="widget catalog-filter-section">
                <h3 class="widget-title"><i class="fa-regular fa-calendar-range" aria-hidden="true"></i>{{ __('front.js.filter.publication_year') }}</h3>
                <div class="catalog-filter-year-grid">
                    <div>
                        <div class="input-group">
                            <input class="form-control" value="{{ request('start') }}" placeholder="{{ __('front.js.filter.from') }}" type="text" inputmode="numeric" maxlength="4" aria-label="{{ __('front.js.filter.from_year') }}">
                            <span class="input-group-text">{{ __('front.js.filter.year_short') }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <input class="form-control" value="{{ request('end') }}" placeholder="{{ __('front.js.filter.to') }}" type="text" inputmode="numeric" maxlength="4" aria-label="{{ __('front.js.filter.to_year') }}">
                            <span class="input-group-text">{{ __('front.js.filter.year_short') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if (collect($initialAttributes ?? [])->flatten(1)->isNotEmpty())
                <div class="widget catalog-filter-section">
                    <h3 class="widget-title"><i class="fa-regular fa-book-open-cover" aria-hidden="true"></i>{{ __('front.js.filter.details') }}</h3>
                    <div class="catalog-attribute-filters">
                        @if (! empty($initialAttributes['letter']))
                            <label class="catalog-attribute-row">
                                <span class="catalog-attribute-label">{{ __('front.js.filter.letter') }}</span>
                                <select class="form-select" aria-label="{{ __('front.js.filter.letter') }}">
                                    <option value="">{{ __('front.js.filter.all_letters') }}</option>
                                    @foreach ($initialAttributes['letter'] as $option)
                                        <option value="{{ $option['value'] }}" {{ request('pismo') === $option['value'] ? 'selected' : '' }}>{{ data_get($attributeValueLabels, 'letter.'.$option['value'], $option['value']) }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif
                        @if (! empty($initialAttributes['condition']))
                            <label class="catalog-attribute-row">
                                <span class="catalog-attribute-label">{{ __('front.js.filter.condition') }}</span>
                                <select class="form-select" aria-label="{{ __('front.js.filter.condition') }}">
                                    <option value="">{{ __('front.js.filter.all_conditions') }}</option>
                                    @foreach ($initialAttributes['condition'] as $option)
                                        <option value="{{ $option['value'] }}" {{ request('stanje') === $option['value'] ? 'selected' : '' }}>{{ data_get($attributeValueLabels, 'condition.'.$option['value'], $option['value']) }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif
                        @if (! empty($initialAttributes['binding']))
                            <label class="catalog-attribute-row">
                                <span class="catalog-attribute-label">{{ __('front.js.filter.binding') }}</span>
                                <select class="form-select" aria-label="{{ __('front.js.filter.binding') }}">
                                    <option value="">{{ __('front.js.filter.all_bindings') }}</option>
                                    @foreach ($initialAttributes['binding'] as $option)
                                        <option value="{{ $option['value'] }}" {{ request('uvez') === $option['value'] ? 'selected' : '' }}>{{ data_get($attributeValueLabels, 'binding.'.$option['value'], $option['value']) }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif
                    </div>
                </div>
            @endif

            @unless ($author)
                <div class="widget widget-filter catalog-filter-section">
                    <div class="catalog-filter-section-title">
                        <h3 class="widget-title mb-0"><i class="fa-regular fa-user-pen" aria-hidden="true"></i>{{ __('front.js.filter.authors') }}</h3>
                        @if (count($selectedAuthors))
                            <span class="catalog-filter-selected-count">{{ count($selectedAuthors) }}</span>
                        @endif
                        <span class="spinner-border spinner-border-sm ms-auto"></span>
                    </div>
                    <div class="input-group mb-2 autocomplete catalog-filter-search">
                        <input type="search" class="form-control rounded-end pe-5" placeholder="{{ __('front.js.filter.search_author') }}" aria-label="{{ __('front.js.filter.search_author') }}">
                        <i class="fa-solid fa-magnifying-glass position-absolute top-50 end-0 translate-middle-y fs-sm me-3" aria-hidden="true"></i>
                    </div>
                    <ul class="widget-list widget-filter-list list-unstyled pt-1 catalog-filter-options" aria-hidden="true"></ul>
                </div>
            @endunless

            @unless ($publisher)
                <div class="widget widget-filter catalog-filter-section">
                    <div class="catalog-filter-section-title">
                        <h3 class="widget-title mb-0"><i class="fa-regular fa-building" aria-hidden="true"></i>{{ __('front.js.filter.publishers') }}</h3>
                        @if (count($selectedPublishers))
                            <span class="catalog-filter-selected-count">{{ count($selectedPublishers) }}</span>
                        @endif
                        <span class="spinner-border spinner-border-sm ms-auto"></span>
                    </div>
                    <div class="input-group mb-2 autocomplete catalog-filter-search">
                        <input type="search" class="form-control rounded-end pe-5" placeholder="{{ __('front.js.filter.search_publisher') }}" aria-label="{{ __('front.js.filter.search_publisher') }}">
                        <i class="fa-solid fa-magnifying-glass position-absolute top-50 end-0 translate-middle-y fs-sm me-3" aria-hidden="true"></i>
                    </div>
                    <ul class="widget-list widget-filter-list list-unstyled pt-1 catalog-filter-options" aria-hidden="true"></ul>
                </div>
            @endunless

            @if ($activeFilterCount)
                <a class="catalog-filter-clear-desktop d-none d-lg-inline-flex" href="{{ $clearFilterUrl }}"><i class="fa-solid fa-trash-can" aria-hidden="true"></i> {{ __('front.js.filter.clear_all') }}</a>
            @endif
        </div>
        <div class="catalog-filter-actions d-lg-none">
            <a class="catalog-filter-clear{{ $activeFilterCount ? '' : ' is-disabled' }}" href="{{ $activeFilterCount ? $clearFilterUrl : '#' }}" @if (! $activeFilterCount) aria-disabled="true" tabindex="-1" @endif>{{ __('front.js.filter.clear') }}</a>
            <button type="button" class="btn btn-primary catalog-filter-apply" data-bs-toggle="collapse" data-bs-target="#shop-sidebar">
                {{ __('front.js.filter.show_results') }}
            </button>
        </div>
    </div>
</aside>
