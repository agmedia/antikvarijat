@extends('back.layouts.backend')

@push('css_before')
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
@endpush

@section('content')

    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">Načini plaćanja</h1>
            </div>
        </div>
    </div>

    <div class="content content-full">
        @include('back.layouts.partials.session')

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">Lista</h3>
            </div>
            <div class="block-content">
                <table class="table table-striped table-borderless table-vcenter">
                    <thead class="thead-light">
                    <tr>
                        <th>Naziv</th>
                        <th style="width: 10%;">Code</th>
                        <th class="text-center" style="width: 15%;">Poredak</th>
                        <th class="text-center" style="width: 15%;">Status</th>
                        <th style="width: 10%;" class="text-right">Uredi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>
                                {{ $payment->title }}
                                @if (! empty($payment->title_en))
                                    <div class="small text-muted">EN: {{ $payment->title_en }}</div>
                                @endif
                            </td>
                            <td class="small">{{ $payment->code }}</td>
                            <td class="text-center">{{ $payment->sort_order }}</td>
                            <td class="text-center">
                                @include('back.layouts.partials.status', ['status' => $payment->status])
                            </td>
                            <td class="text-right font-size-sm">
                                <button type="button" class="btn btn-sm btn-alt-secondary js-payment-edit" data-type="{{ $payment->code }}" data-item="{{ base64_encode(json_encode($payment)) }}">
                                    <i class="fa fa-fw fa-pencil-alt"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr class="text-center">
                            <td colspan="4">Nema načina plaćanja...</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <!-- Pop Out Block Modal -->
    @foreach($payments as $payment)
        @include('back.settings.app.payment.modals.' . $payment->code)
    @endforeach
@endpush

@push('js_after')
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>

    <script>
        $(document).on('click', '.js-payment-edit', function (event) {
            event.preventDefault();

            const item = JSON.parse(atob($(this).attr('data-item')));

            edit(item, $(this).attr('data-type'));
        });

        /**
         *
         * @param item
         * @param type
         */
        function edit(item, type) {
            $('#payment-modal-' + type).modal('show');
            ensurePaymentLocaleFields(type);
            fillPaymentBaseFields(type, item);
            // Call to individual edit function.
            // As. edit_flat (item) {}
            try {
                if (typeof window["edit_" + type] === 'function') {
                    window["edit_" + type](item);
                }
            } catch (error) {
                console.error('Payment modal edit failed:', type, error);
            }
            fillPaymentLocaleFields(type, item);
        }

        function ensurePaymentLocaleFields(type) {
            addLocaleInput(type + '-title', 'Naziv (EN)');
            addLocaleTextarea(type + '-short-description', 'Kratki opis (EN)', 2, 500);
            addLocaleTextarea(type + '-description', 'Detaljni opis (EN)', 4, null);
        }

        function collectPaymentLocaleFields(type, item) {
            item.data = item.data || {};
            item.title_en = emptyToNull($('#' + type + '-title-en').val());
            item.data.short_description_en = emptyToNull($('#' + type + '-short-description-en').val());
            item.data.description_en = emptyToNull($('#' + type + '-description-en').val());

            return item;
        }

        function fillPaymentLocaleFields(type, item) {
            $('#' + type + '-title-en').val(item.title_en || '');
            $('#' + type + '-short-description-en').val(item.data && item.data.short_description_en ? item.data.short_description_en : '');
            $('#' + type + '-description-en').val(item.data && item.data.description_en ? item.data.description_en : '');
        }

        function fillPaymentBaseFields(type, item) {
            const data = item.data || {};

            $('#' + type + '-title').val(item.title || '');
            $('#' + type + '-min').val(item.min || '');
            $('#' + type + '-price').val(data.price || '');
            $('#' + type + '-short-description').val(data.short_description || '');
            $('#' + type + '-description').val(data.description || '');
            $('#' + type + '-sort-order').val(item.sort_order || 0);
            $('#' + type + '-code').val(item.code || type);

            if ($('#' + type + '-geo-zone').length) {
                $('#' + type + '-geo-zone').val(item.geo_zone || '');
                $('#' + type + '-geo-zone').trigger('change');
            }

            if ($('#' + type + '-status').length) {
                $('#' + type + '-status')[0].checked = !!item.status;
            }
        }

        function addLocaleInput(sourceId, label) {
            const $source = $('#' + sourceId);
            const targetId = sourceId + '-en';

            if (!$source.length || $('#' + targetId).length) {
                return;
            }

            $source.closest('.form-group').after(
                '<div class="form-group">' +
                    '<label for="' + targetId + '">' + label + '</label>' +
                    '<input type="text" class="form-control" id="' + targetId + '">' +
                '</div>'
            );
        }

        function addLocaleTextarea(sourceId, label, rows, maxlength) {
            const $source = $('#' + sourceId);
            const targetId = sourceId + '-en';

            if (!$source.length || $('#' + targetId).length) {
                return;
            }

            const max = maxlength ? ' maxlength="' + maxlength + '"' : '';

            $source.closest('.form-group').after(
                '<div class="form-group mb-4">' +
                    '<label for="' + targetId + '">' + label + '</label>' +
                    '<textarea class="form-control" id="' + targetId + '" rows="' + rows + '"' + max + '></textarea>' +
                '</div>'
            );
        }

        function emptyToNull(value) {
            value = value || '';

            return value.trim() === '' ? null : value;
        }

        function handleSettingsSaveError(error) {
            let message = 'Server error! Pokušajte ponovo ili kontaktirajte administratora!';

            if (error.response && error.response.data && error.response.data.message) {
                message = error.response.data.message;
            }

            return errorToast.fire(message);
        }
    </script>

    @stack('payment-modal-js')
@endpush
