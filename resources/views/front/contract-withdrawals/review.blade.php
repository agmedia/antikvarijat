@extends('front.layouts.app')

@section('title', __('contract_withdrawal.review.meta_title'))
@section('description', __('contract_withdrawal.review.meta_description'))
@section('robots', 'noindex,nofollow,noarchive')

@push('css_after')
    @include('front.contract-withdrawals.partials.styles')
@endpush

@section('content')
    <div class="container withdrawal-page">
        <nav class="mb-4" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item">
                    <a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('index') }}"><i class="ci-home"></i> {{ __('front.nav.home') }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a class="text-nowrap" href="{{ \App\Helpers\LocaleHelper::route('contract-withdrawal.create') }}">{{ __('contract_withdrawal.review.breadcrumb_withdrawal') }}</a>
                </li>
                <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ __('contract_withdrawal.review.breadcrumb_review') }}</li>
            </ol>
        </nav>

        <section class="d-md-flex justify-content-between align-items-center mb-4 pb-2">
            <div>
                <h1 class="h2 mb-2">{{ __('contract_withdrawal.review.title') }}</h1>
                <p class="withdrawal-page__intro">{{ __('contract_withdrawal.review.intro') }}</p>
            </div>
        </section>

        <div class="withdrawal-card mx-auto" style="max-width: 900px;">
            <div class="withdrawal-card__body">
                <div class="withdrawal-review-statement">
                    <small class="d-block text-uppercase text-muted mb-2" style="letter-spacing: .08em;">{{ __('contract_withdrawal.review.statement') }}</small>
                    {{ $declaration }}
                </div>

                <dl class="withdrawal-review-list">
                    @foreach ([
                        __('contract_withdrawal.full_name') => $withdrawal['full_name'],
                        'E-mail' => $withdrawal['email'],
                        __('contract_withdrawal.phone') => $withdrawal['phone'],
                        __('contract_withdrawal.review.address') => $withdrawal['address_line'].', '.$withdrawal['postal_code'].' '.$withdrawal['city'].', '.$withdrawal['country_code'],
                        __('contract_withdrawal.order_number') => $withdrawal['order_number'],
                        __('contract_withdrawal.contract_date') => $withdrawal['contract_date'],
                        __('contract_withdrawal.received_date') => $withdrawal['received_date'],
                        __('contract_withdrawal.review.contract_items') => $withdrawal['items'],
                        __('contract_withdrawal.note') => $withdrawal['note'],
                    ] as $label => $value)
                        <div class="withdrawal-review-list__row">
                            <dt>{{ $label }}</dt>
                            <dd>{{ $value !== '' ? $value : __('contract_withdrawal.review.not_provided') }}</dd>
                        </div>
                    @endforeach
                </dl>

                <div class="alert alert-warning mt-4 mb-0" role="note">
                    {{ __('contract_withdrawal.review.warning_before') }}
                    <strong>{{ __('contract_withdrawal.review.confirm') }}</strong>
                    {{ __('contract_withdrawal.review.warning_after') }}
                </div>

                <div class="withdrawal-review-actions">
                    <a class="withdrawal-edit-link" href="{{ \App\Helpers\LocaleHelper::route('contract-withdrawal.create') }}">
                        <i class="ci-arrow-left me-2"></i>{{ __('contract_withdrawal.review.edit') }}
                    </a>

                    <form method="POST" action="{{ \App\Helpers\LocaleHelper::route('contract-withdrawal.store') }}" data-confirm-withdrawal-form>
                        @csrf
                        <input type="hidden" name="draft_token" value="{{ $draftToken }}">
                        <button class="withdrawal-submit" type="submit" data-confirm-withdrawal>
                            {{ __('contract_withdrawal.review.confirm') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js_after')
    <script>
        (function () {
            var form = document.querySelector('[data-confirm-withdrawal-form]');

            if (!form) {
                return;
            }

            form.addEventListener('submit', function () {
                var button = form.querySelector('[data-confirm-withdrawal]');

                if (button) {
                    button.disabled = true;
                    button.textContent = @json(__('contract_withdrawal.review.submitting'));
                }
            });
        })();
    </script>
@endpush
