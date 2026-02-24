<script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.sitekey') }}"></script>
<script>
    if (window.grecaptcha && typeof grecaptcha.ready === 'function') {
        grecaptcha.ready(function() {
            grecaptcha.execute('{{ config('services.recaptcha.sitekey') }}', {action: 'register'}).then(function(token) {
                if (token) {
                    document.getElementById('recaptcha').value = token;
                }
            });
        });
    } else if (document.getElementById('recaptcha')) {
        document.getElementById('recaptcha').value = 'local-bypass';
    }
</script>
