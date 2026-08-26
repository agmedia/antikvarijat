@php
    $woltSettings = isset($woltSettings) && is_array($woltSettings) ? $woltSettings : [];
    $woltModuleEnabled = (bool) data_get($woltSettings, 'module_enabled', false);
    $woltEnvironment = (string) data_get($woltSettings, 'environment', 'development');
    $woltHasApiKey = (bool) data_get($woltSettings, 'has_api_key', false);
    $woltHasWebhookSecret = (bool) data_get($woltSettings, 'has_webhook_secret', false);
    $woltHasVenue = trim((string) data_get($woltSettings, 'venue_id', '')) !== '';
    $woltApiReady = $woltHasApiKey && $woltHasVenue;
    $woltIntegrationReady = $woltApiReady && $woltHasWebhookSecret;
    $woltApiUpdateUrl = \Illuminate\Support\Facades\Route::has('wolt-settings.update')
        ? route('wolt-settings.update')
        : '#';
    $woltApiErrorFields = [
        'module_enabled',
        'environment',
        'venue_id',
        'merchant_id',
        'api_key',
        'webhook_secret',
        'availability_cache_seconds',
        'preparation_time_minutes',
        'request_timeout_seconds',
        'fallback_weight_grams',
        'cod_enabled',
        'pricing_mode',
        'quote_markup_percent',
        'max_quote_price',
        'support_url',
        'support_email',
        'support_phone',
    ];
@endphp

