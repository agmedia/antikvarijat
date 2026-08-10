@extends('front.layouts.app')

@section('content')
    @foreach ($orders as $order)
        <div class="modal fade" id="order-details{{ $order->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('front.account.order_number_with_id', ['id' => $order->id]) }}</h5>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="{{ __('front.auth.close') }}"></button>
                    </div>
                    <div class="modal-body pb-0">
                        @foreach ($order->products as $orderProduct)
                            @php
                                $productUrl = optional($orderProduct->real)->url ? url($orderProduct->real->url) : null;
                                $productImage = optional($orderProduct->real)->thumb ?: optional($orderProduct->product)->image;
                            @endphp
                            <div class="d-sm-flex justify-content-between mb-4 pb-3 border-bottom">
                                <div class="d-sm-flex text-center text-sm-start">
                                    @if($productUrl)<a class="d-inline-block flex-shrink-0 mx-auto" href="{{ $productUrl }}" style="width: 7rem;">@endif
                                        <img src="{{ $productImage ?: asset('media/avatars/avatar0.jpg') }}" alt="{{ $orderProduct->name }}">
                                    @if($productUrl)</a>@endif
                                    <div class="ps-sm-4 pt-2">
                                        <h3 class="product-title fs-base mb-2">
                                            @if($productUrl)<a href="{{ $productUrl }}">{{ $orderProduct->name }}</a>@else{{ $orderProduct->name }}@endif
                                        </h3>
                                        <div class="fs-lg text-accent pt-2">{{ number_format($orderProduct->price, 2, ',', '.') }} €</div>
                                    </div>
                                </div>
                                <div class="pt-2 ps-sm-3 text-center"><span class="text-muted d-block mb-2">{{ __('front.general.quantity') }}:</span>{{ $orderProduct->quantity }}</div>
                                <div class="pt-2 ps-sm-3 text-center"><span class="text-muted d-block mb-2">{{ __('front.general.total') }}</span>{{ number_format($orderProduct->total, 2, ',', '.') }} €</div>
                            </div>
                        @endforeach
                    </div>
                    <div class="modal-footer flex-wrap justify-content-between bg-secondary fs-md">
                        @foreach ($order->totals as $total)
                            @php($totalKey = 'front.email.total_' . $total->code)
                            <div class="px-2 py-1"><span class="text-muted">{{ trans($totalKey) !== $totalKey ? trans($totalKey) : $total->title }}:&nbsp;</span><strong>{{ number_format($total->value, 2, ',', '.') }} €</strong></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @include('front.customer.layouts.header')

    <section class="account-page container pb-5 mb-2 mb-md-4">
        <div class="row g-4">
            @include('front.customer.layouts.sidebar')

            <section class="col-lg-8 col-xl-9">
                <div class="account-card account-content-card">
                    <div class="account-content-header">
                        <div class="account-content-heading">
                            <span class="account-content-icon"><i class="fa-regular fa-bag-shopping" aria-hidden="true"></i></span>
                            <div>
                                <h2 class="account-content-title">{{ __('front.account.orders') }}</h2>
                                <p class="account-content-subtitle">{{ __('front.account.order_history_hint') }}</p>
                            </div>
                        </div>
                    </div>

                    @if($orders->count())
                        <div class="account-table-wrap table-responsive fs-md mb-4">
                            <table class="table table-hover align-middle">
                                <thead>
                                <tr>
                                    <th>{{ __('front.account.order_number') }} #</th>
                                    <th>{{ __('front.account.date') }}</th>
                                    <th>{{ __('front.account.status') }}</th>
                                    <th>{{ __('front.general.total') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($orders as $order)
                                    <tr>
                                        <td class="py-3"><a class="fw-medium" href="#order-details{{ $order->id }}" data-bs-toggle="modal">#{{ $order->id }}</a></td>
                                        <td class="py-3">{{ \Illuminate\Support\Carbon::make($order->created_at)->format('d.m.Y') }}</td>
                                        <td class="py-3"><span class="badge bg-info m-0">{{ \App\Helpers\LocaleHelper::orderStatusTitle($order->status) }}</span></td>
                                        <td class="py-3">{{ number_format($order->total, 2, ',', '.') }} €</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $orders->links() }}
                    @else
                        <div class="account-empty">
                            <div><i class="fa-regular fa-bag-shopping d-block fs-3 mb-3" aria-hidden="true"></i>{{ __('front.account.no_orders') }}</div>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </section>
@endsection
