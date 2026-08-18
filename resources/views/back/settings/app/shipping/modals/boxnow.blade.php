<div class="modal fade" id="shipment-modal-boxnow" tabindex="-1" role="dialog" aria-labelledby="modal-shipment-boxnow" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-popout" role="document">
        <div class="modal-content rounded">
            <div class="block block-themed block-transparent mb-0">
                <div class="block-header bg-primary">
                    <h3 class="block-title">Box Now paketomati</h3>
                    <div class="block-options">
                        <a class="text-muted font-size-h3" href="#" data-dismiss="modal" aria-label="Close"><i class="fa fa-times"></i></a>
                    </div>
                </div>
                <div class="block-content">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="boxnow-title">Naslov</label>
                                <input type="text" class="form-control" id="boxnow-title">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="boxnow-price">Trošak isporuke</label>
                                <input type="text" class="form-control" id="boxnow-price">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label for="boxnow-geo-zone">Geo zona</label>
                            <select class="js-select2 form-control" id="boxnow-geo-zone" style="width: 100%;" data-placeholder="Odaberite geo zonu">
                                <option></option>
                                @foreach ($geo_zones as $geo_zone)
                                    <option value="{{ $geo_zone->id }}">{{ $geo_zone->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label for="boxnow-time">Trajanje isporuke</label>
                        <input type="text" class="form-control" id="boxnow-time">
                    </div>

                    <div class="form-group mb-4">
                        <label for="boxnow-short-description">Kratki opis</label>
                        <textarea class="form-control" id="boxnow-short-description" rows="2" maxlength="500"></textarea>
                    </div>

                    <div class="form-group mb-4">
                        <label for="boxnow-description">Detaljni opis</label>
                        <textarea class="form-control" id="boxnow-description" rows="4"></textarea>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="boxnow-sort-order">Poredak</label>
                                <input type="number" min="0" class="form-control" id="boxnow-sort-order">
                            </div>
                        </div>
                        <div class="col-md-6 text-right" style="padding-top: 37px;">
                            <label class="css-control css-control-sm css-control-success css-switch res">
                                <input type="checkbox" class="css-control-input" id="boxnow-status">
                                <span class="css-control-indicator"></span> Status načina dostave
                            </label>
                        </div>
                    </div>

                    <input type="hidden" id="boxnow-code" value="boxnow">

                    <hr class="my-4">

                    <form id="boxnow-api-settings-form" method="POST" action="{{ route('boxnow-settings.update') }}" autocomplete="off">
                        @csrf
                        {{ method_field('PATCH') }}

                        <h4 class="font-size-h5 mb-1"><i class="fa fa-lock mr-1"></i>Box Now API postavke</h4>
                        <p class="text-muted mb-4">Podaci se primjenjuju pri slanju narudžbe iz admina. Client Secret sprema se šifrirano i nakon spremanja se više ne prikazuje.</p>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="boxnow-client-id">Client ID</label>
                                    <input class="form-control @error('client_id') is-invalid @enderror" id="boxnow-client-id" name="client_id" type="text" value="{{ old('client_id', $boxNowSettings['client_id']) }}" maxlength="500" autocomplete="off">
                                    @error('client_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="boxnow-client-secret">Client Secret</label>
                                    <input class="form-control @error('client_secret') is-invalid @enderror" id="boxnow-client-secret" name="client_secret" type="password" value="" maxlength="1000" autocomplete="new-password" placeholder="{{ $boxNowSettings['has_client_secret'] ? 'Spremljen — ostavite prazno ako ga ne mijenjate' : 'Upišite Client Secret' }}">
                                    @error('client_secret') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    @if($boxNowSettings['has_client_secret'])
                                        <small class="form-text text-success"><i class="fa fa-lock mr-1"></i>Client Secret je spremljen šifrirano.</small>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="boxnow-warehouse-location-id">ID polaznog skladišta</label>
                                    <input class="form-control @error('warehouse_location_id') is-invalid @enderror" id="boxnow-warehouse-location-id" name="warehouse_location_id" type="text" value="{{ old('warehouse_location_id', $boxNowSettings['warehouse_location_id']) }}" maxlength="191">
                                    @error('warehouse_location_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="boxnow-api-partner-id">API Partner ID <span class="text-muted">(opcionalno)</span></label>
                                    <input class="form-control @error('api_partner_id') is-invalid @enderror" id="boxnow-api-partner-id" name="api_partner_id" type="text" value="{{ old('api_partner_id', $boxNowSettings['api_partner_id']) }}" maxlength="191">
                                    @error('api_partner_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="boxnow-widget-partner-id">Widget Partner ID</label>
                                    <input class="form-control @error('widget_partner_id') is-invalid @enderror" id="boxnow-widget-partner-id" name="widget_partner_id" type="number" min="1" value="{{ old('widget_partner_id', $boxNowSettings['widget_partner_id']) }}">
                                    @error('widget_partner_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="boxnow-origin-name">Naziv pošiljatelja</label>
                                    <input class="form-control @error('origin_name') is-invalid @enderror" id="boxnow-origin-name" name="origin_name" type="text" value="{{ old('origin_name', $boxNowSettings['origin_name']) }}" maxlength="191">
                                    @error('origin_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="boxnow-origin-email">E-mail pošiljatelja</label>
                                    <input class="form-control @error('origin_email') is-invalid @enderror" id="boxnow-origin-email" name="origin_email" type="email" value="{{ old('origin_email', $boxNowSettings['origin_email']) }}" maxlength="191">
                                    @error('origin_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="boxnow-origin-phone">Telefon pošiljatelja</label>
                                    <input class="form-control @error('origin_phone') is-invalid @enderror" id="boxnow-origin-phone" name="origin_phone" type="text" value="{{ old('origin_phone', $boxNowSettings['origin_phone']) }}" maxlength="50" placeholder="+385...">
                                    @error('origin_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="boxnow-allow-return">Dopusti povrat</label>
                                    <select class="form-control @error('allow_return') is-invalid @enderror" id="boxnow-allow-return" name="allow_return">
                                        <option value="1" @if((string) old('allow_return', (int) $boxNowSettings['allow_return']) === '1') selected @endif>Da</option>
                                        <option value="0" @if((string) old('allow_return', (int) $boxNowSettings['allow_return']) === '0') selected @endif>Ne</option>
                                    </select>
                                    @error('allow_return') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <button class="btn btn-alt-success mb-4" type="submit"><i class="fa fa-save mr-1"></i>Spremi Box Now API postavke</button>
                    </form>
                </div>
                <div class="block-content block-content-full text-right bg-light">
                    <a class="btn btn-sm btn-light" data-dismiss="modal">Odustani <i class="fa fa-times ml-2"></i></a>
                    <button type="button" class="btn btn-sm btn-primary" onclick="event.preventDefault(); create_boxnow();">
                        Spremi način dostave <i class="fa fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('shipment-modal-js')
    <script>
        $(() => {
            $('#boxnow-geo-zone').select2({
                minimumResultsForSearch: Infinity,
                allowClear: true
            });

            @if($errors->hasAny(['client_id', 'client_secret', 'api_partner_id', 'widget_partner_id', 'warehouse_location_id', 'origin_name', 'origin_email', 'origin_phone', 'allow_return']))
                $('#shipment-modal-boxnow').modal('show');
            @endif
        });

        function create_boxnow() {
            let item = {
                title: $('#boxnow-title').val(),
                code: 'boxnow',
                data: {
                    price: $('#boxnow-price').val(),
                    time: $('#boxnow-time').val(),
                    short_description: $('#boxnow-short-description').val(),
                    description: $('#boxnow-description').val()
                },
                geo_zone: $('#boxnow-geo-zone').val(),
                status: $('#boxnow-status')[0].checked,
                sort_order: $('#boxnow-sort-order').val()
            };

            item = collectShippingLocaleFields('boxnow', item);

            axios.post("{{ route('api.shipping.store') }}", {data: item})
                .then(response => {
                    if (response.data.success) {
                        location.reload();
                    } else {
                        return errorToast.fire(response.data.message);
                    }
                }).catch(handleSettingsSaveError);
        }

        function edit_boxnow(item) {
            fillShippingBaseFields('boxnow', item);
        }
    </script>
@endpush
