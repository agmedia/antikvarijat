<div class="impersonation-banner" role="status" aria-live="polite">
    <div class="container impersonation-banner__inner">
        <div class="impersonation-banner__message">
            <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
            <div>
                <strong>{{ __('front.impersonation.banner_title') }}</strong>
                <span>{{ __('front.impersonation.banner_text', ['name' => auth()->user()->name, 'email' => auth()->user()->email]) }}</span>
            </div>
        </div>
        <form action="{{ route('impersonation.stop') }}" method="POST">
            @csrf
            <button class="btn btn-light" type="submit">
                <i class="fa-solid fa-arrow-right-from-bracket me-2" aria-hidden="true"></i>{{ __('front.impersonation.stop') }}
            </button>
        </form>
    </div>
</div>
