<div class="modal fade" id="payment-modal-corvus_wallets" tabindex="-1" role="dialog" aria-labelledby="modal-payment-corvus-wallets" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-popout" role="document">
        <div class="modal-content rounded">
            <div class="block block-themed block-transparent mb-0">
                <div class="block-header bg-primary">
                    <h3 class="block-title">{{ __('back/app.payments.corvus_wallets') }}</h3>
                    <div class="block-options">
                        <a class="text-muted font-size-h3" href="#" data-dismiss="modal" aria-label="Close">
                            <i class="fa fa-times"></i>
                        </a>
                    </div>
                </div>
                <div class="block-content">
                    <div class="alert alert-info">
                        Ova metoda koristi ShopID, SecretKey, CallbackURL i testni način iz osnovne CorvusPay metode.
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="corvus_wallets-title" class="w-100">{{ __('back/app.payments.input_title') }}</label>
                                <input type="text" class="form-control" id="corvus_wallets-title" name="corvus_wallets-title">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="corvus_wallets-min">{{ __('back/app.payments.min_order_amount') }}</label>
                                <input type="text" class="form-control" id="corvus_wallets-min" name="min">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label for="corvus_wallets-geo-zone">{{ __('back/app.payments.geo_zone') }} <span class="small text-gray">{{ __('back/app.payments.geo_zone_label') }}</span></label>
                            <select class="js-select2 form-control" id="corvus_wallets-geo-zone" name="corvus_wallets_geo_zone" style="width: 100%;" data-placeholder="{{ __('back/app.payments.select_geo') }}">
                                <option></option>
                                @foreach ($geo_zones as $geo_zone)
                                    <option value="{{ $geo_zone->id }}">{{ $geo_zone->title ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="corvus_wallets-price">{{ __('back/app.payments.fee_amount') }}</label>
                                <input type="text" class="form-control" id="corvus_wallets-price" name="data['price']">
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label for="corvus_wallets-short-description" class="w-100">{{ __('back/app.payments.short_desc') }} <span class="small text-gray">{{ __('back/app.payments.short_desc_label') }}</span></label>
                        <textarea id="corvus_wallets-short-description" class="form-control" name="data['short_description']"></textarea>
                        <small class="form-text text-muted">160 {{ __('back/app.payments.chars') }} max</small>
                    </div>

                    <div class="form-group mb-4">
                        <label for="corvus_wallets-description" class="w-100">{{ __('back/app.payments.long_desc') }} <span class="small text-gray">{{ __('back/app.payments.long_desc_label') }}</span></label>
                        <textarea id="corvus_wallets-description" class="form-control" rows="4" maxlength="160" name="data['description']"></textarea>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="corvus_wallets-sort-order">{{ __('back/app.payments.sort_order') }}</label>
                                <input type="text" class="form-control" id="corvus_wallets-sort-order" name="sort_order">
                            </div>
                        </div>
                        <div class="col-md-6 text-right" style="padding-top: 37px;">
                            <div class="form-group">
                                <label class="css-control css-control-sm css-control-success css-switch res">
                                    <input type="checkbox" class="css-control-input" id="corvus_wallets-status" name="status">
                                    <span class="css-control-indicator"></span> {{ __('back/app.payments.status_title') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="corvus_wallets-code" name="code" value="corvus_wallets">
                </div>
                <div class="block-content block-content-full text-right bg-light">
                    <a class="btn btn-sm btn-light" data-dismiss="modal" aria-label="Close">
                        {{ __('back/app.payments.cancel') }} <i class="fa fa-times ml-2"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-primary" onclick="event.preventDefault(); create_corvus_wallets();">
                        {{ __('back/app.payments.save') }} <i class="fa fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('payment-modal-js')
    <script>
        $(() => {
            $('#corvus_wallets-geo-zone').select2({
                minimumResultsForSearch: Infinity,
                allowClear: true
            });
        });

        function create_corvus_wallets() {
            let item = {
                title: $('#corvus_wallets-title').val(),
                code: $('#corvus_wallets-code').val(),
                min: $('#corvus_wallets-min').val(),
                data: {
                    price: $('#corvus_wallets-price').val(),
                    short_description: $('#corvus_wallets-short-description').val(),
                    description: $('#corvus_wallets-description').val(),
                    credential_source: 'corvus'
                },
                geo_zone: $('#corvus_wallets-geo-zone').val(),
                status: $('#corvus_wallets-status')[0].checked,
                sort_order: $('#corvus_wallets-sort-order').val()
            };

            item = collectPaymentLocaleFields('corvus_wallets', item);

            axios.post("{{ route('api.payment.store') }}", {data: item})
                .then(response => {
                    if (response.data.success) {
                        location.reload();
                    } else {
                        return errorToast.fire(response.data.message);
                    }
                }).catch(handleSettingsSaveError);
        }

        function edit_corvus_wallets(item) {
            const data = item.data || {};

            $('#corvus_wallets-min').val(item.min || '');
            $('#corvus_wallets-price').val(data.price || '');
            $('#corvus_wallets-geo-zone').val(item.geo_zone || '').trigger('change');
            $('#corvus_wallets-sort-order').val(item.sort_order || 0);
            $('#corvus_wallets-code').val(item.code || 'corvus_wallets');
            $('#corvus_wallets-title').val(item.title || '');
            $('#corvus_wallets-short-description').val(data.short_description || '');
            $('#corvus_wallets-description').val(data.description || '');
            $('#corvus_wallets-status')[0].checked = !!item.status;
        }
    </script>
@endpush
