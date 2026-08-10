<style>
    .checkout-page-heading {
        font-size: 1.9rem !important;
        line-height: 1.2;
    }

    .checkout-progress-shell {
        margin-bottom: 1.35rem;
        padding: .85rem 1.4rem 1.2rem;
        border-bottom: 1px solid rgba(49, 72, 55, .12);
    }

    .checkout-progress-shell .checkout-steps {
        overflow: visible;
        scrollbar-width: thin;
    }

    .checkout-progress-shell .step-progress {
        height: 2px;
        background-color: #e5eae6;
    }

    .checkout-progress-shell .step-count {
        top: -.87rem;
        width: 1.85rem;
        height: 1.85rem;
        margin-left: -.925rem;
        border: 3px solid #fff;
        font-size: .82rem;
        font-weight: 600;
        line-height: 1.47rem;
        color: #738078;
        background: #eef1ef;
        box-shadow: 0 0 0 1px rgba(49, 72, 55, .06);
    }

    .checkout-progress-shell .step-label {
        padding-top: 1.5rem;
        font-size: 1rem;
        font-weight: 500;
        line-height: 1.2;
        color: #6f7973;
        white-space: nowrap;
    }

    .checkout-progress-shell .step-label i {
        margin-right: .4rem;
        font-size: .95rem;
    }

    .checkout-progress-shell .step-item.active .step-label {
        color: #314837;
    }

    .checkout-progress-shell .step-item.current .step-label {
        font-weight: 700;
    }

    .checkout-progress-shell .step-item.active .step-count,
    .checkout-progress-shell .steps-dark .step-item.active .step-progress {
        color: #fff;
        background-color: var(--bs-primary);
    }

    .checkout-surface {
        padding: clamp(1.15rem, 2.2vw, 1.85rem) !important;
        border: 1px solid rgba(49, 72, 55, .1);
        border-radius: .85rem !important;
        background: #fff;
        box-shadow: 0 .65rem 1.8rem rgba(32, 49, 39, .065) !important;
    }

    .checkout-page .checkout-cta {
        border-color: #2f7d52;
        background: #2f7d52;
        box-shadow: 0 .35rem .9rem rgba(47, 125, 82, .18);
        color: #fff;
        font-weight: 600;
        text-transform: none;
        transition: background-color .16s ease, border-color .16s ease, box-shadow .16s ease, transform .16s ease;
    }

    .checkout-page .checkout-cta:hover,
    .checkout-page .checkout-cta:focus {
        border-color: #286b46;
        background: #286b46;
        box-shadow: 0 .45rem 1rem rgba(40, 107, 70, .24);
        color: #fff;
        transform: translateY(-1px);
    }

    .checkout-page .checkout-cta:focus-visible {
        box-shadow: 0 0 0 .2rem rgba(47, 125, 82, .25), 0 .45rem 1rem rgba(40, 107, 70, .22);
        outline: 0;
    }

    .checkout-page .checkout-cta:active {
        border-color: #245f3e;
        background: #245f3e;
        box-shadow: none;
        transform: translateY(0);
    }

    .checkout-page .checkout-cta.disabled,
    .checkout-page .checkout-cta:disabled {
        border-color: #2f7d52;
        background: #2f7d52;
        box-shadow: none;
        opacity: .55;
        transform: none;
    }

    @media (prefers-reduced-motion: reduce) {
        .checkout-page .checkout-cta {
            transition: none;
        }
    }

    @media (max-width: 767.98px) {
        .checkout-progress-shell {
            margin-right: -.35rem;
            margin-left: -.35rem;
            padding-right: .35rem;
            padding-left: .35rem;
            overflow-x: auto;
            overscroll-behavior-inline: contain;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
        }

        .checkout-progress-shell .checkout-steps {
            min-width: 32rem;
        }

        .checkout-progress-shell .step-label {
            font-size: .84rem;
        }
    }
</style>
