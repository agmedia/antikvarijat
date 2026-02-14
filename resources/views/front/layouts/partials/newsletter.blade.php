<section class="py-4 py-md-5 bg-secondary border-top border-bottom">
    <div class="container">
        <div class="row align-items-center g-3 g-md-4">
            <div class="col-lg-5">
                <p class="text-uppercase  fw-bold mb-1 text-primary">Biblos Newsletter</p>
                <h4 class="mb-1">Za knjigoljupce koji vole pronaći rijetko</h4>
                <p class="mb-0 fs-md text-muted">Novi naslovi, posebni primjerci i tihe preporuke iz antikvarijata.</p>
            </div>

            <div class="col-lg-7">
                @if (old('newsletter_form') && ($errors->has('email') || $errors->has('gdpr')))
                    <div class="alert alert-danger py-2 px-3 mb-2">
                        Provjerite email i potvrdite GDPR privolu.
                    </div>
                @endif

                @if (session()->has('newsletter_success'))
                    <div class="alert alert-success py-2 px-3 mb-2">
                        {{ session('newsletter_success') }}
                    </div>
                @endif

                <div id="newsletter-alert" class="alert d-none mb-2 py-2 px-3 rounded-3 shadow-sm" role="alert"></div>

                <form id="newsletter-form" class="p-0" action="{{ route('newsletter.subscribe') }}" method="post" novalidate>
                    @csrf
                    <input type="hidden" name="newsletter_form" value="1">
                    <div class="row g-2">
                        <div class="col-md-8">
                            <label class="form-label mb-1 fs-sm" for="newsletter_email">E-mail adresa</label>
                            <input class="form-control bg-white" type="email" id="newsletter_email" name="email" value="{{ old('email') }}" placeholder="ime.prezime@email.com" required>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button class="btn btn-primary w-100" type="submit">Prijavi me</button>
                        </div>
                    </div>

                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="newsletter_gdpr" name="gdpr" value="1" {{ old('gdpr') ? 'checked' : '' }} required>
                        <label class="form-check-label fs-xs text-muted" for="newsletter_gdpr">
                            Dajem privolu za primanje newslettera i obradu podataka u skladu s GDPR-om.
                        </label>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

@push('js_after')
    <script>
        (function () {
            var form = document.getElementById('newsletter-form');
            var alertBox = document.getElementById('newsletter-alert');
            if (!form || !alertBox || typeof window.jQuery === 'undefined') return;

            function showAlert(type, message) {
                alertBox.classList.remove('d-none', 'alert-success', 'alert-danger');
                alertBox.classList.add(type === 'success' ? 'alert-success' : 'alert-danger');
                alertBox.textContent = message;
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var submitBtn = form.querySelector('button[type="submit"]');
                var originalBtnText = submitBtn ? submitBtn.textContent : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Spremam...';
                }

                $.ajax({
                    url: form.action,
                    method: 'POST',
                    data: $(form).serialize(),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: function (response) {
                        showAlert('success', response.message || 'Hvala! Uspješno ste prijavljeni na newsletter.');
                        form.reset();
                    },
                    error: function (xhr) {
                        var message = 'Došlo je do greške. Pokušajte ponovno.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            if (xhr.responseJSON.errors.email) {
                                message = xhr.responseJSON.errors.email[0];
                            } else if (xhr.responseJSON.errors.gdpr) {
                                message = xhr.responseJSON.errors.gdpr[0];
                            }
                        }
                        showAlert('error', message);
                    },
                    complete: function () {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalBtnText;
                        }
                    }
                });
            });
        })();
    </script>
@endpush