<div class="modal fade" id="shipment-modal-wolt_drive" tabindex="-1" role="dialog" aria-labelledby="shipment-modal-wolt-drive-title" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-popout" role="document">
        <div class="modal-content rounded">
            <div class="block block-themed block-transparent mb-0">
                <div class="block-header bg-primary">
                    <div>
                        <div class="wolt-modal-kicker"><i class="fa-duotone fa-motorcycle mr-1" aria-hidden="true"></i> Dostava isti dan</div>
                        <h3 class="block-title" id="shipment-modal-wolt-drive-title">Wolt Drive</h3>
                    </div>
                    <div class="block-options">
                        <button type="button" class="btn-block-option text-white" data-dismiss="modal" aria-label="Zatvori">
                            <i class="fa fa-times" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="block-content pt-3">
                    <div class="wolt-status-grid mb-4" aria-label="Status Wolt Drive postavki">
                        <div class="wolt-status-item">
                            <span class="wolt-status-label">Checkout metoda</span>
                            <span class="badge badge-secondary" id="wolt-shipping-status-badge">Nije učitano</span>
                        </div>
                        <div class="wolt-status-item">
                            <span class="wolt-status-label">Wolt modul</span>
                            <span class="badge {{ $woltModuleEnabled ? 'badge-success' : 'badge-secondary' }}">
                                {{ $woltModuleEnabled ? 'Omogućen' : 'Onemogućen' }}
                            </span>
                        </div>
                        <div class="wolt-status-item">
                            <span class="wolt-status-label">Integracija</span>
                            <span class="badge {{ $woltIntegrationReady ? 'badge-success' : 'badge-warning' }}">
                                {{ $woltIntegrationReady ? 'Spremna' : 'Nedostaje API ključ, Venue ID ili webhook secret' }}
                            </span>
                        </div>
                        <div class="wolt-status-item">
                            <span class="wolt-status-label">Okruženje</span>
                            <span class="badge {{ $woltEnvironment === 'production' ? 'badge-success' : 'badge-info' }}">
                                {{ $woltEnvironment === 'production' ? 'Produkcija' : 'Development' }}
                            </span>
                        </div>
                    </div>

                    <ul class="nav nav-tabs nav-tabs-block" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="wolt-shipping-tab" data-toggle="tab" href="#wolt-shipping-panel" role="tab" aria-controls="wolt-shipping-panel" aria-selected="true">
                                <i class="fa-duotone fa-truck-fast mr-1" aria-hidden="true"></i> Dostava i pravila
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="wolt-api-tab" data-toggle="tab" href="#wolt-api-panel" role="tab" aria-controls="wolt-api-panel" aria-selected="false">
                                <i class="fa-duotone fa-key mr-1" aria-hidden="true"></i> Wolt API
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active py-4" id="wolt-shipping-panel" role="tabpanel" aria-labelledby="wolt-shipping-tab">
                            <div class="wolt-section-heading">
                                <div>
                                    <h4>Prikaz u checkoutu</h4>
                                    <p>Tekstovi, cijena i redoslijed koje kupac vidi pri odabiru dostave.</p>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="wolt_drive-title">Naziv dostave (HR)</label>
                                        <input type="text" class="form-control" id="wolt_drive-title" maxlength="191" placeholder="Wolt Drive — dostava isti dan">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="wolt_drive-price">Osnovna cijena</label>
                                        <div class="input-group">
                                            <input type="number" min="0" step="0.01" class="form-control" id="wolt_drive-price" inputmode="decimal">
                                            <div class="input-group-append"><span class="input-group-text">€</span></div>
                                        </div>
                                        <small class="form-text text-muted">Koristi se u fixed načinu obračuna.</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="wolt_drive-sort-order">Poredak</label>
                                        <input type="number" min="0" step="1" class="form-control" id="wolt_drive-sort-order" inputmode="numeric">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="wolt_drive-geo-zone">Geo zona</label>
                                        <select class="js-select2 form-control" id="wolt_drive-geo-zone" style="width: 100%;" data-placeholder="Odaberite geo zonu">
                                            <option></option>
                                            @foreach ($geo_zones as $geo_zone)
                                                <option value="{{ $geo_zone->id }}">{{ $geo_zone->title }}</option>
                                            @endforeach
                                        </select>
                                        <small class="form-text text-muted">Wolt API dodatno provjerava stvarnu dostupnost konkretne adrese.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="wolt_drive-time">Procijenjeno trajanje (HR)</label>
                                        <input type="text" class="form-control" id="wolt_drive-time" maxlength="191" placeholder="Isporuka isti dan">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-4">
                                        <label for="wolt_drive-short-description">Kratki opis (HR)</label>
                                        <textarea class="form-control" id="wolt_drive-short-description" rows="2" maxlength="500" placeholder="Prikazuje se uz način dostave u checkoutu."></textarea>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-4">
                                        <label for="wolt_drive-description">Detaljni opis (HR)</label>
                                        <textarea class="form-control" id="wolt_drive-description" rows="4" placeholder="Dodatne informacije nakon odabira Wolt Drive dostave."></textarea>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" id="wolt_drive-code" value="wolt_drive">

                            <hr class="my-4">

                            <div class="wolt-section-heading">
                                <div>
                                    <h4>Uvjeti dostupnosti</h4>
                                    <p>Ograničenja se kombiniraju. Prazno polje znači da to ograničenje nije aktivno.</p>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="wolt-rule-min-subtotal">Minimalna vrijednost košarice</label>
                                        <div class="input-group">
                                            <input type="number" min="0" step="0.01" class="form-control" id="wolt-rule-min-subtotal" inputmode="decimal">
                                            <div class="input-group-append"><span class="input-group-text">€</span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="wolt-rule-max-subtotal">Maksimalna vrijednost košarice</label>
                                        <div class="input-group">
                                            <input type="number" min="0" step="0.01" class="form-control" id="wolt-rule-max-subtotal" inputmode="decimal">
                                            <div class="input-group-append"><span class="input-group-text">€</span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="wolt-rule-max-items">Maksimalan broj artikala</label>
                                        <input type="number" min="1" step="1" class="form-control" id="wolt-rule-max-items" inputmode="numeric">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="wolt-rule-allowed-postal-codes">Dopušteni poštanski brojevi</label>
                                        <textarea class="form-control" id="wolt-rule-allowed-postal-codes" rows="3" placeholder="10000, 10010, 10020"></textarea>
                                        <small class="form-text text-muted">Odvojite zarezom ili novim redom.</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="wolt-rule-excluded-postal-codes">Isključeni poštanski brojevi</label>
                                        <textarea class="form-control" id="wolt-rule-excluded-postal-codes" rows="3" placeholder="Poštanski brojevi koji imaju prednost nad dopuštenima"></textarea>
                                        <small class="form-text text-muted">Odvojite zarezom ili novim redom.</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="wolt-rule-allowed-cities">Dopušteni gradovi</label>
                                        <textarea class="form-control" id="wolt-rule-allowed-cities" rows="3" placeholder="Zagreb, Velika Gorica"></textarea>
                                        <small class="form-text text-muted">Nazivi se odvajaju zarezom ili novim redom.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="wolt-rule-card mb-4">
                                <div class="wolt-rule-card-title">Raspored dostupnosti</div>
                                <div class="row align-items-end">
                                    <div class="col-lg-7">
                                        <label>Dani u tjednu</label>
                                        <div class="wolt-weekdays" role="group" aria-label="Dani kada je Wolt Drive dostupan">
                                            @foreach ([1 => 'Pon', 2 => 'Uto', 3 => 'Sri', 4 => 'Čet', 5 => 'Pet', 6 => 'Sub', 7 => 'Ned'] as $dayNumber => $dayLabel)
                                                <div class="custom-control custom-checkbox custom-control-inline mr-3 mb-2">
                                                    <input type="checkbox" class="custom-control-input js-wolt-weekday" id="wolt-rule-weekday-{{ $dayNumber }}" value="{{ $dayNumber }}" checked>
                                                    <label class="custom-control-label" for="wolt-rule-weekday-{{ $dayNumber }}">{{ $dayLabel }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-2">
                                        <div class="form-group">
                                            <label for="wolt-rule-time-from">Od</label>
                                            <input type="time" class="form-control" id="wolt-rule-time-from">
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-2">
                                        <div class="form-group">
                                            <label for="wolt-rule-time-to">Do</label>
                                            <input type="time" class="form-control" id="wolt-rule-time-to">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="wolt-rule-card">
                                <div class="wolt-rule-card-title">Besplatna dostava</div>
                                <div class="row align-items-end">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="wolt-rule-free-shipping-mode">Pravilo</label>
                                            <select class="form-control" id="wolt-rule-free-shipping-mode">
                                                <option value="never" selected>Wolt Drive nikad nije besplatan</option>
                                                <option value="global">Koristi globalni prag trgovine</option>
                                                <option value="custom">Poseban Wolt prag</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="wolt-rule-free-shipping-threshold-wrap">
                                        <div class="form-group">
                                            <label for="wolt-rule-free-shipping-threshold">Poseban prag</label>
                                            <div class="input-group">
                                                <input type="number" min="0" step="0.01" class="form-control" id="wolt-rule-free-shipping-threshold" inputmode="decimal">
                                                <div class="input-group-append"><span class="input-group-text">€</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="wolt-enable-row mt-4">
                                <div>
                                    <strong>Prikaži Wolt Drive u checkoutu</strong>
                                    <p class="mb-0 text-muted">Metoda će se prikazati samo kada su zadovoljena spremljena pravila i Wolt potvrdi adresu.</p>
                                </div>
                                <div class="custom-control custom-switch custom-control-success">
                                    <input type="checkbox" class="custom-control-input" id="wolt_drive-status">
                                    <label class="custom-control-label" for="wolt_drive-status">Aktivno</label>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade py-4" id="wolt-api-panel" role="tabpanel" aria-labelledby="wolt-api-tab">
                            <form id="wolt-api-settings-form" method="POST" action="{{ $woltApiUpdateUrl }}" autocomplete="off">
                                @csrf
                                {{ method_field('PATCH') }}

                                <div class="alert alert-info d-flex align-items-start">
                                    <i class="fa-duotone fa-shield-check mt-1 mr-3" aria-hidden="true"></i>
                                    <div>
                                        <strong>API adresa nije ručno promjenjiva.</strong>
                                        <div>Siguran endpoint određuje se odabirom production ili development okruženja. Tajni ključevi spremaju se šifrirano i nakon spremanja se više ne prikazuju.</div>
                                    </div>
                                </div>

                                @unless (\Illuminate\Support\Facades\Route::has('wolt-settings.update'))
                                    <div class="alert alert-warning">Backend ruta za spremanje Wolt API postavki još nije registrirana.</div>
                                @endunless

                                <div class="wolt-section-heading">
                                    <div>
                                        <h4>Pristup Wolt Drive servisu</h4>
                                        <p>Modul i checkout metoda imaju odvojene statuse kako se integracija može sigurno isključiti bez gubitka postavki dostave.</p>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="wolt-module-enabled">Status modula</label>
                                            <select class="form-control @error('module_enabled', 'wolt') is-invalid @enderror" id="wolt-module-enabled" name="module_enabled">
                                                <option value="1" @if((string) old('module_enabled', (int) $woltModuleEnabled) === '1') selected @endif>Omogućen</option>
                                                <option value="0" @if((string) old('module_enabled', (int) $woltModuleEnabled) === '0') selected @endif>Onemogućen</option>
                                            </select>
                                            @error('module_enabled', 'wolt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="wolt-environment">Okruženje</label>
                                            <select class="form-control @error('environment', 'wolt') is-invalid @enderror" id="wolt-environment" name="environment">
                                                <option value="development" @if(old('environment', $woltEnvironment) === 'development') selected @endif>Development</option>
                                                <option value="production" @if(old('environment', $woltEnvironment) === 'production') selected @endif>Production</option>
                                            </select>
                                            @error('environment', 'wolt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="wolt-venue-id">Venue ID</label>
                                            <input class="form-control @error('venue_id', 'wolt') is-invalid @enderror" id="wolt-venue-id" name="venue_id" type="text" value="{{ old('venue_id', data_get($woltSettings, 'venue_id')) }}" maxlength="191" autocomplete="off">
                                            @error('venue_id', 'wolt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="wolt-merchant-id">Merchant ID <span class="text-muted">(opcionalno)</span></label>
                                            <input class="form-control @error('merchant_id', 'wolt') is-invalid @enderror" id="wolt-merchant-id" name="merchant_id" type="text" value="{{ old('merchant_id', data_get($woltSettings, 'merchant_id')) }}" maxlength="191" autocomplete="off">
                                            @error('merchant_id', 'wolt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="wolt-api-key">API ključ</label>
                                            <input class="form-control @error('api_key', 'wolt') is-invalid @enderror" id="wolt-api-key" name="api_key" type="password" value="" maxlength="2000" autocomplete="new-password" placeholder="{{ $woltHasApiKey ? 'Spremljen — ostavite prazno ako ga ne mijenjate' : 'Upišite Wolt API ključ' }}">
                                            @error('api_key', 'wolt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            @if($woltHasApiKey)
                                                <small class="form-text text-success"><i class="fa fa-lock mr-1" aria-hidden="true"></i>API ključ je spremljen šifrirano.</small>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="wolt-webhook-secret">Webhook secret</label>
                                            <input class="form-control @error('webhook_secret', 'wolt') is-invalid @enderror" id="wolt-webhook-secret" name="webhook_secret" type="password" value="" maxlength="2000" autocomplete="new-password" placeholder="{{ $woltHasWebhookSecret ? 'Spremljen — ostavite prazno ako ga ne mijenjate' : 'Upišite Webhook secret' }}">
                                            @error('webhook_secret', 'wolt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            @if($woltHasWebhookSecret)
                                                <small class="form-text text-success"><i class="fa fa-lock mr-1" aria-hidden="true"></i>Webhook secret je spremljen šifrirano.</small>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="wolt-webhook-url">Webhook callback URL</label>
                                            <div class="input-group">
                                                <input class="form-control" id="wolt-webhook-url" type="text" readonly value="{{ route('api.wolt.webhook') }}">
                                                <div class="input-group-append">
                                                    <button class="btn btn-alt-secondary" type="button" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('wolt-webhook-url').value)">
                                                        <i class="fa fa-copy mr-1" aria-hidden="true"></i>Kopiraj
                                                    </button>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">Ovu adresu i isti secret potrebno je registrirati za merchant u Wolt Drive konfiguraciji.</small>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <div class="wolt-section-heading">
                                    <div>
                                        <h4>Operativne postavke</h4>
                                        <p>Vrijednosti koje određuju provjeru dostupnosti, pripremu i podatke paketa.</p>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-6 col-lg-3">
                                        <div class="form-group">
                                            <label for="wolt-availability-cache-seconds">Cache dostupnosti</label>
                                            <div class="input-group">
                                                <input class="form-control @error('availability_cache_seconds', 'wolt') is-invalid @enderror" id="wolt-availability-cache-seconds" name="availability_cache_seconds" type="number" min="0" max="900" step="1" value="{{ old('availability_cache_seconds', data_get($woltSettings, 'availability_cache_seconds', 300)) }}">
                                                <div class="input-group-append"><span class="input-group-text">s</span></div>
                                                @error('availability_cache_seconds', 'wolt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-3">
                                        <div class="form-group">
                                            <label for="wolt-preparation-time-minutes">Vrijeme pripreme</label>
                                            <div class="input-group">
                                                <input class="form-control @error('preparation_time_minutes', 'wolt') is-invalid @enderror" id="wolt-preparation-time-minutes" name="preparation_time_minutes" type="number" min="0" max="60" step="1" value="{{ old('preparation_time_minutes', data_get($woltSettings, 'preparation_time_minutes', 30)) }}">
                                                <div class="input-group-append"><span class="input-group-text">min</span></div>
                                                @error('preparation_time_minutes', 'wolt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-3">
                                        <div class="form-group">
                                            <label for="wolt-request-timeout-seconds">API timeout</label>
                                            <div class="input-group">
                                                <input class="form-control @error('request_timeout_seconds', 'wolt') is-invalid @enderror" id="wolt-request-timeout-seconds" name="request_timeout_seconds" type="number" min="3" max="30" step="1" value="{{ old('request_timeout_seconds', data_get($woltSettings, 'request_timeout_seconds', 20)) }}">
                                                <div class="input-group-append"><span class="input-group-text">s</span></div>
                                                @error('request_timeout_seconds', 'wolt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-3">
                                        <div class="form-group">
                                            <label for="wolt-fallback-weight-grams">Zadana težina paketa</label>
                                            <div class="input-group">
                                                <input class="form-control @error('fallback_weight_grams', 'wolt') is-invalid @enderror" id="wolt-fallback-weight-grams" name="fallback_weight_grams" type="number" min="1" max="25000" step="1" value="{{ old('fallback_weight_grams', data_get($woltSettings, 'fallback_weight_grams', 500)) }}">
                                                <div class="input-group-append"><span class="input-group-text">g</span></div>
                                                @error('fallback_weight_grams', 'wolt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <div class="wolt-section-heading">
                                    <div>
                                        <h4>Naplata dostave</h4>
                                        <p>Fixed koristi osnovnu checkout cijenu. Quote koristi Wolt cijenu uvećanu za postotak, uz opcionalnu gornju granicu.</p>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="wolt-cod-enabled">Plaćanje pouzećem</label>
                                            <select class="form-control @error('cod_enabled', 'wolt') is-invalid @enderror" id="wolt-cod-enabled" name="cod_enabled">
                                                <option value="1" @if((string) old('cod_enabled', (int) data_get($woltSettings, 'cod_enabled', true)) === '1') selected @endif>Omogućeno</option>
                                                <option value="0" @if((string) old('cod_enabled', (int) data_get($woltSettings, 'cod_enabled', true)) === '0') selected @endif>Onemogućeno</option>
                                            </select>
                                            @error('cod_enabled', 'wolt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="wolt-pricing-mode">Način obračuna</label>
                                            <select class="form-control @error('pricing_mode', 'wolt') is-invalid @enderror" id="wolt-pricing-mode" name="pricing_mode">
                                                <option value="fixed" @if(old('pricing_mode', data_get($woltSettings, 'pricing_mode', 'fixed')) === 'fixed') selected @endif>Fiksna checkout cijena</option>
                                                <option value="quote" @if(old('pricing_mode', data_get($woltSettings, 'pricing_mode', 'fixed')) === 'quote') selected @endif>Wolt quote</option>
                                            </select>
                                            @error('pricing_mode', 'wolt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4 js-wolt-quote-field">
                                        <div class="form-group">
                                            <label for="wolt-quote-markup-percent">Uvećanje quote cijene</label>
                                            <div class="input-group">
                                                <input class="form-control @error('quote_markup_percent', 'wolt') is-invalid @enderror" id="wolt-quote-markup-percent" name="quote_markup_percent" type="number" min="0" max="200" step="0.01" value="{{ old('quote_markup_percent', data_get($woltSettings, 'quote_markup_percent', 0)) }}">
                                                <div class="input-group-append"><span class="input-group-text">%</span></div>
                                                @error('quote_markup_percent', 'wolt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 js-wolt-quote-field">
                                        <div class="form-group">
                                            <label for="wolt-max-quote-price">Maksimalna prihvatljiva quote cijena</label>
                                            <div class="input-group">
                                                <input class="form-control @error('max_quote_price', 'wolt') is-invalid @enderror" id="wolt-max-quote-price" name="max_quote_price" type="number" min="0" max="500" step="0.01" value="{{ old('max_quote_price', (float) data_get($woltSettings, 'max_quote_price', 0) > 0 ? data_get($woltSettings, 'max_quote_price') : null) }}">
                                                <div class="input-group-append"><span class="input-group-text">€</span></div>
                                                @error('max_quote_price', 'wolt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                            <small class="form-text text-muted">Prazno znači bez gornje granice.</small>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <div class="wolt-section-heading">
                                    <div>
                                        <h4>Korisnička podrška</h4>
                                        <p>Ovi podaci šalju se Woltu uz kreiranu dostavu.</p>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="wolt-support-url">Web adresa</label>
                                            <input class="form-control @error('support_url', 'wolt') is-invalid @enderror" id="wolt-support-url" name="support_url" type="url" value="{{ old('support_url', data_get($woltSettings, 'support_url')) }}" maxlength="500" placeholder="https://...">
                                            @error('support_url', 'wolt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="wolt-support-email">E-mail</label>
                                            <input class="form-control @error('support_email', 'wolt') is-invalid @enderror" id="wolt-support-email" name="support_email" type="email" value="{{ old('support_email', data_get($woltSettings, 'support_email')) }}" maxlength="191">
                                            @error('support_email', 'wolt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="wolt-support-phone">Telefon</label>
                                            <input class="form-control @error('support_phone', 'wolt') is-invalid @enderror" id="wolt-support-phone" name="support_phone" type="text" value="{{ old('support_phone', data_get($woltSettings, 'support_phone')) }}" maxlength="50" placeholder="+385...">
                                            @error('support_phone', 'wolt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>

                                <button class="btn btn-success" type="submit" {{ $woltApiUpdateUrl === '#' ? 'disabled' : '' }}>
                                    <i class="fa fa-save mr-1" aria-hidden="true"></i> Spremi Wolt API postavke
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="block-content block-content-full d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center bg-light">
                    <small class="text-muted mb-2 mb-sm-0">API postavke spremaju se zasebno od checkout pravila.</small>
                    <div>
                        <button type="button" class="btn btn-sm btn-light" data-dismiss="modal">Odustani <i class="fa fa-times ml-2" aria-hidden="true"></i></button>
                        <button type="button" class="btn btn-sm btn-primary" id="wolt-save-shipping" onclick="event.preventDefault(); create_wolt_drive();">
                            Spremi dostavu i pravila <i class="fa fa-arrow-right ml-2" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('css_after')
    <style>
        #shipment-modal-wolt_drive .modal-dialog { max-width: 1180px; }
        #shipment-modal-wolt_drive .modal-content { overflow: hidden; }
        #shipment-modal-wolt_drive .block-header.bg-primary { min-height: 4.6rem; }
        #shipment-modal-wolt_drive .wolt-modal-kicker { margin-bottom: .15rem; color: rgba(255, 255, 255, .72); font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        #shipment-modal-wolt_drive .block-header.bg-primary .block-title { color: #fff; font-size: 1.35rem; }
        #shipment-modal-wolt_drive .wolt-status-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .65rem; }
        #shipment-modal-wolt_drive .wolt-status-item { display: flex; min-width: 0; min-height: 4rem; flex-direction: column; align-items: flex-start; justify-content: center; padding: .7rem .85rem; border: 1px solid var(--admin-line, #d5d2ca); border-radius: var(--admin-radius-sm, .25rem); background: var(--admin-surface-soft, #f7f5f0); }
        #shipment-modal-wolt_drive .wolt-status-label { margin-bottom: .3rem; color: var(--admin-muted, #68746d); font-size: .7rem; font-weight: 800; letter-spacing: .045em; text-transform: uppercase; }
        #shipment-modal-wolt_drive .wolt-section-heading { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; }
        #shipment-modal-wolt_drive .wolt-section-heading h4 { margin: 0 0 .2rem; color: var(--admin-ink, #202a24); font-size: 1rem; font-weight: 800; }
        #shipment-modal-wolt_drive .wolt-section-heading p { margin: 0; color: var(--admin-muted, #68746d); font-size: .84rem; }
        #shipment-modal-wolt_drive .wolt-rule-card { padding: 1rem; border: 1px solid var(--admin-line, #d5d2ca); border-radius: var(--admin-radius, .38rem); background: #faf9f6; }
        #shipment-modal-wolt_drive .wolt-rule-card-title { margin-bottom: .85rem; color: var(--admin-ink, #202a24); font-size: .8rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
        #shipment-modal-wolt_drive .wolt-enable-row { display: flex; gap: 1rem; align-items: center; justify-content: space-between; padding: 1rem; border: 1px solid #adc1b5; border-radius: var(--admin-radius, .38rem); background: #f0f5f2; }
        #shipment-modal-wolt_drive .wolt-enable-row p { font-size: .82rem; }
        #shipment-modal-wolt_drive .nav-tabs-block { margin: 0 -1.25rem; padding: 0 1.25rem; }
        #shipment-modal-wolt_drive .custom-control-inline:last-child { margin-right: 0 !important; }
        #shipment-modal-wolt_drive .alert > i { flex: 0 0 auto; color: var(--admin-forest, #315344); font-size: 1.2rem; }
        @media (max-width: 991.98px) {
            #shipment-modal-wolt_drive .wolt-status-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 575.98px) {
            #shipment-modal-wolt_drive .modal-dialog { margin: .5rem; }
            #shipment-modal-wolt_drive .wolt-status-grid { grid-template-columns: 1fr; }
            #shipment-modal-wolt_drive .wolt-enable-row { align-items: flex-start; flex-direction: column; }
            #shipment-modal-wolt_drive .block-content-full > div:last-child { display: flex; width: 100%; gap: .4rem; }
            #shipment-modal-wolt_drive .block-content-full > div:last-child .btn { flex: 1 1 auto; }
        }
    </style>
@endpush

@push('shipment-modal-js')
    <script>
        $(() => {
            $('#wolt_drive-geo-zone').select2({
                dropdownParent: $('#shipment-modal-wolt_drive'),
                minimumResultsForSearch: Infinity,
                allowClear: true
            });

            $('#wolt-rule-free-shipping-mode').on('change', toggleWoltFreeShippingThreshold);
            $('#wolt-pricing-mode').on('change', toggleWoltQuoteFields);

            toggleWoltFreeShippingThreshold();
            toggleWoltQuoteFields();

            @if($errors->getBag('wolt')->hasAny($woltApiErrorFields))
                $('#shipment-modal-wolt_drive').modal('show');
                $('#wolt-api-tab').tab('show');
            @endif
        });

        function create_wolt_drive() {
            const saveButton = $('#wolt-save-shipping');
            const originalButtonHtml = saveButton.html();

            let item = {
                title: $('#wolt_drive-title').val(),
                code: 'wolt_drive',
                data: {
                    price: $('#wolt_drive-price').val(),
                    time: $('#wolt_drive-time').val(),
                    short_description: $('#wolt_drive-short-description').val(),
                    description: $('#wolt_drive-description').val(),
                    rules: {
                        min_subtotal: nullableWoltNumber($('#wolt-rule-min-subtotal').val()),
                        max_subtotal: nullableWoltNumber($('#wolt-rule-max-subtotal').val()),
                        max_items: nullableWoltInteger($('#wolt-rule-max-items').val()),
                        allowed_postal_codes: normalizeWoltRuleText($('#wolt-rule-allowed-postal-codes').val()),
                        excluded_postal_codes: normalizeWoltRuleText($('#wolt-rule-excluded-postal-codes').val()),
                        allowed_cities: normalizeWoltRuleText($('#wolt-rule-allowed-cities').val()),
                        weekdays: $('.js-wolt-weekday:checked').map(function () {
                            return Number($(this).val());
                        }).get(),
                        time_from: emptyWoltValue($('#wolt-rule-time-from').val()),
                        time_to: emptyWoltValue($('#wolt-rule-time-to').val()),
                        free_shipping_mode: $('#wolt-rule-free-shipping-mode').val(),
                        free_shipping_threshold: $('#wolt-rule-free-shipping-mode').val() === 'custom'
                            ? nullableWoltNumber($('#wolt-rule-free-shipping-threshold').val())
                            : null
                    }
                },
                geo_zone: $('#wolt_drive-geo-zone').val(),
                status: $('#wolt_drive-status')[0].checked,
                sort_order: $('#wolt_drive-sort-order').val()
            };

            item = collectShippingLocaleFields('wolt_drive', item);

            saveButton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Spremanje...');

            axios.post("{{ route('api.shipping.store') }}", { data: item })
                .then(response => {
                    if (response.data.success) {
                        successToast.fire({ text: response.data.success, timer: 1300 })
                            .then(() => location.reload());
                        return;
                    }

                    return errorToast.fire(response.data.message || 'Wolt Drive postavke nije moguće spremiti.');
                })
                .catch(handleSettingsSaveError)
                .finally(() => saveButton.prop('disabled', false).html(originalButtonHtml));
        }

        function edit_wolt_drive(item) {
            const rules = item && item.data && item.data.rules ? item.data.rules : {};

            fillShippingBaseFields('wolt_drive', item);

            $('#wolt-rule-min-subtotal').val(woltRuleValue(rules.min_subtotal));
            $('#wolt-rule-max-subtotal').val(woltRuleValue(rules.max_subtotal));
            $('#wolt-rule-max-items').val(woltRuleValue(rules.max_items));
            $('#wolt-rule-allowed-postal-codes').val(formatWoltRuleList(rules.allowed_postal_codes));
            $('#wolt-rule-excluded-postal-codes').val(formatWoltRuleList(rules.excluded_postal_codes));
            $('#wolt-rule-allowed-cities').val(formatWoltRuleList(rules.allowed_cities));
            $('#wolt-rule-time-from').val(rules.time_from || '');
            $('#wolt-rule-time-to').val(rules.time_to || '');
            $('#wolt-rule-free-shipping-mode').val(rules.free_shipping_mode || 'never');
            $('#wolt-rule-free-shipping-threshold').val(woltRuleValue(rules.free_shipping_threshold));

            const weekdays = normalizeWoltWeekdays(rules.weekdays);
            $('.js-wolt-weekday').each(function () {
                this.checked = weekdays.includes(Number($(this).val()));
            });

            $('#wolt-shipping-status-badge')
                .toggleClass('badge-success', !!item.status)
                .toggleClass('badge-secondary', !item.status)
                .text(item.status ? 'Aktivna' : 'Neaktivna');

            toggleWoltFreeShippingThreshold();
        }

        function toggleWoltFreeShippingThreshold() {
            const isCustom = $('#wolt-rule-free-shipping-mode').val() === 'custom';

            $('#wolt-rule-free-shipping-threshold-wrap').toggle(isCustom);
            $('#wolt-rule-free-shipping-threshold').prop('disabled', !isCustom);
        }

        function toggleWoltQuoteFields() {
            const isQuote = $('#wolt-pricing-mode').val() === 'quote';

            $('.js-wolt-quote-field').toggle(isQuote);
        }

        function normalizeWoltRuleText(value) {
            const seen = new Set();
            const entries = String(value || '')
                .split(/[\n,;]+/)
                .map(entry => entry.trim())
                .filter(entry => {
                    const key = entry.toLocaleLowerCase();

                    if (!entry || seen.has(key)) {
                        return false;
                    }

                    seen.add(key);
                    return true;
                });

            return entries.length ? entries.join('\n') : null;
        }

        function formatWoltRuleList(value) {
            if (Array.isArray(value)) {
                return value.join('\n');
            }

            return value || '';
        }

        function normalizeWoltWeekdays(value) {
            if (!Array.isArray(value) || value.length === 0) {
                return [1, 2, 3, 4, 5, 6, 7];
            }

            return value.map(Number).filter(day => day >= 1 && day <= 7);
        }

        function nullableWoltNumber(value) {
            if (value === null || value === undefined || String(value).trim() === '') {
                return null;
            }

            return Number(value);
        }

        function nullableWoltInteger(value) {
            const number = nullableWoltNumber(value);

            return number === null ? null : Math.trunc(number);
        }

        function woltRuleValue(value) {
            return value === null || value === undefined ? '' : value;
        }

        function emptyWoltValue(value) {
            const normalized = String(value || '').trim();

            return normalized === '' ? null : normalized;
        }
    </script>
@endpush
