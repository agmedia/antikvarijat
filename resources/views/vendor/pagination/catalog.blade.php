@if ($paginator->hasPages())
    <nav class="catalog-pagination" aria-label="{{ __('front.js.pagination.navigation') }}">
        <ul class="pagination justify-content-center mb-0">
            <li class="page-item pagination-prev-nav {{ $paginator->onFirstPage() ? 'disabled' : '' }}"
                @if ($paginator->onFirstPage()) aria-disabled="true" @endif>
                @if ($paginator->onFirstPage())
                    <span class="page-link">
                        <i class="fa-regular fa-arrow-left" aria-hidden="true"></i>
                        <span class="d-none d-sm-inline">{{ __('front.js.pagination.previous') }}</span>
                    </span>
                @else
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                        <i class="fa-regular fa-arrow-left" aria-hidden="true"></i>
                        <span class="d-none d-sm-inline">{{ __('front.js.pagination.previous') }}</span>
                    </a>
                @endif
            </li>

            <li class="page-item pagination-mobile-summary disabled d-sm-none" aria-current="page">
                <span class="page-link">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item pagination-page-nav disabled d-none d-sm-block" aria-disabled="true">
                        <span class="page-link">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li class="page-item pagination-page-nav d-none d-sm-block {{ $page === $paginator->currentPage() ? 'active' : '' }}"
                            @if ($page === $paginator->currentPage()) aria-current="page" @endif>
                            @if ($page === $paginator->currentPage())
                                <span class="page-link">{{ number_format($page, 0, ',', '.') }}</span>
                            @else
                                <a class="page-link" href="{{ $url }}">{{ number_format($page, 0, ',', '.') }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li class="page-item pagination-next-nav {{ $paginator->hasMorePages() ? '' : 'disabled' }}"
                @unless ($paginator->hasMorePages()) aria-disabled="true" @endunless>
                @if ($paginator->hasMorePages())
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">
                        <span class="d-none d-sm-inline">{{ __('front.js.pagination.next') }}</span>
                        <i class="fa-regular fa-arrow-right" aria-hidden="true"></i>
                    </a>
                @else
                    <span class="page-link">
                        <span class="d-none d-sm-inline">{{ __('front.js.pagination.next') }}</span>
                        <i class="fa-regular fa-arrow-right" aria-hidden="true"></i>
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif
