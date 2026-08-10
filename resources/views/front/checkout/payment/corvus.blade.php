<form id="payForm" name="pay" class="w-100 needs-validation" action="{{ $data['action'] }}" novalidate method="POST">
    <input type="hidden" name="amount" value="{{ $data['total'] }}">
    <input id="cart" name="cart" value="Web shop kupnja {{ $data['order_id'] }}" type="hidden"/>
    <input id="currency" name="currency" value="{{ $data['currency'] }}" type="hidden"/>
    <input id="language" name="language" value="{{ $data['lang'] }}" type="hidden"/>
    <input type="hidden" name="order_number" value="{{ $data['order_id'] }}">
    <input id="require_complete" name="require_complete" value="false" hidden="true"/>
    <input type="hidden" name="store_id" value="{{ $data['merchant'] }}">
    <input type="hidden" name="signature" value="{{ $data['md5'] }}">
    <input type="hidden" name="cardholder_name" value="{{ $data['firstname'] }}">
    <input type="hidden" name="cardholder_surname" value="{{ $data['lastname'] }}">
    <input type="hidden" name="cardholder_phone" value="{{ $data['telephone'] }}">
    <input type="hidden" name="cardholder_email" value="{{ $data['email'] }}">
    <input type="hidden" name="payment_all" value="{{ $data['number_of_installments'] }}">
    <input type="hidden" name="version" value="1.3">


    <div class="form-check form-check-inline">
        <label class="form-check-label" for="ex-check-4">{!! __('front.checkout.terms_agree', [
                                                'terms_of_service' => '<a data-bs-toggle="modal" data-bs-target="#exampleModal" class="link-fx">'.__('front.checkout.terms_link').'</a>',
                                                'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="link-fx">'.__('Privacy Policy').'</a>',
                                        ]) !!}</label>
        <input class="form-check-input" type="checkbox" name="terms" id="terms" required>
        <div class="invalid-feedback" id="terms">{{ __('front.checkout.terms_required') }}</div>
    </div>

    <div class="form-check form-check-inline">
        <label class="form-check-label" for="ex-check-4">{{ __('front.checkout.marketing_consent') }}</label>
        <input class="form-check-input" type="checkbox" name="obavijesti" id="obavijesti" checked>

    </div>

    <div class="d-flex mt-3">
        <div class="w-50 pe-3"><a class="btn btn-secondary d-block w-100" href="{{ \App\Helpers\LocaleHelper::route('naplata') }}"><i class="fa-solid fa-arrow-left mt-sm-0 me-1"></i><span class="d-none d-sm-inline">{{ __('front.checkout.back_to_payment') }}</span><span class="d-inline d-sm-none">{{ __('front.checkout.back') }}</span></a></div>
        <div class="w-50 ps-2"><button id="paySubmit" class="btn btn-green d-block w-100" type="submit"><span class="d-none d-sm-inline">{{ __('front.checkout.complete_order') }}</span><span class="d-inline d-sm-none">{{ __('front.checkout.complete_purchase') }}</span><i class="fa-solid fa-arrow-right mt-sm-0 ms-1"></i></button></div>
    </div>
    <div class="clearfix"></div>
</form>


<script>
    (function () {
        function init() {
            const form = document.getElementById('payForm');
            const terms = document.getElementById('terms');
            const btn = document.getElementById('paySubmit');

            if (!form || !terms || !btn) return;

            function block(e) {
                // ako nije checkirano -> stopaj sve
                if (!terms.checked) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (e.stopImmediatePropagation) e.stopImmediatePropagation();

                    terms.classList.add('is-invalid');
                    form.classList.add('was-validated');

                    terms.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    terms.focus({ preventScroll: true });
                    return true;
                }
                terms.classList.remove('is-invalid');
                return false;
            }

            // 1) presretni klik na gumb (često tu drugi JS radi redirect)
            btn.addEventListener('click', block, true);

            // 2) presretni submit (normalan submit + enter)
            form.addEventListener('submit', block, true);

            // 3) delegacija na dokument (ako submit dolazi “izvana”)
            document.addEventListener('submit', function (e) {
                if (e.target === form) block(e);
            }, true);

            // čim označi, makni grešku
            terms.addEventListener('change', () => {
                if (terms.checked) terms.classList.remove('is-invalid');
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>
