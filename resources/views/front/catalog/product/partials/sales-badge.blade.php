@php
    $salesBadgeProduct = $product ?? $prod ?? null;
    $salesBadgeType = $salesBadgeType
        ?? ($salesBadgeProduct
            ? app(\App\Services\ProductRecommendationService::class)->salesBadgeType((int) $salesBadgeProduct->id)
            : null);
    $salesBadgeLabel = $salesBadgeType ? __('front.sales_badges.' . $salesBadgeType) : null;
    $salesBadgeIcon = [
        'bestseller' => 'fa-tag',
        'popular' => 'fa-fire-flame-curved',
    ][$salesBadgeType] ?? null;
@endphp

@if ($salesBadgeType && $salesBadgeLabel)
    <span
        class="product-sales-badge product-sales-badge--{{ $salesBadgeType }}"
        role="img"
        aria-label="{{ $salesBadgeLabel }}"
        data-tooltip="{{ $salesBadgeLabel }}"
        tabindex="0">
        <i class="fa-duotone {{ $salesBadgeIcon }}" aria-hidden="true"></i>
    </span>
@endif
