@extends('back.layouts.backend')

@section('content')
    <div class="admin-page-hero">
        <div class="content content-full">
            <div class="admin-page-heading">
                <div>
                    <div class="admin-page-kicker"><i class="fa-duotone fa-gift-card" aria-hidden="true"></i> Marketing</div>
                    <h1 class="admin-page-title">Poklon bonovi</h1>
                    <p class="admin-page-description">Pregledajte izdane bonove, preostali saldo, dostavu primatelju i iskorištenja.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        @include('back.layouts.partials.session')

        @if(!config('gift_vouchers.emails_enabled', true))
            <div class="alert alert-warning d-flex align-items-start" role="alert">
                <i class="fa-duotone fa-triangle-exclamation mr-2 mt-1" aria-hidden="true"></i>
                <div>Slanje e-mailova poklon bonova trenutačno je isključeno u konfiguraciji. Bonovi će se izdati, ali neće biti automatski poslani.</div>
            </div>
        @endif

        <div class="row admin-gift-voucher-stats">
            <div class="col-sm-6 col-xl-3 d-flex">
                <div class="block block-rounded bg-body-light flex-fill">
                    <div class="block-content d-flex align-items-center py-4">
                        <span class="admin-section-icon mr-3"><i class="fa-duotone fa-chart-line-up" aria-hidden="true"></i></span>
                        <div>
                            <div class="font-size-h3 font-w700">{{ number_format($stats['sold'], 2, ',', '.') }} €</div>
                            <div class="text-muted">Vrijednost izdanih bonova</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3 d-flex">
                <div class="block block-rounded bg-body-light flex-fill">
                    <div class="block-content d-flex align-items-center py-4">
                        <span class="admin-section-icon mr-3"><i class="fa-duotone fa-wallet" aria-hidden="true"></i></span>
                        <div>
                            <div class="font-size-h3 font-w700">{{ number_format($stats['balance'], 2, ',', '.') }} €</div>
                            <div class="text-muted">Preostali saldo</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3 d-flex">
                <div class="block block-rounded bg-body-light flex-fill">
                    <div class="block-content d-flex align-items-center py-4">
                        <span class="admin-section-icon mr-3"><i class="fa-duotone fa-circle-check" aria-hidden="true"></i></span>
                        <div>
                            <div class="font-size-h3 font-w700">{{ number_format($stats['active'], 0, ',', '.') }}</div>
                            <div class="text-muted">Aktivnih bonova</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3 d-flex">
                <div class="block block-rounded bg-body-light flex-fill">
                    <div class="block-content d-flex align-items-center py-4">
                        <span class="admin-section-icon mr-3"><i class="fa-duotone fa-hourglass-half" aria-hidden="true"></i></span>
                        <div>
                            <div class="font-size-h3 font-w700">{{ number_format($stats['pending'], 0, ',', '.') }}</div>
                            <div class="text-muted">Čeka plaćanje</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <div class="d-flex align-items-center min-width-0">
                    <span class="admin-section-icon mr-3"><i class="fa-duotone fa-gifts" aria-hidden="true"></i></span>
                    <div>
                        <h2 class="block-title mb-1">Svi poklon bonovi</h2>
                        <span class="admin-count">{{ number_format($giftVouchers->total(), 0, ',', '.') }} bonova</span>
                    </div>
                </div>
                @if($search !== '' || $status !== '')
                    <div class="block-options">
                        <a class="btn btn-secondary" href="{{ route('gift-vouchers.index') }}">
                            <i class="fa-regular fa-xmark mr-1" aria-hidden="true"></i> Očisti filtere
                        </a>
                    </div>
                @endif
            </div>

            <div class="block-content">
                <div class="admin-filter-panel p-3 mb-4">
                    <form method="get" action="{{ route('gift-vouchers.index') }}">
                        <div class="form-row align-items-end">
                            <div class="col-lg-8 mb-2">
                                <label class="admin-filter-label" for="gift-voucher-search">Pretraživanje</label>
                                <input class="form-control"
                                       id="gift-voucher-search"
                                       type="search"
                                       name="search"
                                       value="{{ $search }}"
                                       placeholder="Kod, zadnjih 6 znakova, ime, e-mail ili # narudžbe">
                            </div>
                            <div class="col-sm-8 col-lg-3 mb-2">
                                <label class="admin-filter-label" for="gift-voucher-status">Status</label>
                                <select class="form-control" id="gift-voucher-status" name="status">
                                    <option value="">Svi statusi</option>
                                    <option value="pending" @if($status === 'pending') selected @endif>Čeka plaćanje</option>
                                    <option value="active" @if($status === 'active') selected @endif>Aktivan</option>
                                    <option value="exhausted" @if($status === 'exhausted') selected @endif>Iskorišten</option>
                                    <option value="disabled" @if($status === 'disabled') selected @endif>Onemogućen</option>
                                    <option value="cancelled" @if($status === 'cancelled') selected @endif>Otkazan</option>
                                </select>
                            </div>
                            <div class="col-sm-4 col-lg-1 mb-2">
                                <button class="btn btn-primary btn-block" type="submit" title="Primijeni filter">
                                    <i class="fa-duotone fa-filter" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-borderless table-striped table-vcenter admin-data-table admin-gift-voucher-table">
                        <thead>
                        <tr>
                            <th>Kod i status</th>
                            <th>Primatelj</th>
                            <th>Kupac</th>
                            <th class="text-right">Vrijednost</th>
                            <th class="text-right">Saldo</th>
                            <th>Narudžba</th>
                            <th>Dostava</th>
                            <th class="text-right">Radnje</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($giftVouchers as $voucher)
                            @php
                                $code = $voucher->code;
                                $redemptionLabels = [
                                    \App\Models\GiftVoucherRedemption::STATUS_RESERVED => ['Rezervirano', 'warning'],
                                    \App\Models\GiftVoucherRedemption::STATUS_REDEEMED => ['Iskorišteno', 'success'],
                                    \App\Models\GiftVoucherRedemption::STATUS_RELEASED => ['Vraćeno', 'secondary'],
                                ];
                            @endphp
                            <tr>
                                <td data-label="Kod i status">
                                    @if($code)
                                        <div class="admin-gift-voucher-code-row">
                                            <code class="admin-gift-voucher-code">{{ $code }}</code>
                                            <button class="btn btn-sm btn-alt-secondary admin-gift-voucher-copy"
                                                    type="button"
                                                    data-copy-value="{{ $code }}"
                                                    title="Kopiraj kod"
                                                    aria-label="Kopiraj kod {{ $code }}">
                                                <i class="fa-duotone fa-copy" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    @else
                                        <span class="font-w600 text-muted">Kod nakon plaćanja</span>
                                    @endif
                                    <div class="mt-2"><span class="badge badge-{{ $voucher->status_color }}">{{ $voucher->display_status }}</span></div>
                                    <div class="font-size-sm text-muted mt-1">Kreiran {{ optional($voucher->created_at)->format('d.m.Y. H:i') }}</div>
                                </td>
                                <td data-label="Primatelj">
                                    <div class="font-w600">{{ $voucher->recipient_name ?: '—' }}</div>
                                    <a class="font-size-sm" href="mailto:{{ $voucher->recipient_email }}">{{ $voucher->recipient_email }}</a>
                                    @if($voucher->sender_name)
                                        <div class="font-size-sm text-muted mt-1">Šalje: {{ $voucher->sender_name }}</div>
                                    @endif
                                    @if($voucher->message)
                                        <div class="admin-gift-voucher-message mt-2" title="{{ $voucher->message }}">„{{ \Illuminate\Support\Str::limit($voucher->message, 75) }}”</div>
                                    @endif
                                </td>
                                <td data-label="Kupac">
                                    <div class="font-w600">{{ $voucher->buyer_name ?: '—' }}</div>
                                    @if($voucher->buyer_email)
                                        <a class="font-size-sm" href="mailto:{{ $voucher->buyer_email }}">{{ $voucher->buyer_email }}</a>
                                    @endif
                                </td>
                                <td class="text-right text-nowrap" data-label="Vrijednost"><strong>{{ number_format($voucher->initial_amount, 2, ',', '.') }} €</strong></td>
                                <td class="text-right text-nowrap" data-label="Saldo">
                                    <strong class="{{ (float) $voucher->balance > 0 ? 'text-success' : 'text-muted' }}">{{ number_format($voucher->balance, 2, ',', '.') }} €</strong>
                                </td>
                                <td data-label="Narudžba">
                                    @if($voucher->purchaseOrder)
                                        <a class="font-w600" href="{{ route('orders.show', ['order' => $voucher->purchaseOrder]) }}">#{{ $voucher->purchase_order_id }}</a>
                                        <div class="font-size-sm text-muted">{{ optional($voucher->purchaseOrder->created_at)->format('d.m.Y. H:i') }}</div>
                                    @else
                                        <span class="text-muted">#{{ $voucher->purchase_order_id ?: '—' }}</span>
                                    @endif
                                </td>
                                <td data-label="Dostava">
                                    @if($voucher->email_error)
                                        <span class="badge badge-danger">Greška slanja</span>
                                        <div class="font-size-sm text-danger mt-1" title="{{ $voucher->email_error }}">{{ \Illuminate\Support\Str::limit($voucher->email_error, 55) }}</div>
                                    @elseif($voucher->last_email_sent_at)
                                        <span class="badge badge-success">Poslano</span>
                                        <div class="font-size-sm text-muted mt-1">{{ $voucher->last_email_sent_at->format('d.m.Y. H:i') }}</div>
                                    @elseif($voucher->issued_at)
                                        <span class="badge badge-warning">Nije poslano</span>
                                    @else
                                        <span class="badge badge-secondary">Čeka plaćanje</span>
                                    @endif
                                </td>
                                <td class="text-right admin-gift-voucher-actions" data-label="Radnje">
                                    @if($voucher->issued_at && $code)
                                        <form class="d-inline-block" method="post" action="{{ route('gift-vouchers.resend', ['giftVoucher' => $voucher]) }}" onsubmit="return confirm('Ponovno poslati poklon bon primatelju?');">
                                            @csrf
                                            <button class="btn btn-sm btn-alt-primary" type="submit" title="Pošalji ponovno" @if(!config('gift_vouchers.emails_enabled', true)) disabled @endif>
                                                <i class="fa-duotone fa-paper-plane" aria-hidden="true"></i>
                                                <span class="sr-only">Pošalji ponovno</span>
                                            </button>
                                        </form>
                                    @endif
                                    @if(in_array($voucher->status, [\App\Models\GiftVoucher::STATUS_ACTIVE, \App\Models\GiftVoucher::STATUS_DISABLED], true))
                                        <form class="d-inline-block" method="post" action="{{ route('gift-vouchers.toggle', ['giftVoucher' => $voucher]) }}" onsubmit="return confirm('{{ $voucher->status === \App\Models\GiftVoucher::STATUS_DISABLED ? 'Ponovno aktivirati ovaj poklon bon?' : 'Onemogućiti ovaj poklon bon?' }}');">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm {{ $voucher->status === \App\Models\GiftVoucher::STATUS_DISABLED ? 'btn-alt-success' : 'btn-alt-danger' }}" type="submit" title="{{ $voucher->status === \App\Models\GiftVoucher::STATUS_DISABLED ? 'Aktiviraj bon' : 'Onemogući bon' }}">
                                                <i class="fa-duotone {{ $voucher->status === \App\Models\GiftVoucher::STATUS_DISABLED ? 'fa-toggle-on' : 'fa-ban' }}" aria-hidden="true"></i>
                                                <span class="sr-only">{{ $voucher->status === \App\Models\GiftVoucher::STATUS_DISABLED ? 'Aktiviraj bon' : 'Onemogući bon' }}</span>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                            @if($voucher->redemptions->isNotEmpty())
                                <tr class="admin-gift-voucher-redemptions-row">
                                    <td colspan="8">
                                        <div class="admin-gift-voucher-redemptions">
                                            <div class="admin-gift-voucher-redemptions-title"><i class="fa-duotone fa-receipt mr-1" aria-hidden="true"></i> Iskorištenja</div>
                                            @foreach($voucher->redemptions as $redemption)
                                                @php($redemptionState = $redemptionLabels[$redemption->status] ?? [ucfirst($redemption->status), 'secondary'])
                                                <div class="admin-gift-voucher-redemption">
                                                    <span class="badge badge-{{ $redemptionState[1] }}">{{ $redemptionState[0] }}</span>
                                                    <strong>{{ number_format($redemption->amount, 2, ',', '.') }} €</strong>
                                                    @if($redemption->order)
                                                        <a href="{{ route('orders.show', ['order' => $redemption->order]) }}">narudžba #{{ $redemption->order_id }}</a>
                                                    @else
                                                        <span>narudžba #{{ $redemption->order_id }}</span>
                                                    @endif
                                                    <span class="text-muted">{{ optional($redemption->redeemed_at ?: $redemption->released_at ?: $redemption->created_at)->format('d.m.Y. H:i') }}</span>
                                                    @if($redemption->release_reason)
                                                        <span class="text-muted">({{ $redemption->release_reason }})</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td class="text-center text-muted py-5" colspan="8">Nema poklon bonova za odabrane filtere.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $giftVouchers->links() }}
            </div>
        </div>
    </div>
