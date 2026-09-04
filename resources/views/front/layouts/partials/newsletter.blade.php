@php
    $newsletterCaptchaBypassed = app()->environment(['local', 'testing'])
        && config('services.recaptcha.bypass_local', true);
    $newsletterCaptchaEnabled = trim((string) config('services.recaptcha.sitekey')) !== ''
        && trim((string) config('services.recaptcha.secret')) !== ''
        && ! $newsletterCaptchaBypassed;
@endphp

<section class="py-4 py-md-5 bg-secondary border-top border-bottom">
    <div class="container">
        <div class="row align-items-center g-3 g-md-4">
            <div class="col-lg-5">
                <p class="text-uppercase  fw-bold mb-1 text-primary">{{ __('front.newsletter.eyebrow') }}</p>
                <h4 class="mb-1">{{ __('front.newsletter.title') }}</h4>
                <p class="mb-0 fs-md text-muted">{{ __('front.newsletter.subtitle') }}</p>
            </div>

            <div class="col-lg-7">
                @if (old('newsletter_form') && ($errors->has('email') || $errors->has('gdpr') || $errors->has('newsletter_started_at') || $errors->has('recaptcha')))
                    <div class="alert alert-danger py-2 px-3 mb-2">
                        {{ $errors->first('newsletter_started_at') ?: ($errors->first('recaptcha') ?: __('front.newsletter.validation')) }}
                    </div>
                @endif

                @if (session()->has('newsletter_success'))
                    <div class="alert alert-success py-2 px-3 mb-2">
                        {{ session('newsletter_success') }}
                    </div>
                @endif

                <div id="newsletter-alert" class="alert d-none mb-2 py-2 px-3 rounded-3 shadow-sm" role="alert"></div>

                <form id="newsletter-form" class="p-0" action="{{ \App\Helpers\LocaleHelper::route('newsletter.subscribe') }}" method="post" novalidate>
                    @csrf
                    <input type="hidden" name="newsletter_form" value="1">
                    <input type="hidden" name="newsletter_started_at" value="{{ app(\App\Services\NewsletterSignupGuard::class)->issueToken() }}">
                    <input type="hidden" name="recaptcha" value="" data-newsletter-recaptcha>

                    <div aria-hidden="true" style="position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden;" tabindex="-1">
                        <label for="newsletter_website">Website</label>
                        <input type="text" id="newsletter_website" name="website" value="" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="row g-2">
                        <div class="col-md-8">
                            <label class="form-label mb-1 fs-sm" for="newsletter_email">{{ __('front.newsletter.email') }}</label>
                            <input class="form-control bg-white" type="email" id="newsletter_email" name="email" value="{{ old('email') }}" placeholder="{{ __('front.newsletter.email_placeholder') }}" required>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button class="btn btn-primary w-100" type="submit">{{ __('front.newsletter.submit') }}</button>
                        </div>
                    </div>

                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="newsletter_gdpr" name="gdpr" value="1" {{ old('gdpr') ? 'checked' : '' }} required>
                        <label class="form-check-label fs-xs text-muted" for="newsletter_gdpr">
                            {{ __('front.newsletter.gdpr') }}
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

            var captchaEnabled = @json($newsletterCaptchaEnabled);
            var captchaSiteKey = @json(config('services.recaptcha.sitekey'));
            var captchaInput = form.querySelector('[data-newsletter-recaptcha]');
            var captchaLoader = null;
            var submissionPending = false;

            function showAlert(type, message) {
                alertBox.classList.remove('d-none', 'alert-success', 'alert-danger');
                alertBox.classList.add(type === 'success' ? 'alert-success' : 'alert-danger');
                alertBox.textContent = message;
            }

            function loadRecaptcha() {
                if (window.grecaptcha && typeof window.grecaptcha.ready === 'function') {
                    return Promise.resolve(window.grecaptcha);
                }

                if (captchaLoader) {
                    return captchaLoader;
                }

                captchaLoader = new Promise(function (resolve, reject) {
                    var script = document.createElement('script');
                    script.src = 'https://www.google.com/recaptcha/api.js?render=' + encodeURIComponent(captchaSiteKey);
                    script.async = true;
                    script.defer = true;
                    script.onload = function () {
                        if (window.grecaptcha) {
                            resolve(window.grecaptcha);
                        } else {
                            reject(new Error('reCAPTCHA unavailable'));
                        }
                    };
                    script.onerror = function (error) {
                        captchaLoader = null;
                        reject(error);
                    };
                    document.head.appendChild(script);
                });

                return captchaLoader;
            }

            function captchaToken() {
                if (!captchaEnabled) {
                    return Promise.resolve('');
                }

                return loadRecaptcha().then(function (grecaptcha) {
                    return new Promise(function (resolve, reject) {
                        grecaptcha.ready(function () {
                            grecaptcha.execute(captchaSiteKey, { action: 'newsletter_subscribe' })
                                .then(resolve)
                                .catch(reject);
                        });
                    });
                });
            }

            function withTimeout(promise, timeout) {
                return new Promise(function (resolve, reject) {
                    var timer = window.setTimeout(function () {
                        reject(new Error('reCAPTCHA timed out'));
                    }, timeout);

                    promise.then(function (value) {
                        window.clearTimeout(timer);
                        resolve(value);
                    }).catch(function (error) {
                        window.clearTimeout(timer);
                        reject(error);
                    });
                });
            }

            function finishSubmission(submitBtn, originalBtnText) {
                submissionPending = false;

                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalBtnText;
                }
            }

            function sendForm(submitBtn, originalBtnText) {
                $.ajax({
                    url: form.action,
                    method: 'POST',
                    data: $(form).serialize(),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: function (response) {
                        showAlert('success', response.message || @json(__('front.newsletter.success')));
                        form.reset();
                    },
                    error: function (xhr) {
                        var message = @json(__('front.messages.server_error'));
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            if (xhr.responseJSON.errors.newsletter_started_at) {
                                message = xhr.responseJSON.errors.newsletter_started_at[0];
                            } else if (xhr.responseJSON.errors.recaptcha) {
                                message = xhr.responseJSON.errors.recaptcha[0];
                            } else if (xhr.responseJSON.errors.email) {
                                message = xhr.responseJSON.errors.email[0];
                            } else if (xhr.responseJSON.errors.gdpr) {
                                message = xhr.responseJSON.errors.gdpr[0];
                            }
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        showAlert('error', message);
                    },
                    complete: function () {
                        if (captchaInput) {
                            captchaInput.value = '';
                        }
                        finishSubmission(submitBtn, originalBtnText);
                    }
                });
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                if (submissionPending) return;

                submissionPending = true;

                var submitBtn = form.querySelector('button[type="submit"]');
                var originalBtnText = submitBtn ? submitBtn.textContent : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = @json(__('front.general.loading'));
                }

                if (captchaInput) {
                    captchaInput.value = '';
                }

                withTimeout(captchaToken(), 12000)
                    .then(function (token) {
                        if (captchaEnabled && !token) {
                            throw new Error('Missing reCAPTCHA token');
                        }

                        if (captchaInput) {
                            captchaInput.value = token || '';
                        }

                        sendForm(submitBtn, originalBtnText);
                    })
                    .catch(function () {
                        captchaLoader = null;
                        showAlert('error', @json(__('front.newsletter.captcha_failed')));
                        finishSubmission(submitBtn, originalBtnText);
                    });
            });
        })();
    </script>
@endpush
