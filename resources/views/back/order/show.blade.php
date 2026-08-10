@extends('back.layouts.backend')

@push('css_before')
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('js/plugins/select2/css/select2.min.css') }}">
    <!-- Page JS Plugins CSS -->
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('js/plugins/magnific-popup/magnific-popup.css') }}">
@endpush

@section('content')
    <div class="admin-page-hero">
        <div class="content content-full">
            <div class="admin-page-heading">
                <div>
                    <div class="admin-page-kicker"><i class="fa-duotone fa-receipt"></i> Narudžbe</div>
                    <h1 class="admin-page-title">Narudžba #{{ $order->id }}</h1>
                    <div class="admin-inline-meta mt-2">
                        <span><i class="fa-regular fa-calendar"></i> {{ \Illuminate\Support\Carbon::make($order->created_at)->format('d.m.Y. H:i') }}</span>
                        <span class="badge badge-pill badge-{{ $order->status->color }}">{{ $order->status->title }}</span>
                    </div>
                </div>
                <div class="admin-page-actions">
                    <a class="btn btn-secondary" href="{{ route('orders') }}"><i class="fa-duotone fa-arrow-left mr-1"></i> Sve narudžbe</a>
                    <a class="btn btn-primary" href="{{ route('orders.edit', ['order' => $order]) }}"><i class="fa-duotone fa-pen-to-square mr-1"></i> Uredi</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="content admin-order-detail">
    @include('back.layouts.partials.session')
        <!-- Products -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Artikli</h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-borderless table-striped table-vcenter admin-order-products">
                        <thead>
                        <tr>
                            <th class="text-center" style="width: 100px;">Slika</th>
                            <th>Naziv</th>
                            <th>Polica</th>
                            <th class="text-center">Kol</th>
                            <th class="text-right" style="width: 10%;">Cijena</th>
                            <th class="text-right" style="width: 10%;">Ukupno</th>
                        </tr>
                        </thead>
                        <tbody class="js-gallery">
                        @foreach ($order->products as $product)
                            <tr class="admin-order-product-row">




                                <td class="text-center" data-label="Slika"> <a class="img-link img-link-zoom-in img-lightbox" href="{{ \App\Support\AdminImage::url(optional($product->product)->image) }}">
                                        <img src="{{ \App\Support\AdminImage::url(optional($product->product)->thumb) }}" height="80px" alt="{{ $product->name }}" decoding="async"/>
                                    </a>
                                </td>



                                <td data-label="Naziv"><strong>{{ $product->name }} -  {{ $product->product->sku }}</strong></td>
                                <td data-label="Polica">{{ $product->product->polica }}</td>
                                <td class="text-center" data-label="Količina"><strong>{{ $product->quantity }}</strong></td>
                                <td class="text-right" data-label="Cijena">{{ number_format($product->price, 2, ',', '.') }}</td>
                                <td class="text-right" data-label="Ukupno">{{ number_format($product->total, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach

                        @foreach ($order->totals as $total)
                            <tr class="admin-order-total-row">
                                <td colspan="5" class="text-right"><strong>{{ $total->title }}:</strong></td>
                                <td class="text-right">{{ number_format($total->value, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- END Products -->

        <!-- Customer -->
        <div class="row">
            <div class="col-sm-6 d-flex">
                <!-- Billing Address -->
                <div class="block block-rounded flex-fill">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Adresa dostave</h3>
                    </div>
                    <div class="block-content">
                        <address class="admin-order-address">
                            <strong>{{ $order->shipping_fname }} {{ $order->shipping_lname }}</strong>
                            <span>{{ $order->shipping_address }}</span>
                            <span>{{ $order->shipping_zip }} {{ $order->shipping_city }}</span>
                            <span>{{ $order->shipping_state }}</span>
                            @if($order->company || $order->oib)
                                <span class="admin-order-company">{{ $order->company }}{{ $order->company && $order->oib ? ' · ' : '' }}{{ $order->oib }}</span>
                            @endif
                            <span class="admin-order-contact"><i class="fa-duotone fa-phone"></i> {{ $order->shipping_phone }}</span>
                            <a class="admin-order-contact" href="mailto:{{ $order->shipping_email }}"><i class="fa-duotone fa-envelope"></i> {{ $order->shipping_email }}</a>
                        </address>
                    </div>
                </div>
                <!-- END Billing Address -->
            </div>
            <div class="col-sm-6 d-flex">
                <!-- Shipping Address -->
                <div class="block block-rounded flex-fill">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Napomene</h3>
                    </div>
                    <div class="block-content">
                        @if($order->napomena || $order->comment)
                            @if($order->napomena)<p>{{ $order->napomena }}</p>@endif
                            @if($order->comment)<p>{{ $order->comment }}</p>@endif
                        @else
                            <p class="text-muted mb-0">Nema napomena uz narudžbu.</p>
                        @endif
                    </div>
                </div>
                <!-- END Shipping Address -->
            </div>
        </div>
        <!-- END Customer -->

        <!-- Log Messages -->
        <div class="block block-rounded">
            <div class="block-header block-header-default admin-order-history-header">
                <h3 class="block-title">Povijest narudžbe</h3>
                <div class="admin-order-history-actions">
                    <button type="button" class="btn btn-secondary" id="btn-add-comment">
                        <i class="fa-duotone fa-message-plus mr-1"></i> Dodaj komentar
                    </button>
                    <div class="dropdown">
                        <button type="button" class="btn btn-secondary" id="dropdown-ecom-filters" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fa-duotone fa-arrows-rotate mr-1"></i> Promijeni status
                            <i class="fa fa-angle-down ml-1"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown-ecom-filters">
                            @foreach ($statuses as $status)
                                <a class="dropdown-item d-flex align-items-center justify-content-between" href="javascript:setStatus({{ $status->id }});">
                                    <span class="badge badge-pill badge-{{ $status->color }}">{{ $status->title }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="block-content">
                <table class="table table-borderless table-striped table-vcenter admin-order-history-table">
                    <tbody id="order-history-list">
                    @foreach ($order->history as $record)
                        @include('back.order.partials.history-row')
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <!-- END Log Messages -->
    </div>
    <!-- END Page Content -->

@endsection

@push('css_after')
    <style>
        .admin-order-products img { width: 3.4rem; height: 4.6rem; border: 1px solid #ded8cc; border-radius: .3rem; object-fit: cover; box-shadow: none; }
        .admin-order-detail address { margin-bottom: 0; color: #4f5c55; font-style: normal; line-height: 1.75; }
        .admin-order-address { display: flex; flex-direction: column; gap: .12rem; }
        .admin-order-address strong { margin-bottom: .2rem; color: var(--admin-ink); font-size: 1.05rem; }
        .admin-order-address .admin-order-company { margin-top: .45rem; }
        .admin-order-address .admin-order-contact { display: inline-flex; gap: .45rem; align-items: center; margin-top: .38rem; }
        .admin-order-history-actions { display: flex; gap: .5rem; align-items: center; margin-left: auto; }
        .admin-order-history-table { table-layout: fixed; }
        .admin-order-history-table td:nth-child(1) { width: 12%; }
        .admin-order-history-table td:nth-child(2) { width: 27%; }
        .admin-order-history-table td:nth-child(3) { width: 22%; }
        @media (max-width: 575.98px) {
            .admin-order-products, .admin-order-products tbody, .admin-order-products tr, .admin-order-products td { display: block; width: 100%; }
            .admin-order-products thead { display: none; }
            .admin-order-products .admin-order-product-row { display: grid; grid-template-columns: 4.2rem repeat(2, minmax(0, 1fr)); gap: .55rem .7rem; padding: .8rem; border-bottom: 1px solid var(--admin-line); }
            .admin-order-products .admin-order-product-row td { display: flex; min-width: 0; padding: 0 !important; border: 0 !important; flex-direction: column; align-items: flex-start; background: transparent !important; text-align: left !important; }
            .admin-order-products .admin-order-product-row td::before { margin-bottom: .12rem; color: #7b877f; content: attr(data-label); font-size: var(--admin-type-xs); font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
            .admin-order-products .admin-order-product-row td:first-child { grid-row: 1; grid-column: 1; }
            .admin-order-products .admin-order-product-row td:first-child::before { display: none; }
            .admin-order-products .admin-order-product-row td:nth-child(2) { grid-row: 1; grid-column: 2 / -1; }
            .admin-order-products .admin-order-product-row td:nth-child(n+3) { grid-column: span 1; }
            .admin-order-products .admin-order-total-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .75rem; padding: .55rem .8rem; border-bottom: 1px solid #ebe7df; }
            .admin-order-products .admin-order-total-row td { padding: 0 !important; border: 0 !important; background: transparent !important; }
            .admin-order-products .admin-order-total-row td:first-child { text-align: left !important; }
            .admin-order-products .admin-order-total-row td:last-child { text-align: right !important; }
            .admin-order-history-header { align-items: stretch !important; flex-direction: column; gap: .75rem; }
            .admin-order-history-actions { display: grid; width: 100%; margin-left: 0; grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .admin-order-history-actions .btn, .admin-order-history-actions .dropdown { width: 100%; }
            .admin-order-history-table, .admin-order-history-table tbody, .admin-order-history-table tr, .admin-order-history-table td { display: block; width: 100% !important; }
            .admin-order-history-table tr { padding: .75rem 0; border-bottom: 1px solid var(--admin-line); }
            .admin-order-history-table td { display: grid; grid-template-columns: 6.2rem minmax(0, 1fr); gap: .55rem; padding: .28rem 0 !important; border: 0 !important; background: transparent !important; }
            .admin-order-history-table td::before { color: #46534c; content: attr(data-label); font-size: var(--admin-type-xs); font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
        }
    </style>
@endpush

@push('modals')
    <div class="modal fade" id="comment-modal" tabindex="-1" role="dialog" aria-labelledby="comment--modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-popout" role="document">
            <div class="modal-content rounded">
                <div class="block block-themed block-transparent mb-0">
                    <div class="block-header bg-primary">
                        <h3 class="block-title">Dodaj komentar</h3>
                        <div class="block-options">
                            <a class="text-muted font-size-h3" href="#" data-dismiss="modal" aria-label="Close">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>
                    <div class="block-content">
                        <div class="row justify-content-center mb-3">
                            <div class="col-md-12">
                                <div class="form-group mb-4">
                                    <label for="status-select">Promjeni status</label>
                                    <select class="js-select2 form-control" id="status-select" name="status" style="width: 100%;" data-placeholder="Promjeni status narudžbe">
                                        <option value="0">Bez Promjene statusa...</option>
                                        @foreach ($statuses as $status)
                                            <option value="{{ $status->id }}">{{ $status->title }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="comment-input">Komentar</label>
                                    <textarea class="form-control" name="comment" id="comment-input" rows="7"></textarea>
                                </div>

                                <input type="hidden" name="order_id" value="{{ $order->id }}">
                            </div>
                        </div>
                    </div>
                    <div class="block-content block-content-full text-right bg-light">
                        <a class="btn btn-sm btn-light" data-dismiss="modal" aria-label="Close">
                            Odustani <i class="fa fa-times ml-2"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-primary" onclick="event.preventDefault(); changeStatus();">
                            Snimi <i class="fa fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('js_after')

    <!-- Page JS Plugins -->
    <script src="{{ \App\Helpers\Asset::url('js/plugins/magnific-popup/jquery.magnific-popup.min.js') }}"></script>

    <!-- Page JS Helpers (Magnific Popup Plugin) -->
    <script>jQuery(function(){Dashmix.helpers('magnific-popup');});</script>

    <script src="{{ \App\Helpers\Asset::url('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(() => {
            $('#status-select').select2({});

            $('#btn-add-comment').on('click', () => {
                $('#comment-modal').modal('show');
                $('#status-select').val(0);
                $('#status-select').trigger('change');
            });
        });

        /**
         *
         * @param status
         */
        function setStatus(status) {
            $('#comment-modal').modal('show');
            $('#status-select').val(status);
            $('#status-select').trigger('change');
        }

        /**
         *
         */
        function changeStatus() {
            let item = {
                order_id: {{ $order->id }},
                comment: $('#comment-input').val(),
                status: $('#status-select').val()
            };

            axios.post("{{ route('api.order.status.change') }}", item)
            .then(response => {
                console.log(response.data)
                if (response.data.message) {
                    $('#comment-modal').modal('hide');
                    $('#comment-input').val('');

                    if (response.data.history_html) {
                        $('#order-history-list').prepend(response.data.history_html);
                    }

                    successToast.fire({
                        timer: 1500,
                        text: response.data.message,
                    })

                } else {
                    return errorToast.fire(response.data.error);
                }
            });
        }
    </script>

@endpush
