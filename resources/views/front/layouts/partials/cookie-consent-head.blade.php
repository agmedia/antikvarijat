<style>
    #cc-main {
        --cc-bg: #ffffff;
        --cc-primary-color: #314837;
        --cc-secondary-color: #4b566b;
        --cc-link-color: #9f8339;
        --cc-btn-primary-bg: #314837;
        --cc-btn-primary-color: #ffffff;
        --cc-btn-primary-border-color: #314837;
        --cc-btn-primary-hover-bg: #25372a;
        --cc-btn-primary-hover-color: #ffffff;
        --cc-btn-primary-hover-border-color: #25372a;
        --cc-btn-secondary-bg: #ffffff;
        --cc-btn-secondary-color: #314837;
        --cc-btn-secondary-border-color: rgba(191, 167, 106, 0.42);
        --cc-btn-secondary-hover-bg: #f8f5ed;
        --cc-btn-secondary-hover-color: #314837;
        --cc-btn-secondary-hover-border-color: #bfa76a;
        --cc-separator-border-color: rgba(191, 167, 106, 0.32);
        --cc-toggle-on-bg: #314837;
        --cc-toggle-off-bg: #aeb4be;
        --cc-toggle-readonly-bg: #bfa76a;
        --cc-cookie-category-block-bg: #faf8f2;
        --cc-cookie-category-block-border: rgba(191, 167, 106, 0.24);
        --cc-cookie-category-block-hover-bg: #f5f1e7;
        --cc-cookie-category-block-hover-border: rgba(191, 167, 106, 0.42);
        --cc-footer-bg: #f8f6f0;
        --cc-footer-color: #4b566b;
        --cc-footer-border-color: rgba(191, 167, 106, 0.32);
        --cc-overlay-bg: rgba(43, 52, 69, 0.58);
        --cc-modal-border-radius: 1.25rem;
        --cc-btn-border-radius: 0.75rem;
        --cc-font-family: "Roboto Slab", serif;
    }

    #cc-main .cm,
    #cc-main .pm {
        border: 1px solid rgba(191, 167, 106, 0.22);
        border-radius: 1.25rem;
        background:
            radial-gradient(circle at top right, rgba(191, 167, 106, 0.14), transparent 34%),
            linear-gradient(180deg, #ffffff 0%, #fcfbf7 100%);
        box-shadow: 0 24px 56px rgba(49, 72, 55, 0.2);
        overflow: hidden;
    }

    #cc-main .cm::before,
    #cc-main .pm::before {
        content: "";
        position: absolute;
        inset: 0 0 auto 0;
        height: 6px;
        background: linear-gradient(90deg, #314837 0%, #bfa76a 100%);
    }

    #cc-main .cm {
        max-width: 42rem;
        padding: 0;
    }

    #cc-main .cm__title,
    #cc-main .pm__title,
    #cc-main .pm__section-title {
        color: #314837;
        font-weight: 700;
    }

    #cc-main .cm__desc,
    #cc-main .pm__section-desc {
        color: #4b566b;
        line-height: 1.55;
    }

    #cc-main .cm__body {
        padding: 2.1rem 2.5rem 1.35rem;
    }

    #cc-main .cm__title {
        margin-bottom: 0.9rem;
    }

    #cc-main .cm__desc {
        margin-bottom: 1.2rem;
    }

    #cc-main .cm__footer,
    #cc-main .pm__footer {
        border-top: 1px solid rgba(191, 167, 106, 0.32);
        background: rgba(255, 255, 255, 0.88);
        backdrop-filter: blur(8px);
    }

    #cc-main .cm__footer {
        padding: 1rem 2.5rem 2rem;
    }

    #cc-main .cm__btn,
    #cc-main .pm__btn {
        min-height: 2.7rem;
        border-radius: 0.75rem;
        font-weight: 700;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
    }

    #cc-main .cm__btn:not(.cm__btn--secondary),
    #cc-main .pm__btn:not(.pm__btn--secondary) {
        box-shadow: 0 12px 26px rgba(49, 72, 55, 0.2);
    }

    #cc-main .cm__btn:not(.cm__btn--secondary):hover,
    #cc-main .pm__btn:not(.pm__btn--secondary):hover {
        transform: translateY(-1px);
        box-shadow: 0 16px 30px rgba(49, 72, 55, 0.25);
    }

    #cc-main .cm__btn-group {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0;
    }

    #cc-main .cm__btn-group .cm__btn + .cm__btn {
        margin-top: 0.55rem;
    }

    #cc-main .pm__header {
        padding-top: 1.35rem;
    }

    #cc-main .pm__body {
        padding-top: 0.5rem;
    }

    #cc-main .pm__badge {
        border-radius: 999px;
        background: rgba(191, 167, 106, 0.2);
        color: #314837;
        font-weight: 700;
    }

    #cc-main .pm__section {
        border: 1px solid rgba(191, 167, 106, 0.24);
        border-radius: 1rem;
        background: #faf8f2;
        overflow: hidden;
        transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
    }

    #cc-main .pm__section:hover {
        border-color: rgba(191, 167, 106, 0.42);
        background: #f5f1e7;
        box-shadow: 0 10px 24px rgba(49, 72, 55, 0.08);
    }

    #cc-main .pm__service-icon {
        border-color: #bfa76a;
    }

    #cc-main .pm__section-arrow svg {
        stroke: #314837;
    }

    .cookie-consent-trigger {
        position: fixed;
        left: 1.25rem;
        bottom: 1.25rem;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        width: 3.25rem;
        height: 3.25rem;
        padding: 0;
        border: 0;
        border-radius: 50% !important;
        background: #314837;
        color: #ffffff !important;
        box-shadow: 0 0.75rem 1.75rem rgba(49, 72, 55, 0.28);
        z-index: 1027;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
    }

    .cookie-consent-trigger:hover {
        transform: translateY(-2px);
        background: #25372a;
        color: #ffffff !important;
        box-shadow: 0 1rem 2rem rgba(49, 72, 55, 0.34);
    }

    .cookie-consent-trigger:focus-visible {
        outline: 3px solid rgba(191, 167, 106, 0.48);
        outline-offset: 3px;
    }

    .cookie-consent-trigger i {
        color: #ffffff;
        font-size: 1.55rem;
        line-height: 1;
        filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.12));
    }

    @media (max-width: 991.98px) {
        .cookie-consent-trigger {
            bottom: 4.75rem;
        }
    }

    @media (max-width: 767.98px) {
        .cookie-consent-trigger {
            display: none !important;
        }
    }

    @media (max-width: 575.98px) {
        #cc-main .cm__body,
        #cc-main .cm__footer {
            padding-left: 1.2rem;
            padding-right: 1.2rem;
        }

        .cookie-consent-trigger {
            left: 0.85rem;
            width: 3rem;
            height: 3rem;
        }
    }
</style>