@endsection

@push('css_after')
    <style>
        .admin-gift-voucher-stats .block { min-height: 7.25rem; }
        .admin-gift-voucher-code-row { display: flex; align-items: center; gap: .4rem; }
        .admin-gift-voucher-code { color: var(--admin-ink); font-size: .88rem; font-weight: 800; letter-spacing: .04em; white-space: nowrap; }
        .admin-gift-voucher-copy { flex: 0 0 auto; }
        .admin-gift-voucher-message { max-width: 18rem; color: #6d776f; font-size: .82rem; font-style: italic; line-height: 1.45; }
        .admin-gift-voucher-actions { min-width: 6.5rem; white-space: nowrap; }
        .admin-gift-voucher-redemptions-row > td { padding: 0 .75rem .85rem !important; background: #f7f5f0 !important; }
        .admin-gift-voucher-redemptions { display: flex; flex-wrap: wrap; gap: .55rem 1rem; align-items: center; padding: .65rem .8rem; border: 1px solid #e5e0d6; border-radius: .35rem; background: #fff; }
        .admin-gift-voucher-redemptions-title { color: var(--admin-ink); font-size: .78rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
        .admin-gift-voucher-redemption { display: inline-flex; flex-wrap: wrap; gap: .35rem; align-items: center; font-size: .82rem; }
        @media (max-width: 767.98px) {
            .admin-gift-voucher-table .admin-gift-voucher-redemptions-row { padding: 0 .75rem .9rem; }
            .admin-gift-voucher-table .admin-gift-voucher-redemptions-row > td { display: block; padding: 0 !important; }
            .admin-gift-voucher-table .admin-gift-voucher-redemptions-row > td::before { display: none; }
            .admin-gift-voucher-redemptions { display: block; }
            .admin-gift-voucher-redemptions-title { margin-bottom: .55rem; }
            .admin-gift-voucher-redemption { display: flex; margin-top: .45rem; }
            .admin-gift-voucher-actions { white-space: normal; }
        }
    </style>
@endpush

@push('js_after')
    <script>
        document.querySelectorAll('[data-copy-value]').forEach(function (button) {
            button.addEventListener('click', function () {
                var value = button.getAttribute('data-copy-value');

                if (!navigator.clipboard || !value) {
                    return;
                }

                navigator.clipboard.writeText(value).then(function () {
                    var icon = button.querySelector('i');
                    icon.classList.remove('fa-copy');
                    icon.classList.add('fa-check');
                    window.setTimeout(function () {
                        icon.classList.remove('fa-check');
                        icon.classList.add('fa-copy');
                    }, 1400);
                });
            });
        });
    </script>
@endpush
