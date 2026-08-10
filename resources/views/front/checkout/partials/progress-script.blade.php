<script>
    (function () {
        if (window.checkoutProgressAutoScrollReady) {
            return;
        }

        window.checkoutProgressAutoScrollReady = true;

        var mobileProgress = window.matchMedia('(max-width: 767.98px)');
        var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        var resizeTimer;

        function positionCheckoutProgress(immediate) {
            if (!mobileProgress.matches) {
                return;
            }

            document.querySelectorAll('.checkout-progress-shell').forEach(function (shell) {
                var current = shell.querySelector('.checkout-steps .step-item.current');

                if (!current) {
                    return;
                }

                var currentCenter = current.offsetLeft + (current.offsetWidth / 2);
                var desiredLeft = currentCenter - (shell.clientWidth * .4);
                var maxLeft = Math.max(0, shell.scrollWidth - shell.clientWidth);
                var targetLeft = Math.max(0, Math.min(desiredLeft, maxLeft));

                if (Math.abs(shell.scrollLeft - targetLeft) < 2) {
                    return;
                }

                if (typeof shell.scrollTo === 'function') {
                    shell.scrollTo({
                        left: targetLeft,
                        behavior: immediate || reducedMotion.matches ? 'auto' : 'smooth',
                    });
                } else {
                    shell.scrollLeft = targetLeft;
                }
            });
        }

        function scheduleCheckoutProgress(immediate) {
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(function () {
                    positionCheckoutProgress(immediate);
                });
            });
        }

        function initializeCheckoutProgress() {
            scheduleCheckoutProgress(true);

            if (window.Livewire && typeof window.Livewire.hook === 'function') {
                window.Livewire.hook('message.processed', function () {
                    scheduleCheckoutProgress(false);
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeCheckoutProgress, { once: true });
        } else {
            initializeCheckoutProgress();
        }

        window.addEventListener('checkout-step-changed', function () {
            scheduleCheckoutProgress(false);
        });

        window.addEventListener('resize', function () {
            window.clearTimeout(resizeTimer);
            resizeTimer = window.setTimeout(function () {
                scheduleCheckoutProgress(true);
            }, 120);
        });
    }());
</script>
