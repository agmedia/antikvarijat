@if ($paginator->total())
    <div class="blog-pagination-wrap">
        {{ $paginator->onEachSide(1)->links('vendor.pagination.catalog') }}

        <p class="blog-pagination-summary mb-0" aria-live="polite">
            {{ __('front.blog.pagination_summary', [
                'from' => number_format($paginator->firstItem(), 0, ',', '.'),
                'to' => number_format($paginator->lastItem(), 0, ',', '.'),
                'total' => number_format($paginator->total(), 0, ',', '.'),
            ]) }}
        </p>
    </div>
@endif
