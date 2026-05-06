<aside class="col-lg-3">
    <div class="offcanvas offcanvas-collapse bg-white w-100 rounded-3 shadow-lg py-1" id="shop-sidebar" style="max-width: 22rem;">
        <div class="offcanvas-body py-grid-gutter px-lg-grid-gutter">
            @if (! empty($initialCategories))
                <div class="widget widget-categories mb-3 pb-4">
                    @if (! $cat && ! $subcat)
                        <h3 class="widget-title">Kategorije</h3>
                    @elseif ($cat && ! $subcat)
                        <h3 class="widget-title">{{ $cat->title }}<span class="badge bg-secondary float-end">{{ number_format((int) ($cat->count ?? 0), 0, ',', '.') }}</span></h3>
                    @elseif ($cat && $subcat)
                        <h3 class="widget-title">{{ $subcat->title }}<span class="badge bg-secondary float-end">{{ number_format((int) ($subcat->count ?? 0), 0, ',', '.') }}</span></h3>
                    @endif

                    <div class="accordion mt-n1">
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
                </div>
            @endif
        </div>
    </div>
</aside>
