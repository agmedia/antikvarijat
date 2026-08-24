<style>
    body.is-impersonating { padding-bottom: 5.25rem; }
    .impersonation-banner {
        position: fixed;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: 1080;
        border-top: 1px solid rgba(255, 255, 255, .26);
        background: #8a651d;
        color: #fff;
        box-shadow: 0 -.75rem 2rem rgba(39, 35, 24, .2);
    }
    .impersonation-banner__inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        min-height: 4.6rem;
        padding-top: .7rem;
        padding-bottom: .7rem;
    }
    .impersonation-banner__message { display: flex; align-items: center; gap: .85rem; min-width: 0; }
    .impersonation-banner__message > i { font-size: 1.45rem; }
    .impersonation-banner__message strong,
    .impersonation-banner__message span { display: block; }
    .impersonation-banner__message strong { margin-bottom: .08rem; font-size: .95rem; }
    .impersonation-banner__message span { color: rgba(255, 255, 255, .88); font-size: .82rem; }
    .impersonation-banner .btn { white-space: nowrap; font-weight: 700; }
    body.is-impersonating .btn-scroll-top,
    body.is-impersonating .cookie-consent-trigger { bottom: 6rem; }
    @media (max-width: 575.98px) {
        body.is-impersonating { padding-bottom: 8.25rem; }
        .impersonation-banner__inner { align-items: stretch; flex-direction: column; gap: .65rem; padding-top: .8rem; padding-bottom: .8rem; }
        .impersonation-banner form,
        .impersonation-banner .btn { width: 100%; }
        body.is-impersonating .btn-scroll-top,
        body.is-impersonating .cookie-consent-trigger { bottom: 8.9rem; }
    }
</style>
