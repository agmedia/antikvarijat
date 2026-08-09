@if ($paginator->hasPages())

    <div class="admin-pagination mt-4">
        <nav aria-label="Navigacija po stranicama">
        <ul class="pagination mb-0">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span class="page-link" aria-hidden="true"><i class="fa-regular fa-arrow-left mr-sm-2" aria-hidden="true"></i><span class="d-none d-sm-inline">Prethodna</span></span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')"><i class="fa-regular fa-arrow-left mr-sm-2" aria-hidden="true"></i><span class="d-none d-sm-inline">Prethodna</span></a>
                </li>
            @endif
            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="page-item disabled admin-page-number" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active admin-page-number" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                        @else
                            <li class="page-item admin-page-number"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach
            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')"><span class="d-none d-sm-inline">Sljedeća</span><i class="fa-regular fa-arrow-right ml-sm-2" aria-hidden="true"></i></a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span class="page-link" aria-hidden="true"><span class="d-none d-sm-inline">Sljedeća</span><i class="fa-regular fa-arrow-right ml-sm-2" aria-hidden="true"></i></span>
                </li>
            @endif
        </ul>
        </nav>

        <p class="admin-pagination-summary mb-0">
            Prikazano <strong>{{ number_format($paginator->firstItem(), 0, ',', '.') }}–{{ number_format($paginator->lastItem(), 0, ',', '.') }}</strong>
            od <strong>{{ number_format($paginator->total(), 0, ',', '.') }}</strong> rezultata
        </p>
    </div>
@endif
