<form id="payForm" name="pay" class="w-100 needs-validation" action="{{ \App\Helpers\LocaleHelper::route('checkout') }}" novalidate method="GET">
    <input type="hidden" name="provjera" value="{{ $data['order_id'] }}">

    <div class="alert alert-success d-flex" role="status">
        <div class="alert-icon"><i class="fa-solid fa-gift" aria-hidden="true"></i></div>
        <div>{{ __('front.gift_voucher.checkout_fully_covered') }}</div>
    </div>

    <div class="form-check form-check-inline">
        <label class="form-check-label" for="terms">{!! __('front.checkout.terms_agree', [
            'terms_of_service' => '<a data-bs-toggle="modal" data-bs-target="#exampleModal" class="link-fx">'.__('front.checkout.terms_link').'</a>',
            'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="link-fx">'.__('Privacy Policy').'</a>',
        ]) !!}</label>
        <input class="form-check-input" type="checkbox" name="terms" id="terms" required>
        <div class="invalid-feedback">{{ __('front.checkout.terms_required') }}</div>
    </div>

    <div class="d-flex mt-3">
        <div class="w-50 pe-3">
            <a class="btn btn-secondary d-block w-100" href="{{ \App\Helpers\LocaleHelper::route('naplata') }}">
                <i class="fa-solid fa-arrow-left me-1"></i>{{ __('front.checkout.back') }}
            </a>
        </div>
        <div class="w-50 ps-2">
            <button id="paySubmit" class="btn checkout-cta d-block w-100" type="submit">
                {{ __('front.checkout.complete_order') }}<i class="fa-solid fa-arrow-right ms-1"></i>
            </button>
        </div>
    </div>
</form>

<script>
    (() => {
        const form = document.getElementById('payForm');
        const terms = document.getElementById('terms');
        if (!form || !terms) return;

        form.addEventListener('submit', event => {
            if (terms.checked) return;
            event.preventDefault();
            event.stopPropagation();
            terms.classList.add('is-invalid');
            form.classList.add('was-validated');
            terms.focus();
        }, true);
    })();
</script>
